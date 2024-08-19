<?php
namespace App\Common;

class Functions {

	public static function startsWith($haystack, $needle)
	{
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}

    public static function convertTimeToMysql($time)
	{
		return date("Y-m-d H:i:s", $time);
	}


	public static function isEmpty($string)
	{
		if (!isset($string)) return true;
		if (trim($string) == '') return true;
		return false;
	}
    
	/**purpose
	 *   get a random id
	 * args
	 *   length
	 * returns
	 *   random id that is the size of length
	 */
    public static function getRandomID($length = 32)
	{
		return substr(str_shuffle(str_repeat($x='0123456789abcdef', ceil($length/strlen($x)) )),1,$length);
	}

	/**purpose
	 *   get a unique id
	 * args
	 *   (none)
	 * returns
	 *   uuid
	 */
	public static function getUUID() {   
		$uuid = array(
			'time_low'  => 0,
			'time_mid'  => 0,
			'time_hi'  => 0,
			'clock_seq_hi' => 0,
			'clock_seq_low' => 0,
			'node'   => array()
		);
		   
		$uuid['time_low'] = mt_rand(0, 0xffff) + (mt_rand(0, 0xffff) << 16);
		$uuid['time_mid'] = mt_rand(0, 0xffff);
		$uuid['time_hi'] = (4 << 12) | (mt_rand(0, 0x1000));
		$uuid['clock_seq_hi'] = (1 << 7) | (mt_rand(0, 128));
		$uuid['clock_seq_low'] = mt_rand(0, 255);

		for ($i = 0; $i < 6; $i++) {
			$uuid['node'][$i] = mt_rand(0, 255);
		}

		$uuid = sprintf('%08x-%04x-%04x-%02x%02x-%02x%02x%02x%02x%02x%02x',
			$uuid['time_low'],
			$uuid['time_mid'],
			$uuid['time_hi'],
			$uuid['clock_seq_hi'],
			$uuid['clock_seq_low'],
			$uuid['node'][0],
			$uuid['node'][1],
			$uuid['node'][2],
			$uuid['node'][3],
			$uuid['node'][4],
			$uuid['node'][5]
		);

		return $uuid;
	}


	/**purpose
	 *   get host
	 * args
	 *   (none)
	 * returns
	 *   host
	 */
	public static function getHost() {
		return (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'];
	}

	/**purpose
	 *   read a csv
	 * args
	 *   handle
	 * returns
	 *   objects
	 */
	public static function readCsv($csv) {
		
        $csv_models = [];
        ini_set('memory_limit','1G');

		$rows = explode("\n", $csv);
		if ($rows <= 1) return [];

		$headers = str_getcsv($rows[0]);  

		foreach ($rows as $key => $row) {
			if ($key == 0 || trim($row) == '') continue;
			$data = str_getcsv($row);
			$model = [];
			for ($i = 0; $i < count($headers); $i++)
			{
				$model[trim($headers[$i])] = isset($data[$i]) ? $data[$i] : '';
			}
			$csv_models[] = $model;
		}

		return $csv_models;
	}

}

?>