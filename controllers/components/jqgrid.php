<?php
// vim: set ts=4 sts=4 sw=4 si noet:

/** Component to assist querying and generating JSON result set when working
 *  with jqGrid
 *
 *  @author Rachman Chavik
 *  @license MIT
 */
class JqgridComponent extends Object {

	var $controller;

	function initialize(&$controller) {
		$this->controller = $controller;
	}

	function _extractFields($fields) {
		for ($i = 0; $i < count($fields); $i++) {
			$arr = explode('.', $fields[$i]);
			$res[$arr[0]][] = $arr[1];
		}
		return $res;
	}

	function find($modelName, $conditions = array(), $fields = array(), $order = null, $recursive = -1) {

		$controller =& $this->controller;
		$url = $controller->params['url'];

		App::import('Vendor', 'Cholesterol.utils');
		$page = array_key_value('page', $url);
		$rows = array_key_value('rows', $url);
		$sidx = array_key_value('sidx', $url);
		$sord = array_key_value('sord', $url);

		$limit = $rows == 0 ? 10 : $rows;
		$start = $limit * $page - $limit;

		if (empty($order)) {
			if (!empty($sidx)) {
				$field_order = $sidx . ' ' . $sord;
			} else {
				$field_order = null;
			}
		} else {
			$field_order = $order;
		}

		if (!array_key_exists('conditions', $conditions)) {
			$conditions = array('conditions' => $conditions);
		}

		$model = ClassRegistry::init($modelName);
		$model->recursive = $recursive;
		$count = $model->find('count', $conditions);

		$findOptions = array_merge($conditions, array(
			'page' => $page,
			'limit' => $limit,
			'order' => $field_order
			)
		);

		$rows = $model->find('all', $findOptions);
		
		$total_pages = $count > 0 ? ceil($count/$limit) : 0;

		$response->page = $page;
		$response->records = count($rows);
		$response->total =  $total_pages;

		for ($i = 0; !empty($rows) && $i < count($rows); $i++) {
			$row =& $rows[$i];

			if (!empty($fields)) {
				// user has specified wanted fields, so use it.
				$needFields = $this->_extractFields($fields);
			} else {
				// fallback using model schema fields
				$needFields = array($modelName => array_keys($model->_schema));
			}

			foreach ($needFields as $gridModel => $gridFields) {

				for ($j = 0; $j < count($gridFields); $j++) {
					$gridField = $gridFields[$j];
					// XXX: assume that an 'id' field exist
					if ($gridField == 'id') {
						$response->rows[$i]['id'] = $row[$gridModel][$gridField];
					}
					if (array_key_exists($gridModel, $row) &&
					    array_key_exists($gridField, $row[$gridModel])) {

						$fieldName = $gridModel . '.' . $gridField;
						$response->rows[$i][$fieldName] = $row[$gridModel][$gridField];
					}
				}
			}
		}

		$res = object_to_array($response);
		$this->controller->set(compact('res'));
		$this->controller->set('json', 'res');
	}
}

?>
