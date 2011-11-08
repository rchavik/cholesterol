<?php
// vim: set ts=4 sw=4 sts=4 si noet:

// A widget helper that retrieves data and renders the element.
// Ideas taken from:
//   http://jamienay.com/2009/11/an-easy-plugin-callback-component-for-cakephp-1-2/
//   http://debuggable.com/posts/requestaction-considered-harmful:48abb514-1f9c-4443-b91c-6d0f4834cda3
class WidgetHelper extends AppHelper {

	/** Similar to View::element with extra component name to load */
	function element($componentName, $elementName, $params = array(), $loadHelpers = false) {

		$componentName = Inflector::classify($componentName . '_widget');
		$componentClassName = $componentName . 'Component';

		if (isset($params['widget_options'])) {
			$widgetOptions = $params['widget_options'];
			unset($params['widget_options']);
		} else {
			$widgetOptions = array();
		}

		// import the component, and call component's method
		if (App::import('Component', $componentName)) {
			$component = ClassRegistry::init($componentClassName, 'component');
			$splitElementName = split('/', $elementName);
			$methodName = $splitElementName[1];
			if (method_exists($component, $methodName)) {
				$data = $component->{$methodName}($widgetOptions);
				$params += array('widget_data' => $data);
			}
		}

		$view = ClassRegistry::getObject('view');
		return $view->element($elementName, $params, $loadHelpers);
	}
}

?>
