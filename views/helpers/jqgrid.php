<?php
// vim: set ft=php ts=4 sts=4 sw=4 si noet:

/** A very simple helper to generate html tags for use with jqGrid
 *  @author Rachman Chavik
 *  @license MIT
 */
class JqgridHelper extends AppHelper {

	var $helpers = array('Javascript');

	/** Generate container for jqGrid */
	function grid($id, $options = array()) {
		$options = array_merge(array(
			'class' => false,
			'pager' => false,
			'pagerClass' => false
			), $options);

		$tableClass = $pager = '';

		if ($options['class'] !== false) {
			$tableClass = 'class=\''. $options['class'] . '\'';
		}

		if ($options['pager'] !== false) {
			$pager = $options['pager'];
			if ($options['pagerClass'] !== false) {
				$pager = '<div id=\'' . $pager . '\'></div>';
			} else {
				$pager = '<div id=\'' . $pager . '\' class=\'' . 
					$options['pagerClass'] . '\'></div>';
			}
		}

		return '<table id=\'' . $id . '\'' . $tableClass . '></table>' . $pager;
	}

	/** Generate javascript block for jqGrid 
	 *  @param $id string id of html element
	 *  @param $gridOptions mixed jqgrid's option 
	 *  @param $navGridOption mixed jqgrid's navigator options
	 *  @param $option mixed Only support array('filterToolbar' = true|false) at this point
	 */
	function script($id, $gridOptions = array(), $navGridOptions = array(), $options = array()) {

		$options = array_merge(array(
			'filterToolbar' => true
			), $options
		);

		$gridOptions = array_merge(array(
			'caption' => null,
			'datatype' => 'json',
			'mtype' => 'GET',
			'gridModel' => true,
			'url' => null,
			'pager' => null,
			'colNames' => array(),
			'colModel' => array(),
			'rowNum' => 5,
			'rowList' => array(5, 10),
			'width' => '100%',
			'jsonReader' => array(
				'repeatitems' => false,
				'id' => 'id',
				)
			), $gridOptions
		);

		$navGridOptions = array_merge(array(
			'add' => false,
			'edit' => false,
			'del' => false,
			'search' => false,
			), $navGridOptions);

		$buffer = json_encode($gridOptions);
		$buffer = str_replace('\r\n', '', $buffer);
		$buffer = str_replace('\n', '', $buffer);
		$buffer = str_replace('\t', '', $buffer);
		$buffer = str_replace('\"', '"', $buffer);
		$buffer = str_replace('"<script>', '', $buffer);
		$buffer = str_replace('<\/script>"', '', $buffer);
		$jsonOptions = $buffer;

		$buffer = json_encode($navGridOptions);
		$buffer = str_replace('\r\n', '', $buffer);
		$buffer = str_replace('\n', '', $buffer);
		$buffer = str_replace('\t', '', $buffer);
		$buffer = str_replace('\"', '"', $buffer);
		$buffer = str_replace('"<script>', '', $buffer);
		$buffer = str_replace('<\/script>"', '', $buffer);
		$jsonNavGridOptions = $buffer;

		$code = '';
		if (!empty($gridOptions['pager'])) {
			$code .=<<<EOF
var grid = $('#{$id}').jqGrid($jsonOptions).navGrid('#$gridOptions[pager]', $jsonNavGridOptions);
EOF;

		} else {
			$code .=<<<EOF
var grid = $('#{$id}').jqGrid($jsonOptions);
EOF;
		}

		if ($options['filterToolbar']) {
			$code .=<<<EOF
grid.filterToolbar();
EOF;
		}

		$script =<<<EOF
$(document).ready(function() {
	$code;
});
EOF;

		return $this->Javascript->codeBlock($script);
	}

}

?>
