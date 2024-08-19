<?php

namespace App\Models\Mysql;

use App\Http\Controllers\Response;
use App\Common\Functions;
use App\Libraries;
use App\Common\Validator;
use Aws\S3\S3Client;
use App\Models\Dynamo;

use Zebra\Zpl\Image as ZplImage;
use Zebra\Zpl\Builder as ZplBuilder;
use Zebra\Zpl\GdDecoder as ZplGdDecoder;

use ApiAuth;

class Label extends Base
{
    public $table = 'labels';
    
    protected $hidden = [
        'verification_service',
        'verification_id',
        'user_id',
        'api_key_id',   
        'verified',
        'from_address_override'
    ];
    
    public $shipment;
    public $rate;
    public $from_address;
    public $scan_form;
    public $pickup;
    public $model_pairs = [
        ['shipment', 'shipment_id', Shipment::class],
        ['rate', 'rate_id', Rate::class],
        ['from_address', 'from_address_id', Address::class],
        ['scan_form', 'scan_form_id', ScanForm::class],
        ['pickup', 'pickup_id', Pickup::class]
    ];
    
    protected $casts = [
        'ship_date' => 'datetime',
        'delivery_date' => 'datetime'
    ];
    
    // search users
    public static $search_parameters = [
        [
            'argument' => 'from_address_id',
            'column' => 'from_address_id', 
            'type' => 'EQUAL'
        ],
        [
            'argument' => 'scan_form_id',
            'column' => 'scan_form_id', 
            'type' => 'EQUAL'
        ],
        [
            'argument' => 'pickup_id',
            'column' => 'pickup_id', 
            'type' => 'EQUAL'
        ],
        [
            'argument' => 'label_ids', 
            'column' => 'id',
            'type' => 'IN'
        ],
        [
            'argument' => 'external_user_id',
            'column' => 'external_user_id', 
            'type' => 'EQUAL'
        ]
    ];

