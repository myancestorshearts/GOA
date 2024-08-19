<?php
namespace App\Models\Mysql;

use Illuminate\Database\Eloquent\Model;
use App\Common\Functions;
use App\Common\Validator;

use App\Http\Controllers\Response;

use Formatter;
use ApiAuth;

class Base extends Model {

    // enabled uuid as primary key
    protected $keyType = 'string';
    public $incrementing = false;

	// links to the database table. Should be named the same as the table in database
    public $table = '';
    public static $properties = [];
    public static $search_parameters = [];
    public static $method_sets = [];

    // sets sub user so they have their own system for theses
    public CONST SUB_USER_ALLOW = true;
    public CONST SUB_USER_BLOCK = false;
    public CONST PERSONAL_ONLY = false;

    // class map to specify which class to manipulate through the api
	static $CLASS_MAP = [
		'user' => User::class,
		'wallettransaction' => WalletTransaction::class,
        'apikey' => ApiKey::class,
        'label' => Label::class,
        'shipment' => Shipment::class,
        'address' => Address::class,
        'parcel' => Parcel::class,
        'rate' => Rate::class,
        'ordergroup' => OrderGroup::class,
        'order' => Order::class,
        'orderproduct' => OrderProduct::class,
        'address' => Address::class,
        'package' => Package::class,
        'referral' => Referral::class,
        'scanform' => ScanForm::class,
        'integrationfailedorder' => IntegrationFailedOrder::class,
        'integration' => Integration::class,
        'apicallback' => ApiCallback::class,
        'pickup' => Pickup::class,
        'labelcorrection' => LabelCorrection::class
	];

    // override default input validator and check inputs myself
 	protected function validateInputs($request)
    {
        return true;
    }

    // generic add for models
    public function add($request)
    {
        $validate_result = $this->validateInputs($request);
        if ($validate_result !== true) return $validate_result;
        $this->save();
        return true;
    }

    // generic set for models
    public function set($request)
    {
        $response = new Response;
        $validate_result = $this->validateInputs($request);
        if ($validate_result !== true) return $validate_result;
        $this->save();
        return $response->setSuccess();
    }

    // generic search method
    public static function search($models_query, $request) {

        $class = get_called_class();
        $temp_model = new $class;
        $models_query->select($temp_model->table . '.*')
            ->groupBy($temp_model->table . '.id');

        return true;
    }

    
    // generic admin search method
    public static function adminSearch($models_query, $request) {
        $class = get_called_class();
        $temp_model = new $class;
        $models_query->select($temp_model->table . '.*');

        return true;
    }


	// get class from class key
	public static function getClassFromClassKey($class_key) {
		if (isset(Base::$CLASS_MAP[$class_key])) return Base::$CLASS_MAP[$class_key];
		return null;
	}


    // method to help build models that stack on each other but keep number of queries low
    // uses the model pairs to get all the models
    public $model_pairs = [];
    public function getModelWherePairs()
    {
        return [];
    }

    public function getModel($request = null) {
        $class = get_class($this);
        return $class::getModels([$this], $request)[0];
    }

