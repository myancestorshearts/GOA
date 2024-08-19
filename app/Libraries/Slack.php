<?php 

namespace App\Libraries;

use GuzzleHttp\Client;

class Slack {

	private static $channels = [
		'#development' => 'https://hooks.slack.com/services/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',
		'#billing' => 'https://hooks.slack.com/services/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'
	];

	public static function pushMessage($message, $channel = null)
	{
		$send_channel = (!isset($channel) || empty(static::$channels[$channel])) ? static::$channels['#development'] : static::$channels[$channel];

		$client = new Client;
		$response = $client->post(
			$send_channel, 
			[
				'json' => ['text' => $message],
				'exceptions' => false
			]);

		$code = $response->getStatusCode();
		if ($code == 200) return true;
		return false;
	}
}