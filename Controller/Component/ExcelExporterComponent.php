<?php
// vim: set ts=4 sts=4 sw=4 si noet:

$include_path = get_include_path();
$newpath = ($include_path . PATH_SEPARATOR . APP . 'Vendor' . DS . 'phpexcel');
set_include_path($newpath);

App::import('Vendor', 'PHPExcel', array(
	'file' => 'phpexcel/PHPExcel.php'
));
App::import('Vendor', 'PHPExcel_IOFactory', array(
	'file' => 'phpexcel/PHPExcel/IOFactory.php'
));

App::uses('CakeTime', 'Utility');

class ExcelExporterComponent extends Component {

	public function initialize(Controller $controller) {
		$this->controller = $controller;
		$this->Time = new CakeTime();
	}

	protected function _writeHeaders(&$xls, $options) {
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

		$col = 0;
		for ($i = 0, $ii = count($columnHeaders); $i < $ii; $i++) {
			$header = $columnHeaders[$i];
			$cell = PHPExcel_Cell::stringFromColumnIndex($col) . '1';
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
	public function export($modelName, $data, $options = array()) {
		$needHeader = true;
		$startRow = 2;
		$startCol = 'A';

		$options = Hash::merge(array(
			'template' => array(
				'file' => null,
				'type' => 'Excel5',
				'startRow' => $startRow,
				'startCol' => $startCol,
				),
			'output' => array(
				'file' => 'export.xls',
				'type' => 'Excel5',
				),
			'columnHeaders' => array(),
			'fields' => array(),
			'format' => array(
				'date' => 'd/m/Y',
				'float' => '%.2f',
				)
			), $options
		);

		if (empty($data)) {
			throw new CakeException('No data to export');
		}

		if (!empty($options['template']['file'])) {
			$template = $options['template'];
			$startRow = $template['startRow'];
			$startCol = $template['startCol'];
			$reader = PHPExcel_IOFactory::createReader($template['type']);
			$xls = $reader->load(($template['file']));
			$needHeader = false;
		} else {
			$xls = new PHPExcel();
			$xls->setActiveSheetIndex(0);
		}
		$sheet = $xls->getActiveSheet();

		if ($needHeader) {
			$this->_writeHeaders($xls, $options);
		}

		$Model = ClassRegistry::init($modelName);

		for ($i = 0, $ii = count($data); $i < $ii; $i++) {
			$col = 0;
			$row = $i + $startRow;
			for ($c = 0, $cc = count($options['fields']); $c < $cc; $c++) {
				$currentField = $options['fields'][$c];
				$split = explode('.', $currentField, 2);
				$fieldModel = $split[0];
				$fieldName = $split[1];
				$cell = PHPExcel_Cell::stringFromColumnIndex($col) . $row;
				if (!isset($data[$i][$fieldModel][$fieldName])) {
					$fieldType = 'string';
					$fieldValue = '';
				} else {
					$fieldType = $this->_getColumnType($Model, $fieldModel, $fieldName);
					$fieldValue = $data[$i][$fieldModel][$fieldName];
				}
				$this->_setCellValue($sheet, $cell, $fieldType, $fieldValue, $options);

				$col ++;
			}
		}

		$writer = PHPExcel_IOFactory::createWriter($xls, $options['output']['type']);
		$writer->save($options['output']['file']);
	}

	protected function _getColumnType($Model, $fieldModel, $fieldName) {
		if ($fieldModel == $Model->name) {
			$fieldType = $Model->getColumnType($fieldName);
		} else {
			if (property_exists($Model, $fieldModel)) {
				$fieldType = $Model->{$fieldModel}->getColumnType($fieldName);
			} else {
				$fieldType = 'string';
			}
		}
		return $fieldType;
	}

	/** Set cell value and format according to field type */
	protected function _setCellValue($sheet, $cell, $fieldType, $fieldValue, $options) {
		switch ($fieldType) {

		case 'timestamp':
		case 'date':
			$value = $this->Time->format($options['format']['date'], $fieldValue);
			$value = $value == '0000-00-00 00:00:00' ? '' : $value;

			if (strlen($value) == 19 && !preg_match('/00:00:00$/', $value)) {
				$format = 'dd/mm/yy hh:mm:ss';
			} else {
				$format = PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY;
			}
			$sheet->getStyle($cell)->getNumberFormat()->setFormatCode($format);
			$sheet->setCellValue($cell, $value);
			break;

		case 'float':
			$sheet->getStyle($cell)->getNumberFormat()->setFormatCode(
				PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
				);
			$sheet->setCellValue($cell, sprintf($options['format']['float'], $fieldValue));
			break;

		default:
			if(is_numeric($fieldValue) && $fieldValue[0]=='0')
				$sheet->setCellValueExplicit($cell, $fieldValue, PHPExcel_Cell_DataType::TYPE_STRING);
			else
				$sheet->setCellValue($cell, $fieldValue);
		}
	}

}
