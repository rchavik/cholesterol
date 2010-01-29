<?php
// vim: set ts=4 sts=4 sw=4 si noet:

if (!function_exists('object_to_array')):

function object_to_array($var) {
	$result = array();
	$references = array();

	// loop over elements/properties
	foreach ($var as $key => $value) {
		// recursively convert objects
		if (is_object($value) || is_array($value)) {
			// but prevent cycles
			if (!in_array($value, $references)) {
				$result[$key] = object_to_array($value);
				$references[] = $value;
			}
		} else {
			// simple values are untouched
			$result[$key] = $value;
		}
	}
	return $result;
}

endif;

function array_key_value($key, $array, $emptyValue = 0) {
	return array_key_exists($key, $array) ? $array[$key] : $emptyValue;
}

function _json_encode($array) {
	$buffer = json_encode($array);
	$buffer = str_replace('\r\n', '', $buffer);
	$buffer = str_replace('\n', '', $buffer);
	$buffer = str_replace('\r', '', $buffer);
	$buffer = str_replace('\t', '', $buffer);
	$buffer = str_replace('\"', '"', $buffer);
	$buffer = str_replace('"<script>', '', $buffer);
	$buffer = str_replace('<\/script>"', '', $buffer);
	return $buffer;
}

?>