    /**purpose
     *   create a label
     * args
     *   shipment_id (required)
     *   rate_id (required)
     *   ship_date (optional) (default - current day)
     * returns
     *   label
     */
    public static function create($model_data, $user, $api_key_id = null) {
        
        // create response
        $response = new Response;

        // validate label size
        $label_size = isset($model_data->label_size) ? $model_data->label_size : Label::IMAGE_SIZE_4X6;
        if (!Label::isValidLabelSize($label_size)) return $response->setFailure('Invalid label size', 'INVALID_PROPERTY', 'INVALID_LABEL_SIZE');

        // validate file type
        $file_type = isset($model_data->file_type) ? $model_data->file_type : Label::FILE_TYPE_JPG;
        if (!Label::isValidFileType($file_type)) return $response->setFailure('Invalid file type', 'INVALID_PROPERTY', 'INVALID_FILE_TYPE');

        // create address model
        $model = new Label;
        $model->user_id = isset($user->parent_user_id) ? $user->parent_user_id : $user->id;
        $model->created_user_id = $user->id;
        $model->api_key_id = isset($api_key_id) ? $api_key_id : 'INTERNAL';

        // get shipment
        if (!isset($model_data->shipment_id)) return $response->setFailure('Missing shipment id', 'MISSING_REQUIRED_FIELDS', 'MISSING_SHIPMENT_ID');
        $shipment = Shipment::find($model_data->shipment_id);
        if (!isset($shipment)) return $response->setFailure('Invalid shipment', 'INVALID_PROPERTY', 'INVALID_SHIPMENT_ID');
        if ($shipment->created_user_id != $user->id) return $response->setFailure('Invalid shipment', 'INVALID_PROPERTY', 'INVALID_SHIPMENT_ID');
        $model->shipment_id = $shipment->id;
        $model->from_address_id = $shipment->from_address_id;
        $model->weight = $shipment->weight;
        $model->external_user_id = $shipment->external_user_id;
        $model->order_group_id = $shipment->order_group_id;
        $model->from_address_override = $shipment->from_address_override;

        // get items
        $customs = Dynamo\Customs::findSk($shipment->id);
        $model->customs = $shipment->customs;
        $model->international = $shipment->international;

        // validate ship_date
        $time_adjusted = strtotime($shipment->ship_date);
        $morning = strtotime(date('Y-m-d', time()));
        if ($time_adjusted < $morning) return $response->setFailure('Shipment has expired', 'EXPIRED', 'SHIPMENT_EXPIRED');
        $model->ship_date = $shipment->ship_date;

        // return address
        $model->return_address_id = $shipment->return_address_id;

        // check if label was already bought for shipment
        if (!Functions::isEmpty($shipment->label_id)) return $response->setFailure('Label already purchased for shipment', 'DUPLICATE_PURCHASE');

        // get rate
        if (!isset($model_data->rate_id)) return $response->setFailure('Missing rate id', 'MISSING_REQUIRED_FIELDS', 'MISSING_RATE_ID');
        $rate = Rate::find($model_data->rate_id);
        if (!isset($rate)) return $response->setFailure('Invalid rate id', 'INVALID_PROPERTY', 'INVALID_RATE_ID');
        if ($rate->shipment_id != $shipment->id) return $response->setFailure('Invalid rate id', 'INVALID_PROPERTY', 'INVALID_RATE_ID');
     
        // set rate on model
        $model->rate_id = $rate->id;
        $model->service = $rate->service;
        $model->delivery_days = $rate->delivery_days;
        $model->delivery_date = $rate->delivery_date;
        $model->delivery_guarantee = $rate->delivery_guarantee;

        // validate there is enough money in wallet
        if (round((float) $rate->rate_list, 2) > round($user->getWalletBalance(), 2)) {
            return $response->setFailure('Wallet balance not high enough - please refill', 'INSUFFICIENT_FUNDS', 'INSUFFICIENT_FUNDS');
        }

        // get to address
        $to_address = Address::find($shipment->to_address_id);
        if (!isset($to_address)) return $response->setFailure('Cannot find to address linked to shipment', 'INVALID_SHIPMENT', 'INVALID_TO_ADDRESS');

        // validate shipment with validation service
        $label_validator = $to_address->isUSDomestic() ? new Libraries\Label\LabelValidator : new Libraries\InternationalLabel\Validator;
        $label_validator = $label_validator->validateLabelModel($model, $shipment, $rate, $customs);
        if ($label_validator->isFailure()) return $label_validator;

        // save model
        $model->save();

        $cost = $label_validator->get('cost');

        // create wallet transaction
        $new_wallet_transaction = new WalletTransaction;
        $new_wallet_transaction->user_id = $model->user_id;
        $new_wallet_transaction->created_user_id = $user->id;
		$new_wallet_transaction->api_key_id = isset($api_key_id) ? $api_key_id : 'INTERNAL';;
        $new_wallet_transaction->type = 'Label';
        $new_wallet_transaction->label_id = $model->id;
		$new_wallet_transaction->amount = (string) (($rate->total_charge) * -1);
		$new_wallet_transaction->cost = isset($cost) ? $cost : $rate->total_charge;
        $new_wallet_transaction->profit = ($new_wallet_transaction->amount * -1) - $new_wallet_transaction->cost;
        $new_wallet_transaction->profit_calculated = isset($cost);
        $new_wallet_transaction->pending = 0;
        $new_wallet_transaction->processing_fee = 0;
        $new_wallet_transaction->failed = 0;
        $new_wallet_transaction->failed_message = '';
        $new_wallet_transaction->finalized_at = Functions::convertTimeToMysql(time());

        // set new balance
		$new_balance = $user->getWalletBalance() - $rate->total_charge;
		$new_wallet_transaction->balance = (string) round($new_balance, 2);
		$new_wallet_transaction->save();
		
        // update user balance
        User::where('id', '=', $model->user_id)->update(['wallet_balance' => $new_balance]);

        // update shipment to include label id
        $shipment->label_id = $model->id;
        $shipment->save();
            
        // build default label image
        $model->setSubModel('label_url', $model->getImageUrl($label_size, $file_type));

        // set model
        $response->set('model', $model);
        $response->set('wallet_transaction', $new_wallet_transaction);


        if (isset($model->order_group_id)) {
            $order_group = OrderGroup::find($model->order_group_id);
            if (isset($order_group)) $order_group->fulfill($user, $model);
        }

        // auto refill wallet
        $refill_response = $user->checkAutoRefill();
        if ($refill_response->result == 'success') {
            $response->set('refill_transaction', $refill_response->get('wallet_transaction'));
        }

        return $response->setSuccess();
    }
    
