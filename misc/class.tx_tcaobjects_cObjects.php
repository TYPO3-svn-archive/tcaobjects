<?php

require_once PATH_tslib . 'interfaces/interface.tslib_content_cobjgetsinglehook.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_smartyAdapter.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_assert.php';

class tx_tcaobjects_cObjects /* extends tslib_cObj */ implements tslib_content_cObjGetSingleHook {

	/**
	 * Renders content objects, that are not defined in the core
	 *
	 * @param	string		The content object name, eg. "TEXT" or "USER" or "IMAGE"
	 * @param	array		array with TypoScript properties for the content object
	 * @param	string		label used for the internal debug tracking
	 * @param	tslib_cObj	parent content object
	 * @return	string		cObject output
	 */
	public function getSingleContentObject($contentObjectName, array $configuration, $TypoScriptKey, tslib_cObj &$parentObject) {
		$content = '';

		switch($contentObjectName) {
			case 'SMARTYTEMPLATE': {
				$content = $this->SMARTYTEMPLATE($configuration, $parentObject);
			} break;
			case 'TCAOBJECT': {
				$content = $this->TCAOBJECT($configuration, $parentObject);
			} break;
			case 'TCAOBJECTCOLLECTION' : {
				$content = $this->TCAOBJECTCOLLECTION($configuration, $parentObject);
			} break;
			case 'TCAOBJECTFORM' : {
				$content = $this->TCAOBJECTFORM($configuration, $parentObject);
			} break;
			default : {
				throw new tx_pttools_exception(sprintf('cObject "%s" not valid', $contentObjectName));
			}
		}

		return $content;
	}



	/**
	 * cObject "TCAOBJECT"
	 *
	 * @param 	array	configuration array
	 * @param 	tslib_cObj	parent objects
	 * @return 	string 	content
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 	2009-03-01
	 */
	protected function TCAOBJECT(array $conf, tslib_cObj $parentObject) {

		$conf['uid'] = $parentObject->stdWrap($conf['uid'], $conf['uid.']);
		$conf['className'] = $parentObject->stdWrap($conf['className'], $conf['className.']);

		// tx_pttools_assert::isNotEmptyString($conf['uid'], array('message' => 'No "uid" given!'));
		tx_pttools_assert::isNotEmptyString($conf['className'], array('message' => 'No "className" given!'));

		$parts = t3lib_div::trimExplode(':', $conf['className']);
		$className = array_pop($parts);

		$path = implode(':', $parts);
		if (!empty($path)) {
			// try to include the file
			tx_pttools_assert::isFilePath($path);
			require_once t3lib_div::getFileAbsFileName($path);
		}

		$object = new $className($conf['uid']);

		if (is_array($conf['override.'])) {

			$conf['override.'] = tx_tcaobjects_div::stdWrapArray($conf['override.'], $parentObject);

			foreach ($conf['override.'] as $key => $value) {
				$object[$key] = $value;
			}
		}

		tx_pttools_assert::isInstanceOf($object, 'tx_tcaobjects_object', array('message' => 'Created object is no instance of "tx_tcaobjects_object"'));

		return $object;
	}



	/**
	 * cObject "TCAOBJECTCOLLECTION"
	 *
	 * @param 	array	configuration array
	 * @param 	tslib_cObj	parent objects
	 * @return 	string 	content
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 	2009-03-01
	 */
	protected function TCAOBJECTCOLLECTION(array $conf, tslib_cObj $parentObject) {
		$conf['className'] = $parentObject->stdWrap($conf['className'], $conf['className.']);
		$conf['load'] = $parentObject->stdWrap($conf['load'], $conf['load.']);

		tx_pttools_assert::isNotEmptyString($conf['load'], array('message' => 'No "load" given!'));
		tx_pttools_assert::isNotEmptyString($conf['className'], array('message' => 'No "className" given!'));

		if (is_array($conf['params.'])) {
			$conf['params.'] = tx_tcaobjects_div::stdWrapArray($conf['params.']);
		} else {
			$conf['params.'] = array();
		}

		$parts = t3lib_div::trimExplode(':', $conf['className']);
		$className = array_pop($parts);

		$path = implode(':', $parts);
		if (!empty($path)) {
			// try to include the file
			tx_pttools_assert::isFilePath($path);
			require_once t3lib_div::getFileAbsFileName($path);
		}

		$object = new $className($conf['load'], $conf['params.']);
		tx_pttools_assert::isInstanceOf($object, 'tx_tcaobjects_objectCollection', array('message' => 'Created object is no instance of "tx_tcaobjects_objectCollection"'));

		return $object;

	}



