<?php

namespace App\Models\Dynamo;

use Kitar\Dynamodb\Model\Model;

use Kitar\Dynamodb\Connection;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws;
use Aws\DynamoDb\Marshaler;

use Exception;

use App\Http\Controllers\Response;

class Base extends Model
{
    
    // set table
	protected $table;
    // set primary key
	protected $primaryKey = 'PK';
    
    // should be hidden from responses
    protected $hidden = [
        'PK',
        'SK'
    ];

    /**purpose
     *   construct a model based on dynamo
     * args
     *   (none)
     * returns
     *   model called
     */
    public function __construct() {
		parent::__construct();
		$this->setConnection('dynamo');
	}

    /**purpose
     *   get the table name
     * args
     *   (none)
     * returns
     *   get master table name
     */
    public static function getTableName() {
		$class = get_called_class();
		$temp_model = new $class;
        return $temp_model->table;
    }

    /**purpose
     *   get application key
     * args
     *   (none)
     * returns
     *   application key
     */
    public static function getApplicationKey() {
        return env('APP_GOAKEY');
    }

    /**purpose
     *   get connection
     * args
     *   (none)
     * returns
     *   connection
     */
    public static function connect() {
        return new Connection([
            'region' => env('AWS_DEFAULT_REGION'),
            'access_key' => env('AWS_ACCESS_KEY_ID'),
            'secret_key' => env('AWS_SECRET_ACCESS_KEY')
        ]);
    }

    /**purpose
     *   get aws connection
     * args
     *   (none)
     * returns
     *   connection
     */
    public static function awsConnect() {
        $sdk = new Aws\Sdk([
            'region' => env('AWS_DEFAULT_REGION'),
            'access_key' => env('AWS_ACCESS_KEY_ID'),
            'secret_key' => env('AWS_SECRET_ACCESS_KEY'),
            'version'  => 'latest'
        ]);

        return $sdk->createDynamoDb();
    }
    

    /**purpose
     *   basic find (pk and sk)
     * args
     *   sk
     * returns
     *   model
     */
    public static function findSk($sk) {
        
        // connect and get model
        $connection = get_called_class()::connect();

        // get application key
        $application_key = get_called_class()::getApplicationKey();

        // get response from dynamo
		$response_model = $connection->table(get_called_class()::getTableName())->getItem(['PK' => $application_key, 'SK' => (string) $sk]);

        // if no item is found return null
        if (!isset($response_model['Item'])) return null;

        // iterate through propertiers and set them on model
        $class = get_called_class();
        $model = new $class;
        foreach ($response_model['Item'] as $key => $value) {
            $model->{$key} = $value;
        }
        
        // return populated model
        return $model;
    }

    /**purpose
     *   basic find (pk and sk) or create
     * args
     *   sk
     * returns
     *   model
     */
    public static function findOrCreate($sk) {

        // get class
        $class = get_called_class();
        
        // find existing 
        $model = $class::findSk($sk);

        // no existing then we need to create
        if (!isset($model)) $model = $class::create($sk);

        // return model
        return $model;
    }

    /**purpose
     *   create a new instance
     * args
     *   (none)
     * returns
     *   model
     */
    public static function create($sk, $save = true) {

        // get class
        $class = get_called_class();

        // create new instance
        $model = new $class;
        $model->PK = $class::getApplicationKey();
        $model->SK = (string) $sk;

        // save model 
        if ($save) $model->save();

        return $model;
    }

    /**purpose
     *   find by query
     * args
     *   query_args
     *   index
     * returns
     *   model
     */
    public static function findByQuery($conditions, $index = null) {
        
        // connect and get model
        $connection = get_called_class()::connect();

        // generate query 
        $query = $connection->table(get_called_class()::getTableName());

        // check for index
        if (isset($index)) $query->index($index);

        // add key conditions
        foreach ($conditions as $condition) {
            $query->keyCondition($condition[0], $condition[1], $condition[2]);
        }

        // add limit
        $query->limit(1);

        // run query
        try {
            $response_model = $query->query();
        }
        catch (Exception $ex) {
            return null;
        }

        // if no item is found return null
        if (!isset($response_model['Items'])) return null;
    
        // get first item
        $item = $response_model['Items'][0];
    
        // iterate through propertiers and set them on model
        $class = get_called_class();
        $model = new $class;
        foreach ($item as $key => $value) {
            $model->{$key} = $value;
        }
        
        // return populated model
        return $model;
    }
    
    /**purpose
     *   override save
     * args
     *   (none)
     * returns
     *   result
     */
    public function save($options = []) {
        try {
            return parent::save($options);
        }
        catch (DynamoDbException $ex) {
            return false;
        }
    }

    /**purpose
     *   update item
     * args
     *   (none)
     * returns
     *   result
     */
    public function updateItem() {
        
        // connect and get model
        $connection = get_called_class()::connect();

        // gather updates
        $updates = [];
        foreach($this->attributes as $key => $value) {
            if (in_array($key, ['PK', 'SK', 'created_at', 'updated_at'])) continue;
            $updates[$key] = $value;
        }

        // call updates
        try {
            $connection->table(get_called_class()::getTableName())->key([
                'PK' => $this->PK,
                'SK' => $this->SK
            ])->updateItem($updates);
            return true;
        }
        catch (DynamoDbException $ex) {
            return false;
        }
    }

    /**purpose
     *   insert batch
     * args
     *   (models) 
     * returns
     *   result
     */
    public static function insertBatch($models) {
        
        // connect and get model
        $connection = Base::awsConnect();
        
        $models_to_add = [];
        $result = true;
        foreach ($models as $model) {
            $models_to_add[] = $model;
            if (count($models_to_add) == 25) {
                Response::addTimer('Submitting to dyanmo', $models_to_add);
                $result = $result && Base::insertModels($connection, $models_to_add);
                $models_to_add = [];
            }
        }
        if (count($models_to_add) > 0) {
            
            Response::addTimer('Submitting to dyanmo final', $models_to_add);
            $result = $result && Base::insertModels($connection, $models_to_add);
        }
        return $result;
    }

    private static function insertModels($connection, $models) {

        $marshaler = new Marshaler;

        $models_by_table = [];
        foreach($models as $model) {
            if (!isset($models_by_table[$model->table])) $models_by_table[$model->table] = [];

            // create insert_model
            $insert_model = [];
            foreach($model->attributes as $key => $value) {
                if (in_array($key, ['created_at', 'updated_at'])) continue;
                $insert_model[$key] = $value;
            }

            $models_by_table[$model->table][] = ['PutRequest' => ['Item' => $marshaler->marshalItem($insert_model)]];
        }


        // call inserts
        try {
            $result = $connection->batchWriteItem([
                'RequestItems' => $models_by_table
            ]);
            return true;
        }
        catch (DynamoDbException $ex) {
            return false;
        }
    }
}