    /**purpose
     *   refund a label
     * args
     *   (none)
     * returns
     *   refund_response
     */
    public function refund() {
        
        // create response
        $response = new Response;

        // get rate
        $rate = Rate::find($this->rate_id);
        if (!isset($rate)) return $response->setFailure('Cannot locate rate', 'INTERNAL_ERROR', 'UNABLE_TO_LOCATE_RATE');

        // check for existing pending refund
        $existing_transaction = WalletTransaction::where([
            ['type', '=', 'Refund'],
            ['label_id', '=', $this->id],
            ['failed', '=', 0]
        ])->limit(1)->get()->first();

        if (isset($existing_transaction)) {
            return $response->setFailure(
                (Validator::validateBoolean($existing_transaction->pending) ? 
                    'Label refund already submitted' : 
                    'Label already refunded'), 
                'DUPLICATE_REFUND',
                'DUPLICATE_REFUND'
            );
        }

        // validate refund
        $refund_validator = new Libraries\Refund\RefundValidator;
        $validated_refund = $refund_validator->validateRefundModel(
            $this
        );
        if ($validated_refund->isFailure()) {
            if (time() > strtotime('+65 days', strtotime($this->created_at))) {
                return $validated_refund;
            }
        }

        $this->refunded = 1;
        $this->save();

        // new wallet transaction
        $new_wallet_transaction = new WalletTransaction;
		$new_wallet_transaction->user_id = ApiAuth::user()->id;
		$new_wallet_transaction->api_key_id = ApiAuth::apiKeyId();
        $new_wallet_transaction->type = 'Refund';
        $new_wallet_transaction->label_id = $this->id;
		$new_wallet_transaction->amount = $rate->rate;
		$new_wallet_transaction->cost = 0;
        $new_wallet_transaction->profit = 0;
        $new_wallet_transaction->pending = 1;
        $new_wallet_transaction->processing_fee = 0;
        $new_wallet_transaction->failed = 0;
        $new_wallet_transaction->failed_message = '';
        $new_wallet_transaction->save();

        $response->set('transaction', $new_wallet_transaction);

        return $response->setSuccess();
    }


    /**purpose
     *   get a packing slip redirect link
     * args
     *   size (optional) (default 6x4)
     * returns
     *   redirect_url
     */
    public function getPackingSlipImageUrl($size, $file_type = Label::FILE_TYPE_JPG) {
        $image_path = env('APP_GOAKEY', 'goa') . '/' . $this->id . '/' . $size . '-packing.' . strtolower($file_type);

        $full_image_path = 'https://goasolutions-labels.s3.us-west-2.amazonaws.com/' . $image_path;

        // create s3 client and put object to s3
        $s3_client = S3Client::factory(array(
            'credentials' => array(
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY')
            ),
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
        ));

        // save object to s3 storage
        $results = $s3_client->listObjectsV2(array(
            'Bucket'            => env('AWS_BUCKET'),
            'Prefix'            => $image_path
        ));

        // check key count
        //if ($results->get('KeyCount') == 0) {
            $this->generatePackingSlipImage($s3_client, $size, $image_path, $file_type);
        //}

        // clear image for garbage collection
        return $full_image_path;
    }

    /**purpose
     *   get image redirect link
     * args
     *   size (optional) (default 6x4)
     * returns
     *   redirect_url
     */
    public function getImageUrl($size, $file_type = Label::FILE_TYPE_JPG) {
        
        $image_path = env('APP_GOAKEY', 'goa') . '/' . $this->id . '/' . $size . '.' . strtolower($file_type);
        $full_image_path = 'https://goasolutions-labels.s3.us-west-2.amazonaws.com/' . $image_path;

        // create s3 client and put object to s3
        $s3_client = S3Client::factory(array(
            'credentials' => array(
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY')
            ),
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
        ));

        // save object to s3 storage
        $results = $s3_client->listObjectsV2(array(
            'Bucket'            => env('AWS_BUCKET'),
            'Prefix'            => $image_path
        ));

        // check key count
        //if ($results->get('KeyCount') == 0) {
            if (Validator::validateBoolean($this->customs)) $this->generateCustomsLabel($s3_client, $size, $image_path, $file_type);
            else $this->generateImage($s3_client, $size, $image_path, $file_type);
       // }

        // clear image for garbage collection
        return $full_image_path;
    }

    /**
     * check label to see if it is a valid label
     */
    public static function isValidLabelSize($size) {
        return in_array($size, [
            Label::IMAGE_SIZE_4X5,
            Label::IMAGE_SIZE_4X6/*,
            Label::IMAGE_SIZE_4X4,
            Label::IMAGE_SIZE_2X7*/
        ]);
    }

    /**
     * check file type to see if it is valid
     */
    public static function isValidFileType($size) {
        return in_array($size, [
            Label::FILE_TYPE_JPG,
            Label::FILE_TYPE_ZPL,
            Label::FILE_TYPE_PNG
        ]);
    }

    // variables associated with label printing
    const IMAGE_SIZE_4X6 = '4X6';
    const IMAGE_SIZE_4X5 = '4X5';
    const IMAGE_SIZE_4X4 = '4X4';
    const IMAGE_SIZE_2X7 = '2X7';
    const IMAGE_DPI = 200;

    // create differetn file types available 
    const FILE_TYPE_JPG = 'JPG';
    const FILE_TYPE_ZPL = 'ZPL';
    const FILE_TYPE_PNG = 'PNG';

