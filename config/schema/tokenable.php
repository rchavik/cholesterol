<?php
// vim: set ts=4 sts=4 sw=4 si noet:

/**
 * This is Tokenable Schema file
 *
 * Use it to configure database for Tokenable Behavior
 *
 */

/*
 *
 * Using the Schema command line utility
 * cake schema create -plugin cholesterol tokenable
 *
 */
class TokenableSchema extends CakeSchema {

	var $name = 'Tokenable';

	function before($event = array()) {
		return true;
	}

	function after($event = array()) {
	}

	var $tokens = array(
		'id' => array('type' => 'string', 'length' => 36, 'null' => false, 'key' => 'primary'),
		'model' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 50),
		'foreign_key' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
		'token' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 50),
		'created' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'un_tokens' => array('column' => 'token', 'unique' => 1),
			'un_tokens_foreign' => array('column' => array('foreign_key', 'model'), 'unique' => 1),
		)
	);

}
?>