    public static function getModels($collection, $request = null, $ignore_classes = [])
    {
        $new_ignore_classes = $ignore_classes;
        $new_ignore_classes[] = get_called_class();
        $include_classes = [];

        // get include classes from request
        if (isset($request))
        {
            $exploded_include_classes = explode(',', $request->get('include_classes', ''));
            foreach ($exploded_include_classes as $include_class) {
                $include_class = Base::getClassFromClassKey($include_class);
                if (isset($include_class)) $include_classes[] = $include_class;
            }
        }

        $models = [];
        if (count($collection) > 0)
        {
            $ids = [];
            $model_pairs = $collection[0]->model_pairs;
            $model_pairs_where = $collection[0]->model_pairs_where;

            // for find
            foreach ($model_pairs as $model_pair) {
                $identifier = $model_pair[1];
                $ids[$model_pair[0]] = [];
                foreach ($collection as $element) {
                    if ($element->$identifier != null) $ids[$model_pair[0]][] = $element->$identifier; 
                }
            }

            $sub_collections = [];
            $sub_models_mapped = [];

            // for find
            foreach ($model_pairs as $model_pair) {  
                $class = $model_pair[2];
                if (in_array($class, $ignore_classes)) continue;
                if (!in_array($class, $include_classes)) continue;
                $class_model = new $class;
                $sub_collections[$model_pair[0]] = $class::whereIn($class_model->primaryKey, $ids[$model_pair[0]]);
                $new_ignore_classes = $ignore_classes;
                $new_ignore_classes[] = $class;
                $sub_models_mapped[$model_pair[0]] = $class::getModelsMapped($sub_collections[$model_pair[0]]->get(), $request, $new_ignore_classes);
            }

            // for where
            foreach ($collection as $element) {
                foreach ($element->getModelWherePairs() as $model_pair_where) {  
                    $class = $model_pair_where[2];
                    if (in_array($class, $ignore_classes) && (!isset($model_pair_where[3]) || !$model_pair_where[3])) continue;
                    if (!in_array($class, $include_classes)) continue;
                    $class_model = new $class;
                    if (!isset($sub_collections[$model_pair_where[0]])) $sub_collections[$model_pair_where[0]] = $class::where($model_pair_where[1]);
                    else $sub_collections[$model_pair_where[0]]->orWhere($model_pair_where[1]);
                }
            }

            foreach ($collection[0]->getModelWherePairs() as $model_pair_where) {  
                $class = $model_pair_where[2];
                $new_ignore_classes = $ignore_classes;
                $new_ignore_classes[] = $class;
                if (isset($sub_collections[$model_pair_where[0]])) $sub_models_mapped[$model_pair_where[0]] = $class::getModelsMapped($sub_collections[$model_pair_where[0]]->get(), $request, $new_ignore_classes);
            }

            foreach ($collection as $element) {
                foreach ($model_pairs as $model_pair) {    
                    $identifier = $model_pair[1];
                    if (isset($sub_models_mapped[$model_pair[0]][$element->$identifier])) $element->setSubModel($model_pair[0], $sub_models_mapped[$model_pair[0]][$element->$identifier]);
                }
                foreach ($element->getModelWherePairs() as $model_pair_where) {  
                    if (isset($sub_models_mapped[$model_pair_where[0]]))
                    {
                        $sub_elements = array_filter($sub_models_mapped[$model_pair_where[0]], function($mapped_model) use ($model_pair_where)
                        {
                            foreach ($model_pair_where[1] as $where_arg) {
                                $identifier = $where_arg[0];
                                if ($where_arg[1] == '=' && $mapped_model->$identifier != $where_arg[2])
                                {
                                    return false;
                                } 
                            }
                            return true;
                        });
                        $element->setSubModel($model_pair_where[0], array_values($sub_elements));
                    }
                }
                $models[] = $element;
            }

        }

		// get user
		$user = ApiAuth::user();
        if (Validator::validateBoolean($user->admin)) {
            foreach ($models as $model) {
                $model->hidden = [];
            }
        }

        return $models;
    }

    protected function serializeDate(\DateTimeInterface $date) {
        return (Formatter::convertToDateFormat($date));
    }

    public static function getModelsMapped($collection = [], $request = null, $ignore_classes = [])
    {
        $mapped_models = [];

        $class = get_called_class();
        $model = new $class;
        $primary_id = $model->primaryKey;
        $models = $class::getModels($collection, $request, $ignore_classes);
        foreach ($models as $model) {
            $mapped_models[$model->$primary_id] = $model;
        }
        return $mapped_models;
    }

    protected $added_attributes = [];
    public function populateSubModelWhere($sub_model_property, $where_pairs, $class)
    {   
        if (!isset($this->$sub_model_property)) 
        {
            $this->$sub_model_property = $class::where($where_pairs)->get();
            if (isset($this->$sub_model_property)) 
            {
                $this->attributes[$sub_model_property] = $class::getModels($this->$sub_model_property, [[get_called_class()]]);
                $this->added_attributes[] = $sub_model_property;
            }
        }
    }
    public function populateSubModel($sub_model_property, $sub_id_property, $class)
    {   
        if (!isset($this->$sub_model_property)) 
        {
            $this->$sub_model_property = $class::find($this->$sub_id_property);
            if (isset($this->$sub_model_property))
            { 
                $this->attributes[$sub_model_property] = $this->$sub_model_property->model; 
                $this->added_attributes[] = $sub_model_property;
            }
        }
    }