    // image width for label printing
    const IMAGE_WIDTH = [
        Label::IMAGE_SIZE_4X6 => 4 * Label::IMAGE_DPI,
        Label::IMAGE_SIZE_4X5 => 4 * Label::IMAGE_DPI,
        Label::IMAGE_SIZE_2X7 => 7 * Label::IMAGE_DPI,
        Label::IMAGE_SIZE_4X4 => 4 * Label::IMAGE_DPI
    ];

    // image height for label printing
    const IMAGE_HEIGHT = [
        Label::IMAGE_SIZE_4X6 => 6 * Label::IMAGE_DPI,
        Label::IMAGE_SIZE_4X5 => 5 * Label::IMAGE_DPI,
        Label::IMAGE_SIZE_2X7 => 2 * Label::IMAGE_DPI,
        Label::IMAGE_SIZE_4X4 => 4 * Label::IMAGE_DPI
    ];

    const IMAGE_SERVICE_LETTER = [
        'Cubic' => 'P',
        'Priority' => 'P',
        'First Class' => 'F',
        'Priority Express' => 'E'
    ];

    const IMAGE_SERVICE_BANNER_TEXT = [
        'Cubic' => '        USPS PRIORITY MAIL',
        'Priority' => '        USPS PRIORITY MAIL',
        'Priority Express' => 'USPS PRIORITY MAIL EXPRESS',
        'First Class' => '     USPS FIRST-CLASS PKG',
        'Parcel Select' => '       USPS PARCEL SELECT',
    ];

