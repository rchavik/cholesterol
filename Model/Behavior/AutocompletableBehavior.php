<?php

// vim: set ts=4 sts=4 sw=4 si noet:

class AutocompletableBehavior extends ModelBehavior {

	function autocomplete(&$model, $q, $limit = 20, $extraFields = null) {
		$conditions = array(
			$model->name . '.title like' => $q . '%',
		);

		$this->recursive = -1;
		if ($extraFields) {
			$conditions += array(
				$extraFields['field'] => $extraFields['value']
			);
		}
		$result = $model->find('all', array(
			'fields' => array('id', 'title'),
			'conditions' => $conditions,
			)
		);
		return Set::extract('{n}.' . $model->name, $result);
	}

}
