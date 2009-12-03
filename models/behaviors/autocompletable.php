<?php

// vim: set ts=4 sts=4 sw=4 si noet:

class AutocompletableBehavior extends ModelBehavior {

	function autocomplete(&$model, $q, $limit = 20, $extraFields = null) {
		$conditions = array(
			$model->name . '.title like' => $q . '%',
		);

		if ($extraFields) {
			$conditions += array(
				$extraFields['field'] => $extraFields['value']
			);
			$contain = $extraFields['field'];
			$this->recursive = 0;
		} else {
			$contain = null;
			$this->recursive = -1;
		}
		$result = $model->find('all', array(
			'contain' => $contain,
			'fields' => array('id', 'title'),
			'conditions' => $conditions,
			)
		);
		return Set::extract('{n}.' . $model->name, $result);
	}

}
