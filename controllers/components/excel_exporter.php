<?php
// vim: set ts=4 sts=4 sw=4 si noet:

$include_path = get_include_path();
$newpath = ($include_path . PATH_SEPARATOR . APP . 'vendors' . DS . 'phpexcel');
set_include_path($newpath);

App::import(array(
	'type' => 'Vendor',
	'name' => 'PHPExcel',
	'file' => 'PHPExcel.php'
));
App::import(array(
	'type' => 'Vendor',
	'name' => 'PHPExcel_IOFactory',
	'file' => 'PHPExcel/IOFactory.php'
));

class ExcelExporterComponent extends Object {

	function _writeHeaders(&$xls, $options) {
		$sheet = $xls->getActiveSheet();

		if (!empty($options['columnHeaders'])) {

			$columnHeaders = $options['columnHeaders'];

		} else {

			$columnHeaders = $options['fields'];
			foreach ($columnHeaders as &$header) {
				if (strstr($header, '.')) {
					$split = explode('.', $header, 2);
					$header = Inflector::humanize($split[1]);
				} else {
					$header = Inflector::humanize($header);
				}
			}
			unset($header);

		}

		$col = ord('A');
		for ($i = 0, $ii = count($columnHeaders); $i < $ii; $i++) {
			$header = $columnHeaders[$i];
			$cell = chr($col) . '1';
			$sheet->setCellValue ($cell, $header);
			$col++;
		}
	}

	/** Export $data into an Excel file
	 *
	 *  @param $data mixed data retrieved via Model->find operation
	 *  @param $options mixed array of options
	 *
	 */
	function export($data, $options = array()) {

		$options += array(
			'outputFile' => 'export.xls',
			'columnHeaders' => array(),
			'fields' => array(),
			);

		if (empty($data)) {
			trigger_error('No data to export');
			return;
		}

		$xls = new PHPExcel();
		$xls->setActiveSheetIndex(0);
		$sheet = $xls->getActiveSheet();

		$this->_writeHeaders($xls, $options);

		for ($i = 0, $ii = count($data); $i < $ii; $i++) {
			$col = ord('A');
			$row = $i + 2;
			for ($c = 0, $cc = count($options['fields']); $c < $cc; $c++) {
				$currentField = $options['fields'][$c];
				$split = explode('.', $currentField, 2);
				$modelName = $split[0];
				$fieldName = $split[1];

				if (!isset($data[$i][$modelName][$fieldName])) {
					continue;
				}

				$cell = chr($col) . $row;
				$sheet->setCellValue($cell, $data[$i][$modelName][$fieldName]);
				$col ++;
			}
		}

		$writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
		$writer->save($options['outputFile']);
	}

}

?>
