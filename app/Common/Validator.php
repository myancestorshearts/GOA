<?php
namespace App\Common;

class Validator {

//////////////////////////
	//// input validation ////
	//////////////////////////

	private static $VALIDATION_TYPE_TO_FUNCTION = [
	    'TEXT' => 'validateText',
	    'TEXTAREA' => 'validateText',
	    'EMAIL' => 'validateEmail',
	    'PHONE' => 'validatePhone',
	    'BOOLEAN' => 'validateBoolean',
	    'FLOAT' => 'validateFloat',
	    'INTEGER' => 'validateInteger',
	    'DATE' => 'validateDate',
	    'ENUM' => 'validateEnum',
	    'DATA' => 'validateData'
	];

	// purpose
	//   validate a property value
	// args
	//   model property
	//   value
	// returns
	//   filteredValue (null if not valid)
	public static function validatePropertyValue($value, $property)
	{
		if (!isset($property['type'])) throw new Exception('Invalid property type');
		if (!isset(Validator::$VALIDATION_TYPE_TO_FUNCTION[$property['type']])) throw new Exception('Invalid property type - ' . $property['type']);
		return Validator::{Validator::$VALIDATION_TYPE_TO_FUNCTION[$property['type']]}($value, $property);

	}

	// purpose
	//   validate a data
	// args
	//   value
	// returns
	//   filtered_value
	public static function validateData($value, $property)
	{
		$decoded_value = json_decode($value);
		if (!isset($decoded_value)) return null;
		if (!json_last_error() == JSON_ERROR_NONE) return null;
		return $value;
	}

	// purpose
	//   validate a enum
	// args
	//   value
	// returns
	//   filtered_value
	public static function validateEnum($value, $property)
	{
		if (!isset($property['enums'])) throw new Exception('Type enum requires enums');
		if (!in_array($value, $property['enums'])) return null;
		return $value;
	}

	// purpose
	//   validate a date
	// args
	//   value
	// returns
	//   filtered_value
	public static function validateDate($value, $property)
	{
		$value = Validator::convertStringToMysql($value);
		return $value;
	}

	// purpose
	//   validate an int
	// args
	//   value
	// returns
	//   filtered_value
	public static function validateInteger($value, $property)
	{
		$value = (int) $value;
		if (isset($property['min']) && $value < $property['min']) return null;
		if (isset($property['max']) && $value > $property['max']) return null;
		return $value;
	}

	// purpose
	//   validate a float
	// args
	//   value
	// returns
	//   filtered_value
	public static function validateFloat($value, $property)
	{
		$value = (float) $value;
		if (isset($property['min']) && $value < $property['min']) return null;
		if (isset($property['max']) && $value > $property['max']) return null;
		return $value;
	}

	// purpose
	//   validate a text value
	// args
	//   value
	// returns
	//   filtered_value
	public static function validateText($value, $property = null)
	{
		if (isset($property['lowercase']) && $property['lowercase']) $value = strtolower($value);
		if (isset($property['trim']) && $property['trim']) $value = trim($value);
		if (isset($property['clearable']) && !$property['clearable'] && trim($value) == '') return null;
		return $value;
	}

	// purpose
	//   validate a bool value
	// args
	//   value
	// returns
	//   filtered_value
	public static function validateBoolean($value, $property = null)
	{
		return filter_var($value, FILTER_VALIDATE_BOOLEAN);
	}

	// purpose
	//   validate an email
	// args
	//   value
	// returns
	//   email if it is valid - (null if valid is not valid)
	public static function validateEmail($value, $property = [])
	{
		$validated_value = strtolower(trim($value));
		if ((isset($property['clearable']) && $property['clearable']) && $validated_value == '') return '';
		return (filter_var($validated_value, FILTER_VALIDATE_EMAIL)) ? $validated_value : null;
	}

	// purpose
	//   validate a phone number
	// args
	//   value
	// returns
	//   filtered value that is the phone number - (null if value is not valid)
	public static function validatePhone($value, $property = [])
	{
		$filtered_value = trim($value);
		if ((isset($property['clearable']) && $property['clearable']) && $filtered_value == '') return '';
		
		// phone numbers should not have anything but digits
		$filtered_value = preg_replace('/\D/', '', $filtered_value);

		// phone numbers should have exactly 10 digtis
		if (strlen($filtered_value) != 10 && strlen($filtered_value) != 11) return null;
		return $filtered_value;
	}

