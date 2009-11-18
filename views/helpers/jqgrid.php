<?php
// vim: set ft=php ts=4 sts=4 sw=4 si noet:

/** A very simple helper to generate html tags for use with jqGrid
 *  @author Rachman Chavik
 *  @license MIT
 */
class JqgridHelper extends AppHelper {

	var $helpers = array('Javascript');

	var $modelName;

	var $pager; // ID of pager element

	var $filterToolbar;

	var $exportToExcel;

	/** Generate container for jqGrid */
	function grid($id, $options = array()) {
		$options = array_merge(array(
			'modelName' => false,
			'class' => false,
			'pager' => false,
			'pagerClass' => false,
			'filterToolbar' => false,
			'exportToExcel' => false,
			), $options);

		$tableClass = $pagerhtml = $formhtml = '';
		$this->filterToolbar = $options['filterToolbar'];
		$this->exportToExcel = $options['exportToExcel'];

		if ($options['class'] !== false) {
			$tableClass = 'class=\''. $options['class'] . '\'';
		}

		if (!$options['modelName'] === false) {
			$this->modelName = $options['modelName'];
		} else {
			$this->modelName = Inflector::classify($id);
		}

		if ($this->exportToExcel == true) {
			$options['pager'] = true;
			$formhtml =<<<EOF
<style>
.eexport-excel-form input {
	visibility: hidden;
	display: none;
}
</style>
<form id=form_download_{$id} class=export-excel-form></form>
EOF;
		}

		if ($options['pager'] !== false) {
			if ($options['pager'] === true) {
				$pager = 'pager_' . Inflector::underscore($id);
			} else {
				$pager = $options['pager'];
			}

			$this->pager = $pager;

			if ($options['pagerClass'] !== false) {
				$pagerhtml = '<div id=\'' . $pager . '\'></div>';
			} else {
				$pagerhtml = '<div id=\'' . $pager . '\' class=\'' . 
					$options['pagerClass'] . '\'></div>';
			}
		}

		return $formhtml . '<table id=\'' . $id . '\'' . $tableClass . '></table>' . $pagerhtml;
	}

	function _useModelSchema(&$gridOptions) {
		$model = ClassRegistry::init($this->modelName);

		$colModel =& $gridOptions['colModel'];

		foreach ($model->_schema as $fieldName => $fieldInfo) {
			$colModel[] = array(
				'index' => $this->modelName . '.' . $fieldName,
				'name' => $this->modelName . '.' . $fieldName,
				'label' => Inflector::humanize($fieldName),
				);
		}
	}

	/** Generate javascript block for jqGrid 
	 *  @param $id string id of html element
	 *  @param $gridOptions mixed jqgrid's option 
	 *  @param $navGridOption mixed jqgrid's navigator options
	 *  @param $option mixed Only support array('filterToolbar' = true|false) at this point
	 */
	function script($id, $gridOptions = array(), $navGridOptions = array(), $options = array()) {

		$options = array_merge(array(
			'filterToolbar' => true,
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
			'viewrecords' => true,
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


		if (!empty($this->pager)) {
			$pager = $this->pager;
		}
		if (!empty($gridOptions['pager'])) {
			$pager = $gridOptions['pager'];
		}
		if (!empty($pager)) {
			$gridOptions['pager'] = $pager;
		}

		if (empty($gridOptions['colModel'])) {
			$this->_useModelSchema($gridOptions);
		}

		$buffer = json_encode($gridOptions);
		$buffer = str_replace('\r\n', '', $buffer);
		$buffer = str_replace('\n', '', $buffer);
		$buffer = str_replace('\r', '', $buffer);
		$buffer = str_replace('\t', '', $buffer);
		$buffer = str_replace('\"', '"', $buffer);
		$buffer = str_replace('"<script>', '', $buffer);
		$buffer = str_replace('<\/script>"', '', $buffer);
		$jsonOptions = $buffer;

		$buffer = json_encode($navGridOptions);
		$buffer = str_replace('\r\n', '', $buffer);
		$buffer = str_replace('\n', '', $buffer);
		$buffer = str_replace('\r', '', $buffer);
		$buffer = str_replace('\t', '', $buffer);
		$buffer = str_replace('\"', '"', $buffer);
		$buffer = str_replace('"<script>', '', $buffer);
		$buffer = str_replace('<\/script>"', '', $buffer);
		$jsonNavGridOptions = $buffer;

		$code = '';

		if (!empty($pager)) {
			$code .=<<<EOF
var grid = $('#{$id}').jqGrid($jsonOptions).navGrid('#$pager', $jsonNavGridOptions);
EOF;

		} else {
			$code .=<<<EOF
var grid = $('#{$id}').jqGrid($jsonOptions);
EOF;
		}

		if ($this->exportToExcel) {
			$code .=<<<EOF
grid.navButtonAdd('#$pager',{
	caption: '',
	buttonicon: 'ui-icon-disk',
	onClickButton: function() {
		var url = grid.getGridParam('url')
		var post = grid.getPostData();
		var param = [];
		var form = $('#form_download_{$id}');
		post.exportToExcel = true;

		var inputs = '';
		for (p in post) { 
			var item = p + '=' + post[p]; 
			inputs += '<input name="' + p + '" value="' + post[p] + '">';
			param.push(item) 
		}
		form.html(inputs);

		form.attr('action', url).submit();

		delete post.exportToExcel;
	}, 
	position: 'last'
});
EOF;
		}
		if ($this->filterToolbar) {
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