	/**
	 * cObject "SMARTYTEMPLATE"
	 *
	 * @param 	array	configuration array
	 * @param 	tslib_cObj	parent objects
	 * @return 	string 	content
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 	2009-03-01
	 */
	protected function SMARTYTEMPLATE(array $conf, tslib_cObj $parentObject) {

		// Getting the content
		$content = $parentObject->cObjGetSingle($conf['template'], $conf['template.'], 'template');

		// render marks
		$markerArray = array();
		$markerArray['currentPage'] = $GLOBALS['TSFE']->id;

		if (is_array($conf['marks.']))	{
			foreach ($conf['marks.'] as $theKey => $theValue) {
				if (substr($theKey, -1) != '.') {
					$markerArray[$theKey] = $parentObject->cObjGetSingle($theValue, $conf['marks.'][$theKey.'.'],'marks.'.$theKey);
					if (is_string($markerArray[$theKey]) && t3lib_div::isFirstPartOfStr($markerArray[$theKey], 'tcaobject')) {
						$markerArray[$theKey] = tx_pttools_registry::getInstance()->get($markerArray[$theKey]);
					}
				}
			}
		}

		$smarty = new tx_pttools_smartyAdapter();
		foreach ($markerArray as $key => $value) {
			if (!(is_scalar($value) || is_array($value) || $value instanceof ArrayAccess)) {
				throw new tx_pttools_exception(sprintf('Value in key "%s" has to be an array, scalar or implement the ArrayAccess interface', $key));
			}
			$smarty->assign($key, $value);
		}

		return $smarty->fetch('string:'.$content);
	}



	/**
	 * cObject "TCAOBJECTFORM"
	 *
	 * @param 	array	configuration array
	 * @param 	tslib_cObj	parent objects
	 * @return 	string 	content
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 	2009-03-01
	 */
	protected function TCAOBJECTFORM(array $conf, tslib_cObj $parentObject) {
		tx_pttools_assert::isNotEmptyString($conf['prefix'], array('message' => 'No "prefix" parameter given!'));
		tx_pttools_assert::isNotEmptyString($conf['formName'], array('message' => 'No "formName" parameter given!'));
		tx_pttools_assert::isNotEmptyArray($conf['formDefinition.'], array('message' => 'No "formDefinition" parameter given!'));

		// retrieve tcaObject
		$conf['tcaObject'] = $parentObject->cObjGetSingle($conf['tcaObject'], $conf['tcaObject.']);
		if (($conf['tcaObject']) && t3lib_div::isFirstPartOfStr($conf['tcaObject'], 'tcaobject')) {
			$conf['tcaObject'] = tx_pttools_registry::getInstance()->get($conf['tcaObject']);
		}
		tx_pttools_assert::isInstanceOf($conf['tcaObject'], 'tx_tcaobjects_object', array('message' => 'Invalid "tcaObject" given!'));

		if (empty($conf['method'])) $conf['method'] = 'post';
		tx_pttools_assert::isInArray(strtolower($conf['method']), array('post', 'get'), array('message' => 'Method must be either "post" or "get"!'));

		// action (TEXT/typolink with returnLast = url)
		$conf['action'] = $parentObject->cObjGetSingle($conf['action'], $conf['action.']);

		if (empty($conf['onValidated'])) {
			$conf['onValidated'] = 'EXT:tcaobjects/misc/class.tx_tcaobjects_cObjects.php:tx_tcaobjects_cObjects->saveOnValidated';
		}

		$form = new tx_tcaobjects_quickform(
			/* prefixId */ 			$conf['prefix'],
			/* formname */ 			$conf['formName'],
			/* method */ 			$conf['method'],
			/* action */			$conf['action'],
			/* target */ 			$conf['target'],
			/* attributes */ 		is_array($conf['attributes.']) ? $conf['attributes.'] : array(),
			/* track submit */ 		$conf['trackSubmit'],
			/* tcaObject */ 		$conf['tcaObject'],
			/* form definition */	$conf['formDefinition.']
		);

		$form->set_onValidated($conf['onValidated'], is_array($conf['onValidated.']) ? $conf['onValidated.'] : array());
		$form->set_onNotValidated($conf['onNotValidated'], is_array($conf['onNotValidated.']) ? $conf['onNotValidated.'] : array());
		$form->set_onCancel($conf['onCancel'], is_array($conf['onCancel.']) ? $conf['onCancel.'] : array());

		// set renderer
		if (empty($conf['renderer'])) {
			$conf['renderer'] = 'EXT:tcaobjects/res/class.tx_tcaobjects_qfDefaultRenderer.php:tx_tcaobjects_qfDefaultRenderer';
		}
		$renderer = t3lib_div::getUserObj($conf['renderer']);
		tx_pttools_assert::isInstanceOf($renderer, 'tx_tcaobjects_iQuickformRenderer');
		tx_pttools_assert::isInstanceOf($renderer, 'HTML_QuickForm_Renderer');
		if (method_exists($renderer, 'setTemplateFile') && !empty($conf['templateFile'])) {
			$renderer->setTemplateFile($conf['templateFile']);
		}
		$form->set_renderer($renderer);

		// processes "onCancel", "onValidate" or "onNotValidate"
		return $form->processController();

	}

	public static function saveOnValidated(array $params, tx_tcaobjects_quickform $form) {
		$form->setObjectPropertiesFromSubmitValues();

		$modelObj = $form->get_object();
		$modelObj->storeSelf();

		$cObj = clone $GLOBALS['TSFE']->cObj;
		$cObj->data = $modelObj->getDataArray();

		return $cObj->cObjGetSingle($params['conf']['message'], $params['conf']['message.']);
	}

}

?>