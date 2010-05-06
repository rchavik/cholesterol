<?php
// vim: set ts=4 sts=4 sw=4 si noet:

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


	/** Enable Tokenable Behavior
	 */
	function enableTokenable(&$model, $enable = null) {
		if ($enable !== null) {
			$this->settings[$model->alias]['enabled'] = $enable;
		}
		return $this->settings[$model->alias]['enabled'];
	}
	
	/** Disable Behavior
	 *  In a controller, call this model's method to disable Tokenable behavior
	 *  eg: $Account->disableTokenable()
	 */
	function disableTokenable(&$model) {
		return $this->enableTokenable($model, false);
	}

	function beforeSave(&$Model) {
		if (!$this->__settings[$Model->alias]['enabled']) {
			return false;
		}

		if ($Model->id) {
			return true;
		}

		$tokenField = $this->__settings[$Model->alias]['tokenField'];
		if (!$Model->hasField($this->__settings[$Model->alias]['tokenField'])) {
			trigger_error('Missing column: `' . $tokenField . '` in Model ' .  $Model->alias,  E_USER_ERROR );
			return false;
		}

		$this->Token =& ClassRegistry::init('Cholesterol.Token');
		$len = $this->__settings[$Model->alias]['tokenLength'];

		for ($i = 0; $i < 10; $i++) {
			$token = $this->__uniqid($len);
			if ($this->__isValidToken($Model, $token)) {
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
		$tokens = $this->Token->find('first', array(
			'conditions' => array(
				'Token.token' => $token,
				),
			)
		);
		return empty($tokens);
	}

	function __uniqid($len) {
		return substr(uniqid(), -$len);
	}
}
