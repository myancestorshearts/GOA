<?php

namespace App\Models\Mysql;

use Auth;
use App\Http\Controllers\Response;


class IntegrationFailedOrder extends Base
{
    public $table = 'integration_failed_orders';
    
    // sets sub user so they have their own system for theses
    public CONST SUB_USER_ALLOW = false;

    public static $search_parameters = [
        [
            'argument' => 'query',
            'columns' => ['reference'],
            'type' => 'SEARCH'
        ],
        [
            'argument' => 'resolved',
            'column' => 'resolved', 
            'type' => 'EQUAL',
            'default' => 0
        ],
        [
            'argument' => 'active',
            'column' => 'active',
            'type' => 'EQUAL',
            'default' => 1
        ]
    ];

    
    /**purpose
     *   override default search method
     */
    public static function search($models_query, $request) {

        $models_query->join('integrations', 'integration_failed_orders.integration_id', '=', 'integrations.id')
            ->selectRaw('integration_failed_orders.*');

        return parent::search($models_query, $request);
    }


    public $integration;
    public $model_pairs = [
        ['integration', 'integration_id', Integration::class]
    ];

    public function deactivate() {
        $this->active = 0;
        $this->save();
        $response = new Response;
        return $response->setSuccess('Deactivated');
    }


}
