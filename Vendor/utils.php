<?php
// vim: set ts=4 sts=4 sw=4 si noet:

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
