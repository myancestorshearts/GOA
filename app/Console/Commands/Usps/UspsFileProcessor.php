<?php
namespace App\Console\Commands\Usps;

use Illuminate\Console\Command;

use App\Models\Mysql\GlobalFile;
use App\Models\Mysql\WalletTransaction;
use App\Models\Mysql\Label;
use App\Models\Mysql\LabelCorrection;
use Aws\S3\S3Client;

use App\Common\Functions;


class UspsFileProcessor extends Command
{

    const SERVICE_MAP = [
        'FC' => 'First Class',
        'PS' => 'Parcel Select',
        'PM' => 'Priority',
        'EX' => 'Priority Express'
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'usps:fileprocessor';


    private $s3_client;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'processes files from usps and triggers callbacks';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        // upload to s3
        $this->s3_client = S3Client::factory(array(
            'credentials' => array(
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY')
            ),
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
        ));

        // process ppc pn mi
        $this->processUspsPpcPnMi();
        
        // process ppc ca mi
        $this->processUspsPpcCaMi();
    }


    private function processUspsPpcPnMi() {

        $global_files = GlobalFile::on('goa')->where([
            ['type', '=', 'USPS_PPC_PN_MI'],
            ['processed', '=', 0]
        ])->get();

        $index = 0;
        $count = count($global_files);

        $total_profit = 0;

        foreach($global_files as $global_file) {
            echo ++$index . '/' . $count . ' Processing ' . $global_file->id . ' - ' . $global_file->filename;

            $result = $this->s3_client->getObject(array(
                'Bucket'            => 'goasolutions-files',
                'Key'               => 'usps/' . $global_file->filename
            ));
            $models = Functions::readCsv((string) $result['Body']);

            foreach ($models as $model) {
                $transactions = WalletTransaction::on('goa')->join('labels', 'wallet_transactions.label_id', 'labels.id')->where('labels.tracking', '=', $model['IMPB'])->select('wallet_transactions.*')->get();

                if (count($transactions) != 1) {

                    echo " no transaction ";
                    //dd ($model);
                    continue;
                }

                $transaction = $transactions->first();

                $profit = (-1 * $transaction->amount) - $model['Total Postage'];
                $transaction->cost = $model['Total Postage'];
                $transaction->profit = $profit;
                $transaction->profit_calculated = 1;
                $transaction->save();

                $total_profit += $profit;

                echo ' - ' . $profit . ' - '.  $total_profit;
            }

            $global_file->processed = 1;
            $global_file->save();

            echo "\n";
        }

    }


    private function processUspsPpcCaMi() {
        $global_files = GlobalFile::on('goa')->where([
            ['type', '=', 'USPS_PPC_CA_MI'],
            ['processed', '=', 0]
        ])->get();

        $index = 0;
        $count = count($global_files);

		$total_charged_back = 0;


        foreach($global_files as $global_file) {
            echo ++$index . '/' . $count . ' Processing ' . $global_file->id . ' - ' . $global_file->filename;

            $result = $this->s3_client->getObject(array(
                'Bucket'            => 'goasolutions-files',
                'Key'               => 'usps/' . $global_file->filename
            ));
            $models = Functions::readCsv((string) $result['Body']);


            $row_index = 0;
			foreach ($models as $model) {
                ++$row_index;
                $reference_id = $global_file->id . '-' . $row_index;

				$total_charged_back += $model['Assessed Postage'] - $model['Claimed Postage'];

                $label = Label::on('goa')->where('tracking', '=', $model['Impb'])->get()->first();

                if (!isset($label)) {
                    echo "\nTest";
                    dd ($model);
                    continue;
                }

                // create price correction
                $price_correction = LabelCorrection::on('goa')->where('reference_id', '=', $reference_id)->get()->first();

                if (!isset($price_correction)) {
                    echo ' - could not find creating a new one';
                    $price_correction = new LabelCorrection;
                    $price_correction->setConnection('goa');
                    $price_correction->reference_id = $reference_id;
                    $price_correction->label_id = $label->id;
                    $price_correction->user_id = $label->user_id;
                    $price_correction->amount = $model['Assessed Postage'] - $model['Claimed Postage'];
                    $price_correction->external_user_id = $label->external_user_id;
                    $price_correction->width = $model['Scan Width'];
                    $price_correction->length = $model['Scan Length'];
                    $price_correction->height = $model['Scan Height'];
                    $price_correction->weight = trim($model['Scan Weight']) != '' ? ($model['Scan Weight'] * 16) : 0;
                    $price_correction->carrier = 'usps';
                    if (!isset(UspsFileProcessor::SERVICE_MAP[$model['Scan Mail Class']])) {
                        echo "\nTest 2";
                        dd($model);
                    }
                    $price_correction->service = UspsFileProcessor::SERVICE_MAP[$model['Scan Mail Class']];
                    $price_correction->raw = json_encode($model);
                }

                $price_correction->created_at = Functions::convertTimeToMysql(strtotime($model['First Scan Date']));
                $price_correction->save();

				echo ' - ' . $total_charged_back;
			}
            
            $global_file->processed = 1;
            $global_file->save();

            echo "\n";
        }
    }
}