    private function generateCustomsLabel($s3_client, $size, $image_path, $file_type) {
        
        // create image with dimensions
        $image = imagecreatetruecolor(Label::IMAGE_WIDTH[$size], Label::IMAGE_HEIGHT[$size]);
        imageresolution($image, 100);

        
        /****Barcode Segment */
        $image_barcode = imagecreatefromjpeg($this->url);
        $image_barcode_cropped = $this->trimImageWhiteSpace($image_barcode);

        if (Validator::validateBoolean($this->international)) {
            $color_white = imagecolorallocate($image, 255, 255, 255);
            $image_barcode_cropped = imagerotate(
                $image_barcode_cropped,
                90,
                $color_white
            );
        }

        $source_x = 0;
        $source_y = 0;
        $source_width = imagesx($image_barcode_cropped);
        $source_height = imagesy($image_barcode_cropped);

        imagecopyresized(
            $image,
            $image_barcode_cropped,
            2,
            2,
            $source_x,
            $source_y,
            Label::IMAGE_WIDTH[$size],
            Label::IMAGE_HEIGHT[$size],
            $source_width,
            $source_height
        ); 

        $image_data = null;
        if ($file_type == Label::FILE_TYPE_JPG) {

            $image_stream = fopen('php://memory','r+');
            imagejpeg($image, $image_stream);
            rewind($image_stream);
            $image_data = stream_get_contents($image_stream);
        }
        else if ($file_type == Label::FILE_TYPE_ZPL) {
            // if type zpl convert to zpl file
            $decoder = ZplGdDecoder::fromResource($image);
            $zpl_image = new ZplImage($decoder);

            $zpl = new ZplBuilder();
            $zpl->fo(0, 0)->gf($zpl_image)->fs();
            $image_data = $zpl->toZpl();
        }
        
        // save object to s3 storage
        $s3_client->putObject(array(
            'Bucket'            => env('AWS_BUCKET'),
            'Key'               => $image_path,
            'Body'              => $image_data,
            'ContentEncoding'   => 'image/jpg'
        ));
    }

    
    /**purpose
     *   generate image
     * args
     *   size
     *   image_path
     * returns
     *   (none)
     */
    private function generatePackingSlipImage($s3_client, $size, $image_path, $file_type) {

        // create image with dimensions
        $image = imagecreatetruecolor(Label::IMAGE_WIDTH[$size], Label::IMAGE_HEIGHT[$size]);
        imageresolution($image, 100);

        // create colors
        $color_white = imagecolorallocate($image, 255, 255, 255);
        $color_black = imagecolorallocate($image, 0, 0, 0);
        $font_arial = __DIR__ . '/Fonts/Arial.ttf';
        $font_arial_bold = __DIR__ . '/Fonts/Arial-Bold.ttf';
        
        // make full image white
        imagefilledrectangle(
            $image, 
            0, 
            0, 
            Label::IMAGE_WIDTH[$size], 
            Label::IMAGE_HEIGHT[$size], 
            $color_white
        );

        
        // draw border rectangle 
        imagerectangle(
            $image,
            1,
            1,
            Label::IMAGE_WIDTH[$size] - 2, 
            Label::IMAGE_HEIGHT[$size] - 2, 
            $color_black
        );

        /*
            GdImage $image,
            float $size,
            float $angle,
            int $x,
            int $y,
            int $color,
            string $font_filename,
            string $text,
            array $options = []*
        */

        // draw package slip rectangle
        imagerectangle(
            $image,
            1,
            1,
            Label::IMAGE_WIDTH[$size] - 2, 
            1 + (Label::IMAGE_DPI * .6),
            $color_black
        );

        // create word packing slip
        imagettftext(
            $image,
            Label::IMAGE_DPI * .2,
            0,
            Label::IMAGE_DPI * .2,
            Label::IMAGE_DPI * .4,
            $color_black,
            $font_arial_bold,
            'Packing Slip'
        );


        // add overview information
        // draw overview rectangle
        imagerectangle(
            $image,
            1,
            1 + (Label::IMAGE_DPI * .6),
            Label::IMAGE_WIDTH[$size] - 2, 
            1 + (Label::IMAGE_DPI * 1.7),
            $color_black
        );

        $overview_lines = [];
        $shipment = Shipment::find($this->shipment_id);
        if (isset($shipment)) {
            $overview_lines[] = 'Order Ref: ' . $shipment->reference;
            $to_address = Address::find($shipment->to_address_id);
            if (isset($to_address)) {
                $overview_lines[] = 'Name: ' . $to_address->name;
                $overview_lines[] = 'Address:';
                $overview_lines[] = '   ' . $to_address->formattedStreet();
                $overview_lines[] = '   ' . $to_address->formattedCityStatePostal();
            }
        }
        
        $this->writeImageLines(
            $image,
            Label::IMAGE_DPI * .1,
            Label::IMAGE_DPI * .2,
            Label::IMAGE_DPI * .9,
            $color_black, 
            $font_arial_bold,
            $overview_lines, 
            40
        );

        // add the items
        $lines = [
            'Product Not Specified'
        ];

       

        if (isset($this->order_group_id)) {
            $order_products = OrderProduct::join('orders', 'order_products.order_id', '=', 'orders.id')
                ->where('orders.order_group_id', '=', $this->order_group_id)
                ->select('order_products.*')->get();

            if (count($order_products) > 0) {
                $lines = [''];
                foreach ($order_products as $order_product) { 
                    $lines[] = $order_product->quantity . ': - ' . $order_product->sku . ': ' . $order_product->name;
                }
            }
        }

        //($image, $fontsize, $x, $y, $color, $font, $lines) {
        $this->writeImageLines(
            $image,
            Label::IMAGE_DPI * .1,
            Label::IMAGE_DPI * .2,
            Label::IMAGE_DPI * 1.9,
            $color_black, 
            $font_arial_bold,
            $lines, 
            40
        );
        
        $image_data = null;
        if ($file_type == Label::FILE_TYPE_PNG) {

            $image_stream = fopen('php://memory','r+');
            imagepng($image, $image_stream);
            rewind($image_stream);
            $image_data = stream_get_contents($image_stream);
        }
        else if ($file_type == Label::FILE_TYPE_JPG) {

            $image_stream = fopen('php://memory','r+');
            imagejpeg($image, $image_stream);
            rewind($image_stream);
            $image_data = stream_get_contents($image_stream);
        }
        else if ($file_type == Label::FILE_TYPE_ZPL) {
            // if type zpl convert to zpl file
            $decoder = ZplGdDecoder::fromResource($image);
            $zpl_image = new ZplImage($decoder);

            $zpl = new ZplBuilder();
            $zpl->fo(0, 0)->gf($zpl_image)->fs();
            $image_data = $zpl->toZpl();
        }

        // save object to s3 storage
        $s3_client->putObject(array(
            'Bucket'            => env('AWS_BUCKET'),
            'Key'               => $image_path,
            'Body'              => $image_data
        ));
    }

