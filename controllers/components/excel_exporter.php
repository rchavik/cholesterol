<?php
// vim: set ts=4 sts=4 sw=4 si noet:

class ExcelExporter extends Object {

	function _writeHeaders(&$xls, $options) {
		$sheet = $xls->getActiveSheet();

		$col = ord('A');
		foreach ($options['columnHeaders'] as $header) {
			$cell = chr($col) . '1';
			$sheet->setCellValue ($cell, $header);
			$col++;
		}

	}

	function export($rows, $options = array()) {

		$options += array(
			'outputDirectory' => '/tmp',
			'outputFile' => 'export.xls',
			);

		if (empty($rows)) {
			trigger_error('No data to export');
			return;
		}


		$include_path = get_include_path();
		$newpath = ($include_path . PATH_SEPARATOR . APP . 'vendors' . DS . 'phpexcel');
		set_include_path($newpath);

		include_once('PHPExcel.php');
		include_once('PHPExcel/IOFactory.php');
		include_once('PHPExcel/Cell/DataType.php');


		$xls = new PHPExcel();
		$xls->setActiveSheetIndex(0);
		$sheet = $xls->getActiveSheet();

		if (!empty($options['columnHeaders'])) {
			$this->_writeHeaders($xls, $options);
		}

		for ($i = 0, $ii = count($rows); $i < $ii; $i++) {
			$col = ord('A');
			for ($c = 0, $cc = count($options['fields']); $c < $cc; $c++) {
				$currentField = $options['fields'][$c];
				$split = explode('.', $currentField, 2);
				$modelName = $split[0];
				$fieldName = $split[1];

				if (!isset($rows[$i][$modelName][$fieldName])) {
					continue;
				}
				
				$cell = chr($col) . ($i + 1);
				$sheet->setCellValue($cell, $rows[$i][$modelName][$fieldName]);
				$col ++;
			}
		}

		$writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
		$writer->save($options['outputFile']);
	}

}

?>
