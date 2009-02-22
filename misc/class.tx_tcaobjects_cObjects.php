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
				throw new tx_pttools_exception('Not implemented yet!');
			} break;
			default : {
				throw new tx_pttools_exception(sprintf('cObject "%s" not valid', $contentObjectName));
			}
		}

		return $content;
	}

	protected function TCAOBJECT(array $conf, tslib_cObj $parentObject) {

		$conf['uid'] = $parentObject->stdWrap($conf['uid'], $conf['uid.']);
		$conf['className'] = $parentObject->stdWrap($conf['className'], $conf['className.']);

		tx_pttools_assert::isNotEmptyString($conf['uid'], array('message' => 'No "uid" given!'));
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
		tx_pttools_assert::isInstanceOf($object, 'tx_tcaobjects_object', array('message' => 'Created object is no instance of "tx_tcaobjects_object"'));

		return $object;
	}

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


	protected function SMARTYTEMPLATE(array $conf, tslib_cObj $parentObject) {

		// Getting the content
		$content = $parentObject->cObjGetSingle($conf['template'], $conf['template.'], 'template');

		// render marks
		$markerArray = array();

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

}

?>