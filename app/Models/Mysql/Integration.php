<?php

namespace App\Models\Mysql;

use Auth;
use App\Integrations;
use App\Http\Controllers\Response;
use App\Common\Functions;

class Integration extends Base
{
    public $table = 'integrations';

    public static $search_parameters = [
        [
            'argument' => 'active',
            'column' => 'active',
            'type' => 'EQUAL',
            'default' => 1
        ]
    ];

    /**purpose
     *   sync orders for integration
     * args
     *   user
     * returns 
     *   (none)
     */
    public function syncOrders() {

		// create integration instance
		$integration = new Integrations\Integration($this->store);
        
        // sync integrations
        $integration->syncOrders($this);

        // set refreshed at
        $this->refreshed_at = Functions::convertTimeToMysql(time());
        $this->save();
    }

    
	/**purpose
     *   create a failed order sync
     * args
     *   user
     *   order
     *   error message
     * returns
     *   (none)
     */
    public function addFailedOrder($reference, $error_message) {

        // check to see if we already have logged the error
        if (IntegrationFailedOrder::where([
            ['user_id', '=', $this->user_id],
            ['integration_id', '=', $this->id],
            ['reference', '=', $reference]
        ])->count() > 0) return;

        // create integration failed job
        $integration_failed_order = new IntegrationFailedOrder;
        $integration_failed_order->user_id = $this->user_id;
        $integration_failed_order->integration_id = $this->id;
        $integration_failed_order->reference = $reference;
        $integration_failed_order->error_message = $error_message;
        $integration_failed_order->resolved = 0;
        $integration_failed_order->active = 1;
        $integration_failed_order->save();
    }

    /**purpose
     *   if an order is in a failed state resolve that failed order
     * args
     *   reference
     * returns
     *   (none)
     */
    public function resolveFailedOrder($reference) {

        // check to see if we already have logged the error
        $failed_order = IntegrationFailedOrder::where([
            ['user_id', '=', $this->user_id],
            ['integration_id', '=', $this->id],
            ['reference', '=', $reference]
        ])->limit(1)->get()->first();

        if (isset($failed_order)) {
            $failed_order->resolved = 1;
            $failed_order->save();
        }
    }

    /**purpose
     *   mark an order fulfilled in the integration
     * args
     *   order
     *   label
     * returns
     *   (none)
     */
    public function completeOrder($order, $label) {
        
		// create integration instance
		$integration = new Integrations\Integration($this->store);

        // complete order
        $integration->completeOrder($this, $order, $label);
    }

	/**purpose
	 *   convert weight to oz
	 * args
	 *   weight
	 *   type
	 * returns
	 *   weight in oz
	 */
	public static function convertWeightToOz($weight, $unit) {
		$ratios = [
			'oz' => 1,
			'lb' => .0625,
			'g' => 28.3495,
			'kg' => .0283495
		];

		return round($weight / $ratios[$unit], 2);
	}

}
