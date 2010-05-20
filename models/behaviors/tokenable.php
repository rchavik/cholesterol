<?php
// vim: set ts=4 sts=4 sw=4 si noet:

/** Creates a unique token for any row.
 *
 *  Requires a field called 'token' to be available in the parent Model (or
 *  customize it via 'tokenField' setting.
 *
 *  Other implementations for a similar functionality:
 *  http://teknoid.wordpress.com/2009/09/19/build-a-url-shortener-for-your-app/
 *
 *  It always seems to boil down to choosing the right keywords, doesn't it?
 *  - TehTreag
 */
class TokenableBehavior extends ModelBehavior {

	var $__settings = array();

	function setup(&$Model, $settings = array()) {

		if (!isset($this->__settings[$Model->alias])) {
			$this->__settings[$Model->alias] = array(
				'enabled' => true,
				'foreignKey' => 'id',
				'tokenField' => 'token',
				'tokenLength' => 5,
				'maxIterations' => 10,
				);
		}

		$this->__settings[$Model->alias] = Set::merge($this->__settings[$Model->alias], $settings);
	}


	function beforeSave(&$Model) {
		if (!$this->__settings[$Model->alias]['enabled']) {
			return false;
		}

		$tokenField = $this->__settings[$Model->alias]['tokenField'];
		if ($Model->id && isset($Model->data[$Model->alias][$tokenField]) && $Model->data[$Model->alias][$tokenField] != 'default') {
			return true;
		}

		if (!$Model->hasField($this->__settings[$Model->alias]['tokenField'])) {
			trigger_error('Missing column: `' . $tokenField . '` in Model ' .  $Model->alias,  E_USER_ERROR );
			return false;
		}

		$this->Token =& ClassRegistry::init('Cholesterol.Token');
		$len = $this->__settings[$Model->alias]['tokenLength'];

		for ($i = 0; $i < 10; $i++) {
			$token = $this->__uniqid($len);
			if ($this->__isValidToken($token)) {
				$Model->data[$Model->alias][$tokenField] = $token;
				return true;
			}
		}
		trigger_error('Cannot generate token after ' . $maxIterations . ' iterations');
		return false;
	}

	function afterSave(&$Model, $created) {
		$tokenField = $this->__settings[$Model->alias]['tokenField'];
		if ($created) {
			return $this->__saveToken($Model, $Model->data[$Model->alias][$tokenField]);
		}
		return true;
	}

	function __saveToken(&$Model, $token) {
		$token = $this->Token->create(array(
			'model' => $Model->alias,
			'foreign_key' =>  $Model->id,
			'token' => $token,
		));
		return $this->Token->save($token);
	}

	function __isValidToken($token) {
		$count = $this->Token->find('count', array(
			'conditions' => array(
				'Token.token' => $token,
				),
			)
		);
		return 0 == $count;
	}

	function __uniqid($len) {
		return substr(uniqid(), -$len);
	}
}
