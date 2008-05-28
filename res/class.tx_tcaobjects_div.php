<?php

require_once PATH_t3lib . 'class.t3lib_tstemplate.php';

class tx_tcaobjects_div {
	
	
	
	
	/**
	 * Returns the classname for a given property.
	 * Looks at config "tcaobject" or uses "allowed" as default.
	 *
	 * @param 	string		calling table name
	 * @param 	string		calling property name
	 * @return 	string 		class name
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-23
	 */
	public static function getClassname($callingtable, $callingproperty) {
		t3lib_div::loadTCA($callingtable);
		
		$classname = self::getColumnConfig($callingtable, $callingproperty, 'foreign_tcaobject_class');
		
		if (empty($classname)) {
			$classname = self::getColumnConfig($callingproperty, $callingproperty, 'allowed');
		}
		if (empty($classname)) {
			$classname = $callingproperty;
		}
		if (!class_exists($classname)) {
			throw new tx_pttools_exception('Target class "'.$classname.'" for property "'.$callingproperty.'" in table "'.$callingtable.'" does not exist!');
		}
		return $classname;
	}
	
	
	
	/**
	 * Get the config for a property from the TCA
	 *
	 * @param 	string		table name
	 * @param 	string		property name
	 * @param 	string		config element
	 * @return 	string		configuration
	 * @author	Fabrizio Branca <branca@punkt.de>
	 * @since	2008-04-27
	 */
	public static function getColumnConfig($table, $column, $config) {
		if (empty($table)) {
			throw new tx_pttools_exception('Parameter "table" empty!');
		}
		if (empty($column)) {
			throw new tx_pttools_exception('Parameter "column" empty!');
		}
		if (empty($config)) {
			throw new tx_pttools_exception('Paramter "config" empty!');
		}
		t3lib_div::loadTCA($table);
		return $GLOBALS['TCA'][$table]['columns'][$column]['config'][$config];
	}

	
	/**
	 * Load the lang object in frontend environment
	 * 
	 * @param	void
	 * @return	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-23
	 */
	public static function loadLang() {
		if (!is_object($GLOBALS['LANG'])) {

			require_once t3lib_extMgm::extPath('lang') . 'lang.php';
			
			$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
			$GLOBALS['LANG']->csConvObj = t3lib_div::makeInstance('t3lib_cs');
		}
		if (is_object($GLOBALS['TSFE']) && $GLOBALS['TSFE']->config['config']['language']) {
			$LLkey = $GLOBALS['TSFE']->config['config']['language'];
			$GLOBALS['LANG']->init($LLkey);
		}
		
	}
	
	

	/**
	 * Autoload function for all tcaobjects
	 * 
	 * @param	string	$className
	 * @return	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-21
	 */
	public static function autoLoad($className) {
	
		$extKey = self::getCondensedExtKeyFromClassName($className);
		
		$path = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tcaobjects']['autoLoadingPath'][$extKey];
		if (!empty($path)) {
			$path .= 'class.' . $className . '.php';
			$path = t3lib_div::getFileAbsFileName($path);
			if (file_exists($path)) {
				require_once $path;
			}
		}
	}
	
	
	
	/**
	 * Get condensened extension key from class name
	 *
	 * @param 	string	class name
	 * @return 	string 	condensed extension key
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-05-27
	 */
	public static function getCondensedExtKeyFromClassName($className) {
		list ( , $extKey) = t3lib_div::trimExplode('_', $className);
		return $extKey;
	}
	
	

	/**
	 * Autoload function for pear classes
	 * 
	 * @param	string	$className
	 * @return	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-04-27
	 */
	public static function pearAutoLoad($classname) {
		$path = str_replace('_', '/', $classname) . '.php';
		
		if (self::file_exists_incpath($path)) {
			require_once $path;
		}
	}
	
	
	
	/**
	 * Check if a file exists in the include path
	 *
	 * @version     1.2.1
	 * @author      Aidan Lister <aidan@php.net>
	 * @link        http://aidanlister.com/repos/v/function.file_exists_incpath.php
	 * @param       string     $file       Name of the file to look for
	 * @return      mixed      The full path if file exists, FALSE if it does not
	 */
	public static function file_exists_incpath($file) {
		$paths = explode(PATH_SEPARATOR, get_include_path());
	
		foreach ($paths as $path) {
			// Formulate the absolute path
			$fullpath = $path . DIRECTORY_SEPARATOR . $file;
	
			// Check it
			if (file_exists($fullpath)) {
				return $fullpath;
			}
		}
	
		return false;
	}
	
	
	