    /**purpose
     *   generate image
     * args
     *   size
     *   image_path
     * returns
     *   (none)
     */
    private function generateImage($s3_client, $size, $image_path, $file_type) {

        // create image with dimensions
        $image = imagecreatetruecolor(Label::IMAGE_WIDTH[$size], Label::IMAGE_HEIGHT[$size]);
        imageresolution($image, 100);

        // create colors
        $color_white = imagecolorallocate($image, 255, 255, 255);
        $color_black = imagecolorallocate($image, 0, 0, 0);
        $font_arial = __DIR__ . '/Fonts/Arial.ttf';
        $font_arial_bold = __DIR__ . '/Fonts/Arial-Bold.ttf';

        // make full image white
        imagefilledrectangle(
            $image, 
            0, 
            0, 
            Label::IMAGE_WIDTH[$size], 
            Label::IMAGE_HEIGHT[$size], 
            $color_white
        );

        // draw border rectangle 
        imagerectangle(
            $image,
            1,
            1,
            Label::IMAGE_WIDTH[$size] - 2, 
            Label::IMAGE_HEIGHT[$size] - 2, 
            $color_black
        );

        /**** Service Letter */
        // draw service letter rectangle
        imagerectangle(
            $image,
            2,
            2,
            2 + Label::IMAGE_DPI,
            2 + Label::IMAGE_DPI,
            $color_black
        );

        if ($this->service == 'Parcel Select') {
            // make full image white
            imagefilledrectangle(
                $image,
                2,
                2,
                2 + Label::IMAGE_DPI,
                2 + Label::IMAGE_DPI,
                $color_black
            );
        }
        else {
            // draw service letter
            imagettftext(
                $image,
                Label::IMAGE_DPI * .75,
                0,
                2 + (Label::IMAGE_DPI * .15),
                2 + (Label::IMAGE_DPI * .875),
                $color_black,
                $font_arial_bold,
                Label::IMAGE_SERVICE_LETTER[$this->service]
            );
        }

        /**** Postage Paid */
        // draw postage paid box
        imagerectangle(
            $image,
            2 + Label::IMAGE_DPI,
            2,
            Label::IMAGE_WIDTH[$size] - 3,
            2 + Label::IMAGE_DPI,
            $color_black
        );
        imagerectangle(
            $image,
            Label::IMAGE_DPI * 1.75,
            Label::IMAGE_DPI * .25,
            Label::IMAGE_DPI * 3.5,
            Label::IMAGE_DPI * .75,
            $color_black
        );
        imagettftext(
            $image,
            Label::IMAGE_DPI * .1,
            0,
            Label::IMAGE_DPI * 1.75 + 5,
            (Label::IMAGE_DPI * .25) + (Label::IMAGE_DPI * .14),
            $color_black,
            $font_arial_bold,
            'U.S. POSTAGE PAID'
        );
        $name = strtoupper(env('APP_NAME'));
        if (env('APP_ENV') != 'prod') $name = 'TEST LABEL - NOT VALID';
        imagettftext(
            $image,
            Label::IMAGE_DPI * .1,
            0,
            Label::IMAGE_DPI * 1.75 + 5,
            (Label::IMAGE_DPI * .25) + (Label::IMAGE_DPI * .3),
            $color_black,
            $font_arial_bold,
            $name
        );
        imagettftext(
            $image,
            Label::IMAGE_DPI * .1,
            0,
            Label::IMAGE_DPI * 1.75 + 5,
            (Label::IMAGE_DPI * .25) + (Label::IMAGE_DPI * .46),
            $color_black,
            $font_arial_bold,
            'eVS'
        );
        if ($this->service == 'Cubic') {
            imagettftext(
                $image,
                Label::IMAGE_DPI * .1,
                0,
                Label::IMAGE_DPI * 1.75 + 5,
                Label::IMAGE_DPI * .9,
                $color_black,
                $font_arial_bold,
                'CUBIC'
            );
        }

        /**** USPS Route */
        $this->drawRoute(
            $image,
            Label::IMAGE_DPI * .1,
            Label::IMAGE_DPI * 3.44,
            Label::IMAGE_DPI * 1.9,
            $color_black,
            $font_arial_bold,
            $this->route
        );
        
        /**** USPS RDC */
        $this->drawRDC(
            $image,
            Label::IMAGE_DPI * .1,
            Label::IMAGE_DPI * 3.5,
            Label::IMAGE_DPI * 1.7,
            $color_black,
            $font_arial_bold,
            $this->rdc
        );

        /**** Service Days Line */
        imagerectangle(
            $image,
            2,
            3 + Label::IMAGE_DPI,
            Label::IMAGE_WIDTH[$size] - 3,
            3 + (Label::IMAGE_DPI * 1.5),
            $color_black
        );
        imagettftext(
            $image,
            Label::IMAGE_DPI * .18,
            0,
            4 + (Label::IMAGE_DPI * .15),
            2 + (Label::IMAGE_DPI * 1.35),
            $color_black,
            $font_arial_bold,
            Label::IMAGE_SERVICE_BANNER_TEXT[$this->service]
        );

        /***Address segments */
        imagerectangle(
            $image,
            2,
            4 + (Label::IMAGE_DPI * 1.5),
            Label::IMAGE_WIDTH[$size] - 3,
            4 + (Label::IMAGE_DPI * 3.5),
            $color_black
        );

        // from address portion
        $from_address = isset($this->return_address_id) ? Address::find($this->return_address_id) : Address::find($this->from_address_id);
        $from_lines = [];
        if (trim($from_address->company) != '') $from_lines[] = $from_address->company;
        //if (trim($from_address->phone) != '' && $this->service == 'Priority Express') $from_lines[] = $from_address->phone;
        $from_lines[] = $from_address->street_1 . ' ' . $from_address->street_2;
        $from_lines[] = $from_address->city . ' ' . $from_address->state . ' ' . $from_address->postal;

        // express and signature service not added
        if ($this->service == 'Priority Express') {
            $found_signature = false;
            $rate_services = Dynamo\Service::findOrCreate($this->rate_id);
            if (isset($rate_services->services)) {
                foreach($rate_services->services as $service) {
                    if (in_array($service["service"], [
                        Dynamo\Service::SERVICE_SIGNATURE, 
                        Dynamo\Service::SERVICE_ADULT_SIGNATURE
                    ])) {
                        $found_signature = true;
                    }
                }
            }
        
            $from_lines[] = '';
            if (!$found_signature) {
                $from_lines[] = 'WAIVER OF SIGNATURE REQUESTED';
            }
            else {
                $from_lines[] = 'SIGNATURE REQUIRED';
            }
        }

        $from_address_margin = Label::IMAGE_DPI * .125;
        $this->writeImageLines(
            $image,
            Label::IMAGE_DPI * .08,
            $from_address_margin + 2,
            4 + (Label::IMAGE_DPI * 1.5) + (Label::IMAGE_DPI * .08) + $from_address_margin,
            $color_black, 
            $font_arial_bold,
            $from_lines
        );

        // to address portion
        $shipment = Shipment::find($this->shipment_id);
        $to_address = Address::find($shipment->to_address_id);
        $to_lines = [];
        if (trim($to_address->name) != '') $to_lines[] = $to_address->name;
        if (trim($to_address->company) != '') $to_lines[] = $to_address->company;
        /*if (trim($to_address->phone) != '') $to_lines[] = $to_address->phone;*/
        $to_lines[] = $to_address->street_1 . ' ' . $to_address->street_2;
        $to_lines[] = $to_address->city . ' ' . $to_address->state . ' ' . $to_address->postal;
        if (trim($shipment->reference) != '') $to_lines[] = 'Ref: ' . $shipment->reference;
  
        $to_address_margin = Label::IMAGE_DPI * .5;
        $this->writeImageLines(
            $image,
            Label::IMAGE_DPI * .1,
            $to_address_margin + 2,
            4 + (Label::IMAGE_DPI * 2.6) + $from_address_margin,
            $color_black, 
            $font_arial_bold,
            $to_lines
        );

        /****Barcode Segment */
        $image_barcode = imagecreatefromjpeg($this->url);
        $image_barcode_cropped = $this->trimImageWhiteSpace($image_barcode);

        $source_x = 0;
        $source_y = 0;
        $source_width = imagesx($image_barcode_cropped);
        $source_height = imagesy($image_barcode_cropped);

        // adjustment for priority express
        if ($this->service == 'Priority Express') {
            $image_barcode_cropped = imagerotate(
                $image_barcode_cropped,
                90,
                $color_white
            );

            $source_x = 5;
            $source_y = 745;
            $source_width = imagesx($image_barcode_cropped)-15;
            $source_height = 355;
        }

        if ($size == Label::IMAGE_SIZE_4X5) 
        {
            imagecopyresized(
                $image,
                $image_barcode_cropped,
                2,
                2 + (Label::IMAGE_DPI * 3.5),
                $source_x,
                $source_y,
                Label::IMAGE_DPI * 4 - 4,
                Label::IMAGE_DPI * 1.5,
                $source_width,
                $source_height
            ); 
        }
        else {

            imagecopyresized(
                $image,
                $image_barcode_cropped,
                2,
                2 + (Label::IMAGE_DPI * 3.5),
                $source_x,
                $source_y,
                Label::IMAGE_DPI * 4 - 4,
                Label::IMAGE_DPI * 2,
                $source_width,
                $source_height
            ); 
        }

        imagerectangle(
            $image,
            2,
            2,
            Label::IMAGE_WIDTH[$size] - 3, 
            Label::IMAGE_HEIGHT[$size] - 3, 
            $color_black
        );
        
        $image_data = null;
        if ($file_type == Label::FILE_TYPE_PNG) {

            $image_stream = fopen('php://memory','r+');
            imagepng($image, $image_stream);
            rewind($image_stream);
            $image_data = stream_get_contents($image_stream);
        }
        else if ($file_type == Label::FILE_TYPE_JPG) {

            $image_stream = fopen('php://memory','r+');
            imagejpeg($image, $image_stream);
            rewind($image_stream);
            $image_data = stream_get_contents($image_stream);
        }
        else if ($file_type == Label::FILE_TYPE_ZPL) {
            // if type zpl convert to zpl file
            $decoder = ZplGdDecoder::fromResource($image);
            $zpl_image = new ZplImage($decoder);

            $zpl = new ZplBuilder();
            $zpl->fo(0, 0)->gf($zpl_image)->fs();
            $image_data = $zpl->toZpl();
        }

        // save object to s3 storage
        $s3_client->putObject(array(
            'Bucket'            => env('AWS_BUCKET'),
            'Key'               => $image_path,
            'Body'              => $image_data,
            'ContentEncoding'   => 'image/jpg'
        ));

    }

