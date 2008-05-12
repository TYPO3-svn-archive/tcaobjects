<?php

require_once(t3lib_extMgm::extPath('smarty').'class.tx_smarty_wrapper.php');

// Create a wrapper class extending Smarty
class ux_tx_smarty_wrapper extends tx_smarty_wrapper {


	// Creates a new instance of Smarty with references to the parent class and the current instance of tslib_cobj
	public function startSmarty(&$pObj) {
	    $this->register_modifier('ll', array($this, 'smarty_modifier_ll'));

	    parent::startSmarty($pObj);
	}



	/**
	 * Edited by Fabrizio Branca
	 *
	 * removed bugs
	 * - getFileAbsFileName instead of getFileAbsName
	 * - no absolute paths
	 *
	 * @param 	mixed	string, getFileAbsFileName or tx_lib_object object
	 * @return 	void
	 */
	public function setPathToLanguageFile($param) {
		if(is_object($param)) {
			if($langFile = t3lib_div::getFileAbsFileName($this->t3_conf['pathToLanguageFile']) && @is_file($langFile)) {
				// Explicit language file definition in Smarty TypoScript has 1st priority
				$this->t3_languageFile = $this->t3_conf['pathToLanguageFile'];
			} elseif(is_subclass_of($param,'getFileAbsFileName')) { // Else check for pi_base scenario...
				$basePath = t3lib_extMgm::extPath($param->extKey).dirname($param->scriptRelPath).'/locallang';
				$file = t3lib_div::getFileAbsFileName($basePath);
				if (@is_file($file.'.xml')) { // Check for XML file
					$this->t3_languageFile = 'EXT:'.$param->extKey.dirname($param->scriptRelPath).'/locallang.xml';
				} elseif(@is_file($file.'.php')) { // or PHP file
				    $this->t3_languageFile = 'EXT:'.$param->extKey.dirname($param->scriptRelPath).'/locallang.php';
				}
			} elseif(is_subclass_of($param,'tx_lib_object')) { // ...or lib/div mvc scenario
				$this->t3_languageFile = $param->controller->configurations->get('pathToLanguageFile');
			}
		} else {
			$this->t3_languageFile = $param;
		}
	}



	/**
	 * Smarty modifier for language labels
	 * Usage: {"label"|ll}
	 *
	 * @param 	string	language label key
	 * @return 	string	language label
	 */
    public function smarty_modifier_ll($key) {

	    // Get the language file and/or label information from the key
		$parts = t3lib_div::trimExplode(':',$key,1);
		$parts = t3lib_div::removeArrayEntryByValue($parts,'LLL');
		$label = array_pop($parts);
		$language_file = implode(':',$parts);
		$language_file = ($language_file)?$language_file:$this->t3_languageFile;

		// Call the sL method from lang object to translate the label
		$translation = $GLOBALS['LANG']->sL('LLL:'.$language_file.':'.$label);
		
		// if additional arguments exists invoke sprintf on the label
		if (func_num_args() > 1) {
			$args = func_get_args();
			array_shift($args);
			$translation = vsprintf($translation, $args);
		}
		
		// Exit if no translation was found
		if(!$translation) {
			$this->trigger_error('Translation unavailable for key "'.$key.'" in language "'.$GLOBALS['TSFE']->lang.'" (language file:"'.$language_file.'")');
			return ($params['alt'])?$params['alt']:$content;
		}

		// If the result contains Smarty template vars run it through Smarty again
		if (preg_match('/['.quotemeta($this->left_delimiter).'[^'.quotemeta($this->right_delimiter).']*'.quotemeta($this->right_delimiter).'/m', $translation)) {
			return ($params['hsc'])?htmlspecialchars($this->display('string:'.$translation)):$this->display('string:'.$translation);
		} else {
			return ($params['hsc'])?htmlspecialchars($translation):$translation;
		}
	}



}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/misc/class.ux_tx_smarty_wrapper.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/misc/class.ux_tx_smarty_wrapper.php']);
}

?>