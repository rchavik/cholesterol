<?php
// vim: set ts=4 sts=4 sw=4 si noet:

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

function array_key_value($key, $array, $emptyValue = 0) {
	return array_key_exists($key, $array) ? $array[$key] : $emptyValue;
}

?>
