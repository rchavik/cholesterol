<?php
// vim: set ts=4 sts=4 sw=4 si noet:

App::import('Vendor', 'Cholesterol.utils');

/** Component to assist querying and generating JSON result set when working
 *  with jqGrid
 *
 *  @author Rachman Chavik
 *  @license MIT
 */
class JqgridComponent extends Component {

	public $controller;

	static $mapOpers = array(
			'eq' => '',
			'ne' => ' <>',
			'lt' => ' <',
			'le' => ' <=',
			'gt' => ' >',
			'ge' => ' >=',
			'bw' => ' LIKE',
			'bn' => ' NOT LIKE',
			'in' => '',
			'ni' => ' NOT',
			'ew' => ' LIKE',
			'en' => ' NOT LIKE',
			'cn' => ' LIKE',
			'nc' => ' NOT LIKE'
			);

	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);
	}
	
	public function initialize(Controller $controller) {
		$this->controller = $controller;
	}

	protected function _extractFields($fields) {
		for ($i = 0; $i < count($fields); $i++) {
			$arr = explode('.', $fields[$i]);
			$res[$arr[0]][] = $arr[1];
		}
		return $res;
	}

	/** construct $conditions array when using Filter Toolbar feature */
	protected function _mergeFilterConditions(&$conditions, $needFields, $filterMode) {
		$ignoreList = array('ext', 'url', '_search', 'nd', 'page', 'rows', 'sidx', 'sord', 'doExport', 'exportOptions', 'filterMode', 'filters', 'gridId',);

		$url = $this->controller->request->query;
		$i = 0;
		foreach ($url as $key => $val) {
			if ($i == 0) {
				$i++; continue;
			}
			if (in_array($key, $ignoreList))  {
				continue;
			}

			// XXX: convert back _ to . when appropriate
			// TODO: check against $needFields
			$newkey = $key;
			if (strstr($key, '_')) {
				$newkey = preg_replace('/_/', '.', $key, 1);
			}

			switch ($filterMode) {
			case 'exact':
				$conditions[$newkey] = $val;
				break;

			default:
				if (strpos($val, ' - ')) {
					$date = explode(' - ', $val);
					if (count($date) == 2) {
						$conditions[$newkey . ' BETWEEN ? AND ?'] = array($date[0], $date[1]);
					} else {
						$conditions[$newkey . ' like'] = '%' . $val . '%';
					}
				} else {
					$conditions[$newkey . ' like'] = '%' . $val . '%';
				}
				break;
			}
		}
	}

	/** construct $conditions array when using Advanced Search feature */
	protected function _mergeAdvSearchConditions(&$conditions, $needFields, $filters) {

		$rules = array();

		foreach ($filters->rules as $rule) {

			$op = JqgridComponent::$mapOpers[$rule->op];

			$data = $rule->data;
			switch ($rule->op) {
			case 'bn':
			case 'bw':
				$data = $data . '%';
				break;
			case 'ew':
			case 'en':
				$data = '%' . $data;
				break;
			case 'cn':
			case 'nc':
				$data = '%' . $data . '%';
				break;

			case 'ni':
				$data = strpos($data, ',') !== false ? explode(',', $data) : $data;
				$data = array_map('trim', $data);
				$op = is_string($data) ? ' <>' : $op;
				break;

			case 'in':
				$data = strpos($data, ',') !== false ? explode(',', $data) : $data;
				$data = array_map('trim', $data);
				$op = is_string($data) ? '' : $op;
				break;
			}

			$rules[]["{$rule->field}{$op}"] = $data;
		}

		$conditions[$filters->groupOp] =& $rules;
	}

	/** Export grid data to CSV */
	protected function _exportToCSV($modelName, $fields, $rows, $exportOptions) {
		$download_filename = $exportOptions['filename'];
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment; filename='. urlencode($download_filename));
		header("Content-Transfer-Encoding: binary\n");

		$rowLen = count($rows);
		$fieldLen = count($fields);
		$hasHeaders = !empty($exportOptions['headers']);

		// construct list of column headers and display it accordingly
		for ($i = 0; $i < $fieldLen; $i++) {
			$dict = explode('.', $fields[$i]);
			$fieldList[] = $dict;
			if ($hasHeaders) {
				echo $exportOptions['headers'][$i] . ',';
			} else {
				echo $dict[1] . ',';
			}
		}
		echo "\r\n";

		for ($i = 0; $i < $rowLen; $i++) {
			$row = $rows[$i];
			for ($j = 0; $j < $fieldLen; $j++) {
				$dict =& $fieldList[$j];
				if (isset($row[$dict[0]][$dict[1]])) {
					echo $row[$dict[0]][$dict[1]] . ',';
				} else {
					echo ',';
				}
			}
			echo "\r\n";
		}
		Configure::write('debug', 0);
	}

	protected function _exportToXls($modelName, $fields, $rows, $exportOptions) {
		if (!property_exists($this->controller, 'ExcelExporter')) {
			$this->log('Jqgrid requires ExcelExporter component');
		}

		$download_filename = $exportOptions['filename'];
		$columnHeaders = $exportOptions['columnHeaders'];
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment; filename='. urlencode($download_filename));
		header("Content-Transfer-Encoding: binary\n");

		$tempfile = tempnam('/tmp', 'CE2X');
		$this->controller->ExcelExporter->export($modelName, $rows, array(
			'fields' => $fields,
			'columnHeaders' => $columnHeaders,
			'output' => array(
				'file' => $tempfile,
				)
			)
		);
		Configure::write('debug', 0);
		readfile($tempfile);
		unlink($tempfile);
	}

	protected function _exportToFile($modelName, $fields, $rows, $exportOptions) {
		switch ($exportOptions['type']) {
		case 'csv':
			return $this->_exportToCSV($modelName, $fields, $rows, $exportOptions);
			break;
		case 'xls':
			return $this->_exportToXls($modelName, $fields, $rows, $exportOptions);
			break;
		default:
			$this->log('Unsupported export format');
			break;
		}
	}

	protected function _extractGetParams($url) {
		$page = array_key_value('page', $url);
		$rows = array_key_value('rows', $url);
		$sidx = array_key_value('sidx', $url);
		$sord = array_key_value('sord', $url);
		$_search = (boolean) array_key_value('_search', $url);
		$doExport = (boolean) array_key_value('doExport', $url);
		$filterMode = array_key_value('filterMode', $url);
		$gridId = urldecode(array_key_value('gridId', $url));
		$filters = urldecode(array_key_value('filters', $url));
		$filters = $filters == '' ? null : json_decode($filters);

		return compact('page', 'rows', 'sidx', 'sord', '_search',
			'filters', 'filterMode', 'gridId', 'doExport'
		);
	}

	protected function _getFieldOrder($sidx, $sord) {
		if (!empty($sidx)) {
			$field_order = $sidx . ' ' . $sord;
		} else {
			$field_order = null;
		}
		return $field_order;
	}

	public function find($modelName, $options = array()) {

		$options += Set::merge(array(
			'conditions' => array(),
			'recursive' => -1,
			'fields' => array()
			), $options);

		extract($options);
		if ($this->controller->request->isPost()) {
			extract($this->_extractGetParams($this->controller->request->params['form']));
		} else {
			$f = $this->_extractGetParams($this->controller->request->query);
			extract($f
			);
		}

		$exportOptions = json_decode(Cache::read('export_options_' . $gridId), true);

		$limit = $rows == 0 ? 10 : $rows;
		$field_order = isset($order) ? $order : $this->_getFieldOrder($sidx, $sord);

		$model = ClassRegistry::init($modelName);

		if (!empty($fields)) {
			// user has specified wanted fields, so use it.
			$needFields = $this->_extractFields($fields);
		} else {
			// fallback using model schema fields
			$needFields = array($modelName => array_keys($model->schema()));

			for ($i = 0, $ii = count($needFields[$modelName]); $i < $ii; $i++) {
				$fields[] = $modelName . '.' . $needFields[$modelName][$i];
			}
		}

		if ($_search) {
			if (!empty($filters)) {
				$this->_mergeAdvSearchConditions($options['conditions'], $needFields, $filters);
			} else {
				$this->_mergeFilterConditions($options['conditions'], $needFields, $filterMode);
			}
		}

		$countOptions = $options;
		unset ($countOptions['fields']);
		$count = $model->find('count', $countOptions);

		if ($doExport && $exportOptions) {
			if (in_array(strtolower($exportOptions['type']), array('xls','csv'))) {
				$page = 1;
				$limit = 65535;
			}
			$this->controller->autoRender = false;
		} else {
			$this->controller->viewClass = 'Json';
		}

		$findOptions = $options + array(
			'recursive' => $recursive,
			'page' => $page,
			'limit' => $limit,
			'order' => $field_order
			);

		$rows = $model->find('all', $findOptions);

		if ($doExport) {
			if (!empty($rows[0])) {
				$exportFields = array_keys(Set::flatten($rows[0]));
			} else {
				$exportFields = $fields;
			}
			return $this->_exportToFile($modelName, $exportFields, $rows, $exportOptions);
		}

		$total_pages = $count > 0 ? ceil($count/$limit) : 0;
		$row_count = count($rows);

		$response = array(
			'page' => $page,
			'records' => $row_count,
			'total' => $total_pages
			);

		$response += $this->_constructResponse($rows);

		$this->controller->set(compact('response'));
		$this->controller->set('_serialize', 'response');
	}

	protected function _constructResponse($rows) {
		$response = array();
		for ($i = 0, $row_count = count($rows); $i < $row_count; $i++) {
			$row =& $rows[$i];
			$response['rows'][$i] = Set::flatten($row);
		}
		return $response;
	}
}

?>