    public function save(array $options = array())
    {
        $temp_attributes = [];
        foreach ($this->added_attributes as $added_attribute) {
            $temp_attributes[$added_attribute] = $this->attributes[$added_attribute];
            unset($this->attributes[$added_attribute]);
        }
        foreach ($this->attributes as $key => $value) {
            if ($value === 'NULL') $this->$key = null;
        }

        if (!isset($this->id)) $this->id = Functions::getUUID();
        
        parent::save($options);
        foreach ($this->added_attributes as $added_attribute) {
            $this->attributes[$added_attribute] = $temp_attributes[$added_attribute];
        }
    }


    public function setSubModel($sub_model_property, $sub_model)
    {
        if (!isset($this->$sub_model_property))
        {
            $this->$sub_model_property = $sub_model;
            $this->attributes[$sub_model_property] = $sub_model; 
            $this->added_attributes[] = $sub_model_property;
        }
        else 
        {
            $this->$sub_model_property = $sub_model;
            $this->attributes[$sub_model_property] = $sub_model; 
        }
    }

    
	/**purpose
	 *   apply filters to search query)
	 * args
	 *   request
	 *   $class
	 *   $models_query (reference)
	 * returns
	 *   search_applied
	 */
    public static function applyFilters($request, $class, &$models_query, $admin = false)
    {
        // iterate through the search parameters and apoly search to models_query
        foreach ($class::$search_parameters as $search_parameter) {

        	// check to see if argument exists in the request otherwise continue
            if (!$request->has($search_parameter['argument']) && !isset($search_parameter['default'])) continue;

            // get argument search parameter
            $argument = $request->get($search_parameter['argument'], isset($search_parameter['default']) ? $search_parameter['default'] : null);
            if ($argument === 'NULL') $argument = null;

            // create temp model to get table
            $temp_model = new $class;
            $get_column_path = function ($path) use ($temp_model) {
                if (strpos($path, '.') === false) {
                    return $temp_model->table . '.' . $path;
                } 
                else return $path;
            };

            // depending on the type filter they models by argument
            switch ($search_parameter['type'])
            {
                case 'EQUAL':
                    $models_query->where($get_column_path($search_parameter['column']), '=', $argument);
                    break;
                case 'SEARCH':
                	// check if there is sequal injection possibility here ---------------------------- **********************
                    $where_raw = [];
                    foreach (explode(' ', $argument) as $query) {
                        $columnQueries = [];
                        foreach ($search_parameter['columns'] as $column) $columnQueries[] = $get_column_path($column) . " LIKE \"%$query%\"";
                        $where_raw[] = '(' . implode(' OR ', $columnQueries) . ')';
                    }
                    $models_query->whereRaw(implode(' AND ', $where_raw));
                    break;
                	// check if there is sequal injection possibility here --------------------------- **********************
                case 'IN':
                    $models_query->whereIn($get_column_path($search_parameter['column']), explode(',', $argument));
                    break;
                case 'NOTIN': 
                    $models_query->whereNotIn($get_column_path($search_parameter['column']), explode(',', $argument));
                    break;
                case 'GREATER':
                    $models_query->where($get_column_path($search_parameter['column']), '>=', $argument);
                    break;
                case 'LESSER':
                    $models_query->where($get_column_path($search_parameter['column']), '<=', $argument);
                    break;
                default:
                    return 'Invalid type set in model configuration - ' . $search_parameter['type'];
            }
        }
        return ($admin) ? $class::adminSearch($models_query, $request) : $class::search($models_query, $request);
    }


    /**purpose
     *   apply order by 
     * args
     *   request
     *   class
     *   models query (by ref)
     * returns
     *   order by applied
     */
    public static function applyOrderBy($request, $class, &$models_query)
    {
        $temp_model = new $class;

        $order_bys = json_decode($request->get('order_by', json_encode([
            [
                'column' => 'created_at',
                'direction' => 'DESC'
            ]
        ])));

        $get_column_path = function ($path) use ($temp_model) {
            if (strpos($path, '.') === false) {
                return $temp_model->table . '.' . $path;
            } 
            else return $path;
        };

        foreach ($order_bys as $key => $order_by) {

            if (!isset($order_by->direction) || !isset($order_by->column)) continue;
            $models_query->orderBy($get_column_path($order_by->column), $order_by->direction);
            $models_query->addSelect($get_column_path($order_by->column));
        }
    }

    

    public function deactivate() {
        $response = new Response;
        return $response->setFailure('Deactivate not implemented on model');
    }

    public function hasPermissionSet() { 
        return false;
    }

}
