<?php
// vim: set ts=4 sts=4 sw=4 si noet:

class AutocompleteHelper extends AppHelper {

	var $helpers = array('Form', 'Js');

	// swiped from Form::label()
	function _getLabel($fieldName) {
		if (strpos($fieldName, '.') !== false) {
			$text = array_pop(explode('.', $fieldName));
		} else {
			$text = $fieldName;
		}
		if (substr($text, -3) == '_id') {
			$text = substr($text, 0, strlen($text) - 3);
		}
		$text = Inflector::humanize(Inflector::underscore(__($text)));
		return $text;
	}

	// let's see how many people will be cross with me ;)
	function _getFieldName($fieldName) {
		return join('', array_map('ucfirst', explode('.', Inflector::camelize($fieldName))));
	}

	function _getControllerName($fieldName) {
		return strtolower(Inflector::pluralize(Inflector::classify($this->_getLabel($fieldName))));
	}

	function input($fieldName, $options = array(), $acOptions = array()) {
		$acFieldName = 'Ac' . $fieldName;

		$hiddenFieldOptions = array('type' => 'hidden');

		$fieldId =  $this->_getFieldName($fieldName);
		$acFieldId = $this->_getFieldName($acFieldName);
		$controller = $this->_getControllerName($fieldName);

		$out  = $this->Form->input($fieldName, $hiddenFieldOptions);
		$out .= $this->Form->input($acFieldName, $options);

		$url = Router::url(array(
			'controller' => $controller,
			'action' => 'autocomplete',
			'ext' => 'json',
			),
			true
		);

		$acOptions += array(
			'dataType' => 'json',
			'parse' => '
<script>
function(data) {
	return $.map(data, function(row) {
		return {
			data: row,
			value: row.id,
			result: row.title
		}
	});
}
</script>',
			'formatItem' => '
<script>
function(item) {
			return item.title;
}
</script>',
		);


		App::import('Vendor', 'Cholesterol.utils');

		$jsonAcOptions = _json_encode($acOptions);

		$script =<<<EOF
$(document).ready(function() {
	$('#{$acFieldId}').autocomplete('$url',
		$jsonAcOptions
)
	.result(function(e, item) {
		$('#{$fieldId}').val(item.id);
	});
});
EOF;
		$this->Js->codeBlock($script, array('inline' => false));
		return $out;
	}
}
