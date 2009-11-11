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

	function _mergeSearchConditions(&$conditions, $needFields) {
		$ignoreList = array('ext', 'url', '_search', 'nd', 'page', 'rows', 'sidx', 'sord');

		$url = $this->controller->params['url'];
		foreach ($url as $key => $val) {
			if (in_array($key, $ignoreList))  {
				continue;
			}

			// XXX: convert back _ to . when appropriate
			// TODO: check against $needFields
			if (strstr($key, '_')) {
				$newkey = preg_replace('/_/', '.', $key, 1);
			}
			$conditions[$newkey . ' like'] = $val . '%';
		}
	}

	/** Export grid data to excel (CSV) */
	function exportToExcel($rows, $options = array()) {
		$options += array(
			'fields' => array(),
			'export_headers' => array(),
			'filename' => 'report.csv'
			);
		extract($options);
		$download_filename = $filename;
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment; filename='. urlencode($download_filename));
		header("Content-Transfer-Encoding: binary\n");

		$rowLen = count($rows);
		$fieldLen = count($fields);
		$hasHeaders = false;

		if (array_key_exists('export_headers', $options) && !empty($options['export_headers'])) {
			$hasHeaders = true;
		}

		// construct list of column headers and display it accordingly
		for ($i = 0; $i < $fieldLen; $i++) {
			$dict = explode('.', $fields[$i]);
			$fieldList[] = $dict;
			if ($hasHeaders) {
				echo $options['export_headers'][$i] . ',';
			} else {
				echo $dict[1] . ',';
			}
		}
		echo "\r\n";

		for ($i = 0; $i < $rowLen; $i++) {
			$row = $rows[$i];
			for ($j = 0; $j < $fieldLen; $j++) {
				$dict =& $fieldList[$j];
				echo $row[$dict[0]][$dict[1]] . ',';
			}
			echo "\r\n";
		}
	}

	function find($modelName, $conditions = array(), $fields = array(), $order = null, $recursive = -1) {

		if (is_array($conditions) && array_key_exists('conditions', $conditions)) {
			extract($conditions);
		}

		$controller =& $this->controller;
		$url = $controller->params['url'];

		App::import('Vendor', 'Cholesterol.utils');
		$page = array_key_value('page', $url);
		$rows = array_key_value('rows', $url);
		$sidx = array_key_value('sidx', $url);
		$sord = array_key_value('sord', $url);
		$_search = array_key_value('_search', $url);
		$exportToExcel = array_key_value('exportToExcel', $url);

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

		$model = ClassRegistry::init($modelName);
		$model->recursive = $recursive;

		if (!empty($fields)) {
			// user has specified wanted fields, so use it.
			$needFields = $this->_extractFields($fields);
		} else {
			// fallback using model schema fields
			$needFields = array($modelName => array_keys($model->_schema));
		}

		if ($_search == 'true') {
			$this->_mergeSearchConditions($conditions, $needFields);
		}

		$count = $model->find('count', $conditions);

		if (strcmp($exportToExcel, 'true') == 0) {
			$page = 1;
			$limit = 65535;
			$this->controller->autoRender = false;
		}

		$findOptions = array_merge(array('conditions' => $conditions), array(
			'page' => $page,
			'limit' => $limit,
			'order' => $field_order
			)
		);

		$rows = $model->find('all', $findOptions);

		if (strcmp($exportToExcel, 'true') == 0) {
			return $this->exportToExcel($rows, array(
				'fields' => $fields,
				'export_headers' => $export_headers,
				'filename' => $export_filename
			));
		}
		
		$total_pages = $count > 0 ? ceil($count/$limit) : 0;

		$response->page = $page;
		$response->records = count($rows);
		$response->total =  $total_pages;

		for ($i = 0; !empty($rows) && $i < count($rows); $i++) {
			$row =& $rows[$i];

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