    private function drawRoute($image, $fontsize, $x, $y, $color, $font, $route) {
        imagerectangle(
            $image,
            $x,
            $y,
            $x + 85, 
            $y + $fontsize + 20, 
            $color
        );
        imagerectangle(
            $image,
            $x + 1,
            $y + 1,
            $x + 85 - 1, 
            $y + $fontsize + 20 - 1, 
            $color
        );
        imagettftext(
            $image,
            $fontsize,
            0,
            $x + 10,
            $y + $fontsize + 10,
            $color,
            $font,
            $route
        );
    }

    private function drawRDC($image, $fontsize, $x, $y, $color, $font, $rdc) {
        imagettftext(
            $image,
            $fontsize,
            0,
            $x,
            $y + $fontsize,
            $color,
            $font,
            $rdc
        );
    }

    private function writeImageLines($image, $fontsize, $x, $y, $color, $font, $lines, $line_break_length = null) {
    
        // lines
        $lines_broken = [];

        // loop through and break lines if they are set to a max length;
        foreach ($lines as $line) {

            // current line 
            $current_line = $line;

            // line break length
            $line_number = 0;
            if (isset($line_break_length)) {
                while (strlen($current_line) > $line_break_length) {
                    $split_line = $line_number == 0 ? substr($current_line, 0, $line_break_length) : ('    ' . substr($current_line, 0, $line_break_length - 4));
                    $offset = $line_number == 0 ? 0 : 4;
                    $current_line = substr($current_line, $line_break_length - $offset, strlen($current_line) - $line_break_length + $offset);
                    $line_number++;
                    $lines_broken[] = $split_line;
                }
            }
            
            // add current line to lines broken
            $lines_broken[] = $line_number == 0 ? $current_line : ('    ' . $current_line);
        }
        
        // lines broken 
        for ($i = 0; $i < count($lines_broken); $i++)
        {
            imagettftext(
                $image,
                $fontsize,
                0,
                $x,
                $y + ($fontsize * $i) + (10 * $i),
                $color,
                $font,
                strtoupper($lines_broken[$i])
            );
        }

    }