	/**
	 * Create a fake backend user object
	 * 
	 * @param 	void
	 * @return 	t3lib_beUserAuth
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-21
	 */
	public static function createFakeBeUser() {
		$BE_USER = t3lib_div::makeInstance('t3lib_beUserAuth'); /* @var $BE_USER t3lib_beUserAuth */
		$BE_USER->OS = TYPO3_OS;

		$BE_USER->user['uid'] = 1; // must be "true"
		$BE_USER->user['admin'] = 1;
		$BE_USER->user['workspace_perms'] = '1';
		$BE_USER->fetchGroupData();
		
		return $BE_USER;
	}
	
	
	
	/**
	 * Renders a tt_content record
	 *
	 * @param 	int		uid of tt_content record
	 * @return 	string	HTML Output of the rendered record
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-04-27
	 */
	public static function renderContent($uid) {
		$lConf = array('tables' => 'tt_content', 'source' => $uid);
		return $GLOBALS['TSFE']->cObj->RECORDS($lConf);
	}
	
	
	
	/**
	 * Converts a QuickForm date to a unix timestamp
	 *
	 * @param 	array 	array with values
	 * @return  int		unix timestamp or 0 if array values are not complete
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-04-27
	 */
	public static function convertQuickformDateToUnixTimestamp(array $value) {
		/*
		  D = Short names of days
		  l = Long names of days
		  d = Day numbers
		  M = Short names of months
		  F = Long names of months
		  m = Month numbers
		  Y = Four digit year
		  h = 12 hour format
		  H = 23 hour  format
		  i = Minutes
		  s = Seconds
		  a = am/pm
		  A = AM/PM 

		  see: http://midnighthax.com/quickform.php

		 */
		$day = max($value['D'], $value['l'], $value['d']);
		$month = max($value['M'], $value['F'], $value['m']);
		$year = $value['Y'];
		// TODO: support other formats
		
		if (empty($day) || empty($month) || empty($year)) {
			return 0;
		} else {
			return mktime(0, 0, 0, $month, $day, $year);
		}
	}
	
	
	
	/**
	 * Applies a stdWrap on all items of an array
	 *
	 * @param 	array 	input array
	 * @return 	array	output array
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-26
	 */
	public static function stdWrapArray(array $data) {
		$newData = array();
		foreach (array_keys($data) as $key) {
			if (substr($key, -1) != '.') {
				if (empty($newData[$key])) {
					$newData[$key] = $GLOBALS['TSFE']->cObj->stdWrap($data[$key], $data[$key.'.']);
				}	
			} else {
				if (empty($newData[substr($key, 0, -1)])) {
					$newData[substr($key, 0, -1)] = $GLOBALS['TSFE']->cObj->stdWrap($data[substr($key, 0, -1)], $data[$key]);
				}
			}
		}
		return $newData;
	}
	
	
	
