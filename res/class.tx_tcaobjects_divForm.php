<?php

require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_formReloadHandler.php'; 

class tx_tcaobjects_divForm {
	

	/**
	 * =========================================================================
	 * FILTERS
	 * 
	 * (form can submitted and values will be converted before further processing)
	 * =========================================================================
	 */
	

    public static function filter_int($value) {
		return intval($value);
	}
	
	public static function filter_lower($value) {
		return $GLOBALS['TSFE']->cObj->caseshift($value,'lower');
	}
	
	public static function filter_upper($value) {
		return $GLOBALS['TSFE']->cObj->caseshift($value,'upper');
	}
	
	public static function filter_nospace($value) {
		return str_replace(' ', '', $value);
	}
	
	public static function filter_alpha($value) {
		return ereg_replace('[^a-zA-Z]','',$value);
	}
	
	public static function filter_num($value) {
		return ereg_replace('[^0-9]','',$value);
	}
	
	public static function filter_alphanum($value) {
		return ereg_replace('[^a-zA-Z0-9]','',$value);
	}
	
	public static function filter_alphanum_x($value) {
		return ereg_replace('[^a-zA-Z0-9_-]','',$value);
	}
	
	public static function filter_trim($value) {
		return trim($value);
	}
	
	



	/**
	 * =========================================================================
	 * RULES
	 * 
	 * (form can not be submitted if a field doesn't obey to all applied rules)
	 * =========================================================================
	 */
	

    /**
     * Rule for HTML_Quickform: Tests if a field is unique
     *
     * @param 	string	field value
     * @param 	string	additional data: "tablename->propertyname->uid"
     * @return 	bool	pass rule if true
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-03-14
     */
	public static function rule_tca_unique($value, $field) {
		list($table, $property, $uid) = explode ('->', $field);
		$where = $GLOBALS['TYPO3_DB']->quoteStr($property, $table).'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($value, $table);
		$where .= 'AND uid !='.intval($uid);
		$quantity = tx_tcaobjects_objectAccessor::selectCollectionCount($table, $where, true);
		
		return ($quantity < 1) ? true : false;
	}
	
	public static function rule_checktoken($value, $field) {
		$formReloadHandler = new tx_pttools_formReloadHandler();
		return $formReloadHandler->checkToken($value, true);
	}	
	
	public static function rule_int($value, $field) {
		return is_int($value);
	}
	
	public static function rule_notint($value, $field) {
		return !is_int($value);
	}
	
	public static function rule_lower($value, $field) {
		return ereg('[^a-z]',$value) === FALSE;
	}
	
	public static function rule_upper($value, $field) {
		return ereg('[^A-Z]',$value) === FALSE;
	}
	
	public static function rule_nospace($value, $field) {
		return strpos($value, ' ') === FALSE;
	}
	
	public static function rule_alpha($value, $field) {
		return ereg('[^a-zA-Z]',$value) === FALSE;
	}
	
	public static function rule_num($value, $field) {
		return ereg('[^0-9]',$value) === FALSE;
	}
	
	public static function rule_alphanum($value, $field) {
		return ereg('[^a-zA-Z0-9]',$value) === FALSE;
	}
	
	public static function rule_alphanum_x($value, $field) {
		return ereg('[^a-zA-Z0-9_-]',$value) === FALSE;
	}
	
	public static function rule_fullAge(array $value, $field) {
		$tstamp = tx_tcaobjects_div::convertQuickformDateToUnixTimestamp($value);
		return !empty($tstamp) && strtotime ("+ 18 years", $tstamp) <= time();
	}
	
	



	/**
	 * =========================================================================
	 * FORM RULES
	 * 
	 * (rules that need more than 1 field)
	 * =========================================================================
	 */
	

    /**
	 * HTML_QuickForm form rule: 
	 * Checks "password" and "repeat password" fields. 
	 * Assuming the fields are called "password" and "passwordRepeat"
	 * 
	 * @param 	array	fields
	 * @return 	bool|array	true on no errors, array (fieldname => error message) on error(s)
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-04-27
	 */
	public static function formRule_checkPasswords($fields) {
		
		$errors = array();
		
		// guess formname and prefix, not nice :(
		foreach (array_keys($fields) as $prefix) {
			if (substr($prefix, 0, 3) == 'tx_') break;
		}
		
		list($formname) = array_keys($fields[$prefix]);
		
		$fields = $fields[$prefix][$formname];
		
		if ($fields['password'] != $fields['passwordRepeat']) {
			$errors[$prefix.'['.$formname.'][passwordRepeat]'] = 'Passwords do not match';
		}
		
		if (empty($errors)) {
			return TRUE;
		} else {
			return $errors;
		}
	}

	
	public static function formRule_checktoken($fields) {
		// TODO das hier wieder rausnhemen. Ist zwar nett,aber eine exception erfüllt den zwekc auch. Hier wundersn sich die user wieos das formular erneut angezeigt wird
		
		$errors = array();
		
		// guess formname and prefix, not nice :(
		foreach (array_keys($fields) as $prefix) {
			if (substr($prefix, 0, 3) == 'tx_') break;
		}
		
		list($formname) = array_keys($fields[$prefix]);
		
		$fields = $fields[$prefix][$formname];
		
		if (self::rule_checktoken($fields['__formToken'], '') == false) {
			$errors[$prefix.'['.$formname.'][__messages]'] = 'Form already submitted';
		}
		
		if (empty($errors)) {
			return TRUE;
		} else {
			return $errors;
		}
	}
	
	



	/**
	 * =========================================================================
	 * OTHER FUNCTIONS
	 * 
	 * 
	 * =========================================================================
	 */
	

    /**
	 * Gets the object field name from the form field name
	 *
	 * @param 	string	form field name
	 * @return 	string	object field name
	 * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	public static function getFieldFromName($name) {
		$parts = explode('[', $name);
		$field = array_pop($parts);
		return $field = str_replace(array(']', '['), '', $field);
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_divForm.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_divForm.php']);
}

?>