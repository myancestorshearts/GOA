<?php 

namespace App\Integrations;

use App\Common\Validator;

class Integration {	

	// store instance generated at constructor
	private $store_instance;

	// types
	const TYPE_SHOPIFY = 'SHOPIFY';
	const TYPE_ETSY = 'ETSY';
	const TYPE_WIX = 'WIX';
	const TYPE_SQUARE= 'SQUARE';
	const TYPE_WOOCOMMERCE = 'WOOCOMMERCE';
	const TYPE_SHIPHERO = 'SHIPHERO';

	/**purpose 
	 *   instantiate the integration class
	 * args
	 *   type (SHOPIFY)
	 * returns
	 *   integration instance
	 */
	function __construct($type) {
		
		switch(strtoupper($type)) {
            case Integration::TYPE_SHOPIFY:
                $this->store_instance = new Shopify;
				break;
			case Integration::TYPE_ETSY: 
				$this->store_instance = new Etsy;
				break;
			case Integration::TYPE_WIX: 
				$this->store_instance = new Wix;
				break;
			case Integration::TYPE_SQUARE: 
				$this->store_instance = new Square;
				break;
			case Integration::TYPE_WOOCOMMERCE:
				$this->store_instance = new WooCommerce;
				break;
			case Integration::TYPE_SHIPHERO:
				$this->store_instance = new ShipHero;
				break;
            default:
        }
	}

	/**purpose
	 *   check type
	 * args
	 *   type
	 * returns
	 *   result (true/false)
	 */
	public static function validateType($type) {
		return Validator::validateEnum($type, ['enums' => [
			Integration::TYPE_SHOPIFY,
			Integration::TYPE_ETSY,
			Integration::TYPE_WIX,
			Integration::TYPE_SQUARE,
			Integration::TYPE_WOOCOMMERCE,
			Integration::TYPE_SHIPHERO
		]]);
	}

	/**purpose
	 *   connect an integration
	 * args
	 *   request
	 * returns
	 *   (none)
	 */
	function connect($request) {
		return $this->store_instance->connect($request);
	}

	/**purpose
	 *   install a integration
	 * args
	 *   request
	 * returns
	 *   (link);
	 */
	function install($request) {
		return $this->store_instance->install($request);
	}

	/**purpose
	 *   confirm connection
	 * args
	 *   user_id
	 *   request
	 * returns
	 *   (none)
	 */
	function confirmConnection($state, $request) {
		return $this->store_instance->confirmConnection($state, $request);
	}
	
	/**purpose
	 *   sync orders
	 * args
	 *   (none)
	 * returns
	 *   (none)
	 */
	function syncOrders($integration) {
		set_time_limit(3000);
		return $this->store_instance->syncOrders($integration);
	}

	/**purpose
	 *   mark order completed in store
	 * args
	 *   order
	 * returns
	 *   (none)
	 */
	function completeOrder($integration, $order, $label) {
		return $this->store_instance->completeOrder($integration, $order, $label);
	}

	/**purpose
	 *   download an integration file
	 * args
	 *   $integration
	 * returns
	 *   downloaded file
	 */
	function download($integration) {
		return $this->store_instance->download($integration);
	}

	

	/**purpose
	 *   purchase a label
	 * args
	 *   $integration
	 *   $request
	 * returns
	 *   label info for integration
	 */
	function purchase($integration, $request) {
		return $this->store_instance->purchase($integration, $request);
	}
}