	/**
	 * Converts a typoscript quickform definition into a definition string
	 *
	 * @param 	array 	typoscript form definition
	 * @return 	string	quickform definition as string
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-26 
	 */
	public static function tsToQuickformString(array $conf) {
		// TODO: groups!
		$quickform = array();
		uksort($conf, 'strnatcmp'); // this is the missing knatsort	
		
		foreach ($conf as $fieldconf) {
			
			$rulesTmp = $fieldconf['rules.'];
			$attrTmp = $fieldconf['attributes.'];
			$fieldconf = self::stdWrapArray($fieldconf);
			$fieldconf['rules.'] = $rulesTmp;
			$fieldconf['attributes.'] = $attrTmp;			
			
			// Rules
			$fieldRules = array();
			foreach ((array)$fieldconf['rules.'] as $rule => $message) {
				$fieldRules[] = $rule.(!empty($message) ? '='.$message : '');	 
			}
			$fieldRules = implode(':', $fieldRules);
			
			// Attributes
			$fieldAttributes = array();
			foreach ((array)$fieldconf['attributes.'] as $attributeKey => $attributeValue) {
				$fieldAttributes[] = $attributeKey.(!empty($attributeValue) ? '='.$attributeValue : '');	 
			}
			$fieldAttributes = implode(':', $fieldAttributes);
			
			$quickform[] = rtrim(implode(';', array($fieldconf['property'], $fieldconf['altLabel'], $fieldconf['specialtype'], $fieldconf['content'], $fieldRules, $fieldAttributes)),';');
		}
		return implode(',', $quickform);
	}
	
	
	/**
	 * Checks if the foreign table of a given property/column is a mm table
	 *
	 * @param 	string	tablename
	 * @return 	bool
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-02-21
	 */
	public static function ForeignTableIsMmTable($table, $property) {
		if (empty($table)) {
			throw new tx_pttools_exception('Parameter "table" empty!');
		}
		if (empty($property)) {
			throw new tx_pttools_exception('Parameter "property" empty!');
		}		
		if (self::getColumnConfig($table, $property, 'foreign_table') && self::getColumnConfig($table, $property, 'foreign_mm_field') ) {
			return true;
		} else {
			return false;
		}
	}
	
	
	public static function getForeignClassName($table, $property) {
		if (empty($table)) {
			throw new tx_pttools_exception('Parameter "table" empty!');
		}
		if (empty($property)) {
			throw new tx_pttools_exception('Parameter "property" empty!');
		}
		if (self::ForeignTableIsMmTable($table, $property)) {
			$foreign_table = self::getColumnConfig($table, $property, 'foreign_table'); 
			$foreign_field = self::getColumnConfig($table, $property, 'foreign_mm_field');
			return self::getClassname($foreign_table, $foreign_field);
		} else {
			return self::getClassname($table, $property);
		}
	}
	
	
	
	/**
	 * Merge a group of arrays
	 *
	 * @see 	am() from CakePHP
	 * @param 	array 	First array
	 * @param 	array 	Second array
	 * @param 	array 	Third array
	 * @param 	array 	Etc...
	 * @return 	array 	All array parameters merged into one
	 */
	public static function am() {
		$r = array();
		foreach (func_get_args()as $a) {
			if (!is_array($a)) {
				$a = array($a);
			}
			$r = array_merge($r, $a);
		}
		return $r;
	}
	
	
	
	/**
	 * Clear caches "pages", "all", "temp_CACHED" or numeric'
	 *
	 * @param 	mixed	
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-05-15
	 * @see 	t3lib_TCEmain::clear_cacheCmd
	 */
	public static function clearCache($cacheCmd = 'all') {
		
		if (!t3lib_div::testInt($cacheCmd) && !in_array($cacheCmd, array('pages', 'all', 'temp_CACHED'))) {
			throw tx_pttools_exception('Parameter must be "pages", "all", "temp_CACHED" or numeric'); 
		}
		
	    $tce = t3lib_div::makeInstance('t3lib_TCEmain'); /* @var $tce t3lib_TCEmain */
	    $tce->stripslashes_values = 0;
	    $tce->start(Array(),Array());
	    $tce->clear_cacheCmd($cacheCmd);
	}


	/**
	 * Get the foreign table name for a given property of a table
	 *
	 * @param 	string	table name
	 * @param 	string	property name
	 * @return 	string	foreign table name
	 */
	/*
	public static function getForeignTableName($tablename, $property) {
		t3lib_div::loadTCA($tablename);

		$foreignTablename = '';
		if (self::isMmTable($tablename)) {
			$foreignfield = $this->_properties[$property]['config']['foreign_mm_field'];
			$foreignTablename = $GLOBALS['TCA'][$tablename]['columns'][$foreignfield]['config']['foreign_table'];
		} else {
			// 1:m
			// TODO: macht keinen SInn,...
			$foreignTablename = $tablename;
		}
		if (empty($foreignTablename)) {
			throw new tx_pttools_exception('Foreign table was not defined correctly for foreign table "' . $tablename . '" of property "' . $property . '" (class: "' . get_class($this) . '")', 0);
		}
		return $foreignTablename;
	}
 	*/
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_div.php']);
}

?>