    private function trimImageWhiteSpace($image) {

        //find the size of the borders
        $b_top = 0;
        $b_btm = 0;
        $b_lft = 0;
        $b_rt = 0;

        //top
        for(; $b_top < imagesy($image); ++$b_top) {
            for($x = 0; $x < imagesx($image); ++$x) {
                if(imagecolorat($image, $x, $b_top) != 0xFFFFFF) {
                break 2; //out of the 'top' loop
                }
            }
        }

        //bottom
        for(; $b_btm < imagesy($image); ++$b_btm) {
            for($x = 0; $x < imagesx($image); ++$x) {
                if(imagecolorat($image, $x, imagesy($image) - $b_btm-1) != 0xFFFFFF) {
                break 2; //out of the 'bottom' loop
                }
            }
        }

        //left
        for(; $b_lft < imagesx($image); ++$b_lft) {
            for($y = 0; $y < imagesy($image); ++$y) {
                if(imagecolorat($image, $b_lft, $y) != 0xFFFFFF) {
                break 2; //out of the 'left' loop
                }
            }
        }

        //right
        for(; $b_rt < imagesx($image); ++$b_rt) {
            for($y = 0; $y < imagesy($image); ++$y) {
                if(imagecolorat($image, imagesx($image) - $b_rt-1, $y) != 0xFFFFFF) {
                break 2; //out of the 'right' loop
                }
            }
        }

        //copy the contents, excluding the border
        $newimage = imagecreatetruecolor(
            imagesx($image)-($b_lft+$b_rt) - 2, imagesy($image)-($b_top+$b_btm));

        imagecopy($newimage, $image, 0, 0, $b_lft, $b_top, imagesx($newimage), imagesy($newimage));

        return $newimage;
    }

    public function addCorrection($amount, $service, $weight, $length, $width, $height, $reference_id = null) {

    }

    public function addTracking($tracking, $reference_id = null) {

    }
    
    public function addUsedCancellation($reference_id = null) {

    }

    public function addUsedReturn($reference_id = null) {

    }

}