	// purpose
	//   validate a zip code
	// args
	//   value
	// returns
	//   filtered value that is the phone number - (null if value is not valid)
	public static function validateState($value, $property = null, $country = 'US')
	{
		$filtered_value = trim(strtoupper($value));
		$state_abbreviations = [
			'US' => ['AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY', 'GU', 'PR', 'VI', 'DC', 'PR', 'AA', 'AE', 'AP', 'AS', 'GU', 'MP', 'FM', 'MH', 'UM'],
			'ES' => ['AC', 'AA', 'AB', 'AN', 'AM', 'AS', 'AV', 'BD', 'BL', 'BR', 'BU', 'CC', 'CD', 'CN', 'CS', 'CE', 'CR', 'CO', 'CU', 'GN', 'GD', 'GJ', 'GP', 'HL', 'HS', 'JA', 'LR', 'LP', 'LN', 'LD', 'LG', 'MD', 'MG', 'ME', 'MR', 'NV', 'OR', 'PL', 'PV', 'SL', 'SC', 'SG', 'SV', 'SR', 'TG', 'TE', 'TD', 'VN', 'VD', 'VZ', 'ZM', 'ZG'],
			'PT' => ['AV', 'AC', 'BE', 'BR', 'BA', 'CB', 'CO', 'EV', 'FA', 'GU', 'LE', 'LI', 'MA', 'PA', 'PO', 'SA', 'SE', 'VC', 'VR', 'VI'],
			'CA' => ['AB', 'BC', 'MB', 'NB', 'NL', 'NT', 'NS', 'ON', 'PE', 'QC', 'SK', 'YT'],
			'UK' => ["ABD", "AGY", "ANS", "ANT", "ARL", "ARM", "AYR", "BAN", "BDF", "BRK", "BEW", "BRI", "BKM", "CAI", "CAM", "CHE", "LND", "CLK", "CWD", "CON", "CMA", "DBY", "DEV", "DOR", "DOW", "DUM", "DFS", "DND", "DUR", "DFD", "ELN", "ERY", "SXE", "EDI", "ESS", "FER", "FIF", "GLS", "GRE", "GTM", "GNT", "GWN", "HAM", "HEF", "HRT", "INV", "IOW", "KEN", "KCD", "LKS", "LAN", "LEI", "LIN", "LDY", "IOM", "MSY", "MLN", "MGM", "MOR", "NAI", "NFK", "NTH", "NBL", "NYK", "NTT", "OKI", "OXF", "PER", "POW", "RFW", "RAC", "ROX", "RUT", "SHI", "SAL", "SOM", "SGM", "SYK", "STS", "KKD", "SAF", "SFK", "SRY", "SUT", "TWE", "TAW", "TYR", "WAR", "WIS", "WGM", "WLN", "WMD", "SXW", "WYK", "WIG", "WIL", "WOR"]
		];

		if (!in_array($filtered_value, isset($state_abbreviations[$country]) ? $state_abbreviations[$country] : [])) return null;

		return $filtered_value;
	}

	// purpose
	//   validate a country
	// args
	//   value
	// returns
	//   filtered value that is the phone number - (null if value is not valid)
	public static function validateCountry($value, $property = null)
	{
		$filtered_value = trim(strtoupper($value));
		$country_abbreviations = ['US', 'PT', 'ES', 'CA', 'UK'];
		if (!in_array($filtered_value, $country_abbreviations)) return null;
		return $filtered_value;
	}

	// purpose
	//   validate a postal code
	// args
	//   value
	// returns
	//   filtered value that is the phone number - (null if value is not valid)
	public static function validatePostalCode($value, $property = null, $country = 'US')
	{
		$filtered_value = trim(strtoupper($value));
		switch($country) {
			case 'US':
			case 'ES':
				$filtered_value = preg_replace('/\D/', '', $filtered_value);
				if (strlen($filtered_value) == 9) {
					$filtered_value = substr($filtered_value, 0, 5);
				}
				if (strlen($filtered_value) != 5) return null;
				break;
			case 'PT':
				$filtered_value = preg_replace('/\D/', '', $filtered_value);
				if (strlen($filtered_value) != 7) return null;
				break;
			case 'CA':
				$filtered_value = trim(preg_replace('/[^\dA-Z\s]/', '', $filtered_value));
				if (strlen($filtered_value) != 7) return null;
				break;
			case 'UK':
				$filtered_value = trim(preg_replace('/[^\d0-9A-Z\s]/', '', strtoupper($filtered_value)));
				$split = explode(' ', $filtered_value);
				if (count($split) != 2) return null;
				if (strlen($split[0]) < 2 || strlen($split[0]) > 4) return null;
				if (strlen($split[1]) != 3) return null;
				if ($split[0][0] < 'A' || $split[0][0] > 'Z') return null;
				if ($split[1][0] < '0' || $split[1][0] > '9') return null;
				break;
		}
		return $filtered_value;
	}

	
	// purpose
	//   validate a url
	// args
	//   value
	// returns
	//   filtered_value
	public static function validateUrl($value, $property = null)
	{
		$value = trim($value);
		if (!Functions::startsWith($value, 'https://') && 
			!Functions::startsWith($value, 'http://')) return null;
		return $value;
	}

}

?>
