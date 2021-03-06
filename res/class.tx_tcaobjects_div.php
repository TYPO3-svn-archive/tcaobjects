<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Fabrizio Branca (mail@fabrizio-branca.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once PATH_t3lib . 'class.t3lib_tstemplate.php';

class tx_tcaobjects_div {

	/**
	 * @var bool	enable time tracking for the autoloader
	 */
	public static $trackTime = false;

	/**
	 * @var array	lookup table: array('condensedExtKey' => 'full_extKey') for method getExtKeyFromCondensendExtKey
	 */
	public static $extKeyLookupTable = array();
	
	/**
	 * @var array
	 */
	public static $fieldsForTypeValueCache = array();

	
	
	/**
	 * Get all available fields for a given type value
	 * 
	 * @param string $typeValue
	 * @param string $table
	 * @return array available fields
	 * @author Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 2010-05-06 (<- Dirk's birthday)
	 */
    public static function getFieldsForTypeValue($typeValue, $table) {
    	if (!isset(self::$fieldsForTypeValueCache[$table.','.$typeValue])) {
	    	t3lib_div::loadTCA($table);
	    	$typeDefinition = $GLOBALS['TCA'][$table]['types'][$typeValue]['showitem'];
	    	if (empty($typeDefinition)) {
	    		throw new tx_pttools_exception(sprintf('No type definition found in TCE for type "%s"', $typeDefinition));
	    	}
	    	$fieldConfigurations = t3lib_div::trimExplode(',', $typeDefinition, true);
	    	$rawFields = array();
	    	foreach ($fieldConfigurations as $fieldConfiguration) {
	    		$rawField = t3lib_div::trimExplode(';', $fieldConfiguration);
	    		if ($rawField[0] == '--palette--') {
	    			$paletteDefinition = $GLOBALS['TCA'][$table]['palettes'][$rawField[2]]['showitem'];
	    			$paletteFieldsConfigurations = t3lib_div::trimExplode(',', $paletteDefinition);
	    			foreach ($paletteFieldsConfigurations as $paletteFieldsConfiguration) {
	    				$rawPaletteField = t3lib_div::trimExplode(';', $paletteFieldsConfiguration);
			    		if (substr($rawPaletteField[0], 0, 1) != '-') {
			    			$rawFields[] = $rawPaletteField[0];
			    		}		
	    			}
	    		}
	    		if (substr($rawField[0], 0, 1) != '-') {
	    			$rawFields[] = $rawField[0];
	    		}
	    	}
	    	self::$fieldsForTypeValueCache[$table.','.$typeValue] = $rawFields;
	    	
    	}
    	return self::$fieldsForTypeValueCache[$table.','.$typeValue];
    }
	
	
	/**
	 * Get file extension from filename
	 * 
	 * @param string $fileName
	 * @return string file extension
	 */
	public static function getFileExtension($fileName) {
		$info = pathinfo($fileName);
		return $info['extension'];
	}
	
	
	/**
	 * Create save file name (without check if this file already exists
	 * 
	 * @param string original filename
	 * @return string save filename
	 */
	public static function createSaveFileName($name, $postFix='') {
		
		$name = $GLOBALS['TSFE']->csConvObj->specCharsToASCII($GLOBALS['TSFE']->renderCharset, $name);
		$name = strtolower($name);
		
		$info = pathinfo($name);
		$fileName = $info['filename'];
		$fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
		
		return $fileName . $postFix . '.' . $info['extension'];
	}
	
	
	/**
	 * Get TCA ctrl setting
	 * 
	 * @param string $table
	 * @param string $key
	 * @return mixed value
	 */
	public static function getCtrl($table, $key) {
		tx_pttools_assert::isNotEmptyString($table, array('message' => 'No table given'));
		tx_pttools_assert::isNotEmptyString($key, array('message' => 'No key given'));
		return $GLOBALS['TCA'][$table]['ctrl'][$key];
	}
	
	
	
	/**
	 * Get language field for table
	 * 
	 * @param string $table
	 * @return string|false
	 * @author Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 2010-03-17
	 */
	public static function getLanguageField($table) {
		tx_pttools_assert::isNotEmptyString($table, array('message' => 'No table given'));
		return isset($GLOBALS['TCA'][$table]['ctrl']['languageField']) ? $GLOBALS['TCA'][$table]['ctrl']['languageField'] : false;
	}

	/**
	 * Get transOrigPointer field
	 * 
	 * @param string $table
	 * @return string fieldname
	 * @author Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 2010-03-26
	 */
    public function getTransOrigPointerField($table) {
    	tx_pttools_assert::isNotEmptyString($table, array('message' => 'No table given'));
    	return $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
    }
	
	
	
	/**
	 * Check if a table can be translated (by checking if the languageField is set)
	 * 
	 * @param string $table table name
	 * @return bool true if table can be translated
	 * @author Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 2010-03-17
	 */
	public static function supportsTranslations($table) {
		return (self::getLanguageField($table) !== false);
	}
	
	
	
	/**
	 * Parse query
	 * 
	 * @param $url
	 * @return array
	 */
	public static function parse_query($url) {
		$var = parse_url($url, PHP_URL_QUERY);
		$var = html_entity_decode($var);
		$var = explode('&', $var);
		$arr = array();

		foreach($var as $val) {
			$x = explode('=', $val);
			$arr[$x[0]] = $x[1];
   		}
		unset($val, $x, $var);
		return $arr;
	}
	
	/**
	 * Returns the configured sorting field for a table
	 * 
	 * @param string $table
	 * @return string sorting field
	 */
	public static function getSortingField($table) {
		t3lib_div::loadTCA($table);
		tx_pttools_assert::isNotEmptyArray($GLOBALS['TCA'][$table], array('message' => sprintf('No TCA found for table "%s"', $table)));
		return $GLOBALS['TCA'][$table]['ctrl']['sortby'];
	}
	

	
	/**
	 * Create a random string
	 * 
	 * @param int $length (optional) length
	 * @param bool $alphaLower (optional) use lowercase letters
	 * @param bool $alphaUpper (optional) use uppercase letters
	 * @param bool $num (optional) use digits
	 * @return string 
	 */
	public static function createRandomString($length=10, $alphaLower=true, $alphaUpper=true, $num=true) {
		$chars = array();
		if ($alphaLower) {
			$chars = array_merge($chars, range('a', 'z'));
		}
		if ($alphaUpper) {
			$chars = array_merge($chars, range('A', 'Z'));
		}
		if ($num) {
			$chars = array_merge($chars, range('0', '9'));
		}
		for ($i=0; $i<$length; $i++) {
			$randomString .= $chars[array_rand($chars)];
		}
		return $randomString;
	}
	
	
	/**
	 * Retrieves the table name a tcaobject class is mapped to
	 * 
	 * @param string $className
	 * @return string table name
	 * @author Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 2009-11-24
	 */
	public static function getTableNameForClassName($className) {
		static $cache = array();
		
		if (empty($cache[$className])) {
			if (!class_exists($className)) {
				throw new tx_pttools_exception(sprintf('Class "%s" not found!', $className));
			}
			$tmp = new $className();
			$cache[$className] = $tmp->getTable();
		}
		
		return $cache[$className];
	}

	
	
	/**
	 * Extracts a (deep) property from an object
	 * E.g. 'foo|bar|hello|world' extracts $object['foo']['bar']['hello']['world']
	 * 
	 * @param array|ArrayAccess
	 * @param string propertyname
	 * @return mixed property value
	 */
	public static function extractProperty($object, $propertyName) {
		$propertyParts = t3lib_div::trimExplode('|', $propertyName);

		$tmp = $object;
		foreach ($propertyParts as $part) {
			if (!is_array($tmp) && !($tmp instanceof ArrayAccess)) {
				throw new tx_pttools_exception('Item must be an array or implement the ArrayAccess interface!');
			}
			$tmp = $tmp[$part];
		}
		return $tmp;
	}
	
	
	
	/**
	 * Redirect function that can be called via typoscipt
	 * 
	 * @param string content
	 * @param array configuration
	 * @return void (never returns)
	 */
	public function typoscriptRedirectUserFunction($content, array $conf = array()) {
		tx_pttools_div::localRedirect($content);
	}
	
	
	/**
	 * Returns the full extension key from the condensed extension key (with caching)
	 *
	 * @param 	string	condensedExtKey
	 * @return 	mixed	full_extKey or false if no extKey found
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-09 (rewritten 2009-11-24)
	 */
	public static function getExtKeyFromCondensendExtKey($condensedExtKey) {
		if (!is_string($condensedExtKey) || empty($condensedExtKey)) {
			// pt_tools classes cannot be used here because they might not be loaded yet...
			//throw new InvalidArgumentException('Invalid condensed extension key.');
			return false;
		}
		// tx_pttools_assert::isNotEmptyString($condensedExtKey);
		static $cache = array();
		if (empty($cache[$condensedExtKey])) {
			$foundKey = false;
			$extKeys = t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXT']['extList']);
			foreach ($extKeys as $extKey) {
				if ($condensedExtKey == str_replace('_', '', $extKey)) {
					$foundKey = true;
					break;
				}
			}
			$cache[$condensedExtKey] = $foundKey ? $extKey : false;
		}
		return $cache[$condensedExtKey];
	}



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
			$classname = self::getColumnConfig($callingtable, $callingproperty, 'allowed');
		}
		if (empty($classname)) {
			$classname = self::getColumnConfig($callingtable, $callingproperty, 'foreign_table');
		}
		if (empty($classname)) {
			$classname = $callingproperty;
		}
		if (!class_exists($classname)) {
			throw new tx_pttools_exception(sprintf('Target class "%s" for property "%s" in table "%s" does not exist!', $classname, $callingproperty, $callingtable));
		}
		return $classname;
	}
	
	
	
	/**
	 * Get table name for classname 
	 *  
	 * @param string class name
	 * @return string table name
	 */
	public static function getTableName($className) {
		static $cache=array();
		if (isset($cache[$className])) {
			return $cache[$className];
		}
		
		$tmpObj = new $className; /* @var $tmpObj tx_tcaobjects_object */
		tx_pttools_assert::isInstanceOf($tmpObj, 'tx_tcaobjects_object');
		$tableName = $tmpObj->getTable();
		
		$cache[$className] = $tableName;
		
		return $tableName;
	}
	
	
	
	/**
	 * Returns the classname of the object collection for a given class
	 * 
	 * @param string $className
	 * @return string collection class name
	 * @author Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 2010-03-26
	 */
	public static function getCollectionClassName($className) {
		tx_pttools_assert::isNotEmptyString($className);
		
		// create object collection
        $collectionClassName = $className . 'Collection';

        // Fallback if collection class does not exist
        $collectionClassName = class_exists($collectionClassName) ? $collectionClassName : 'tx_tcaobjects_objectCollection';
		
		return $collectionClassName;
	}



	/**
	 * Get the config for a property from the TCA
	 *
	 * @param 	string		table name
	 * @param 	string		property name
	 * @param 	string		config element
	 * @return 	string		configuration
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-04-27
	 */
	public static function getColumnConfig($table, $column, $config) {
		tx_pttools_assert::isNotEmptyString($table);
		tx_pttools_assert::isNotEmptyString($column);
		tx_pttools_assert::isNotEmptyString($config);
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
			//TODO see pt_tool smarty!
			$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
			$GLOBALS['LANG']->csConvObj = t3lib_div::makeInstance('t3lib_cs');
		}
		if (is_object($GLOBALS['TSFE']) && $GLOBALS['TSFE']->config['config']['language']) {
			$LLkey = $GLOBALS['TSFE']->config['config']['language'];
			$GLOBALS['LANG']->init($LLkey);
		}

	}


	/**
	 * Add or remove slash at the beginning or the end of a string
	 *
	 * @param	string	string
	 * @param 	int		(optional) 1: add slash, -1: remove slash, 0: do nothing at the end
	 * @param 	int		(optiomal) 1: add slash, -1: remove slash, 0: do nothing at the beginning
	 * @return 	string	string
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-09
	 */
	public static function slashes($string, $end = 0, $beginning = 0) {

		if ($beginning == 1 && substr($string, 0, 1) != '/') {
			$string = '/'.$string;
		} elseif ($beginning == -1 && substr($string, 0, 1) == '/') {
			$string = substr($string, 1);
		}

		if ($end == 1 && substr($string, -1) != '/') {
			$string = $string.'/';
		} elseif ($end == -1 && substr($string, -1) == '/') {
			$string = substr($string, 0, -1);
		}

		return $string;
	}



	/**
	 * Generates a random hash
	 *
	 * @return 	string	random hash
	 * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 	2007-10-17
	 */
	public static function makeRandomHash($cut = 0){
		$hash =  md5(uniqid(rand()));
		if ($cut > 0) {
			$hash = substr($hash, 0, $cut);
		}
		return $hash;
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

		$condensedExtKey = self::getCondensedExtKeyFromClassName($className);
		
		if (empty($condensedExtKey)) {
			return;
		}
		
		$extKey = tx_tcaobjects_div::getExtKeyFromCondensendExtKey($condensedExtKey);
		
		if (empty($extKey)) {
			return;
		}
		
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tcaobjects']['autoloader'][$extKey])) {

			$validPath = false;

			// read parameters from configuration
			$classPaths = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tcaobjects']['autoloader'][$extKey]['classPaths'];
			$partsMatchDirectories = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tcaobjects']['autoloader'][$extKey]['partsMatchDirectories'];
			$classMap = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tcaobjects']['autoloader'][$extKey]['classMap'];
			$basePath = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tcaobjects']['autoloader'][$extKey]['basePath'];
				if (empty($basePath)) {
					$basePath = 'EXT:'.$extKey.'/';
				}
				$basePath = self::slashes($basePath, 1); // add trailing slash

			// look in classMap first
			if (!empty($classMap[$className])) {
				// remove slash at the beginning
				$classMap[$className] = self::slashes($classMap[$className], 0, -1);
				$path = $basePath . $classMap[$className];
				$path = t3lib_div::getFileAbsFileName($path);
				if (t3lib_div::validPathStr($path) && file_exists($path)) {
					$validPath = $path;
				}
			}

			// look for partsMatchDirectories
			if (!$validPath && $partsMatchDirectories == true) {
				$classNameParts = explode('_', $className);
				$path = $basePath . implode('/', array_slice($classNameParts, 2, -1)) . '/class.' . $className . '.php';
				$path = t3lib_div::getFileAbsFileName($path);
				if (t3lib_div::validPathStr($path) && file_exists($path)) {
					$validPath = $path;
				}
			}

			// loop through classPaths to find the file
			if (!$validPath) {
				if (!is_array($classPaths)) {
					$classPaths = array();
				}

				array_unshift($classPaths, ''); // append empty classPath (that means: look in the basePath first!)

				reset($classPaths);
				while (!$validPath && list( ,$classPath) = each($classPaths)) {

					// add slash at the end and remove it from the beginning
					$classPath = self::slashes($classPath, 1, -1);

					$path = $basePath . $classPath . 'class.' . $className . '.php';
					$path = t3lib_div::getFileAbsFileName($path);
					if (t3lib_div::validPathStr($path) && file_exists($path)) {
						$validPath = $path;
					}
				}

			}


			// require_once the path finally if found
			if ($validPath) {
				require_once $validPath;

				if (self::$trackTime && ($GLOBALS['TT'] instanceof t3lib_timeTrack)) {
					$GLOBALS['TT']->setTSlogMessage('"'.$className.'" found in "'.str_replace(PATH_site, '', $validPath).'"', 0);
					$GLOBALS['TT']->pull();
				}
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

		require_once PATH_t3lib . 'class.t3lib_userauth.php';
		require_once PATH_t3lib . 'class.t3lib_userauthgroup.php';
		require_once PATH_t3lib . 'class.t3lib_befunc.php';

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
	public static function renderContent($uid, $table='tt_content') {
		$lConf = array('tables' => $table, 'source' => $uid);
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
		$day = max($value['D'], $value['l'], $value['d'], 1);
		$month = max($value['M'], $value['F'], $value['m'], 1);
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
	public static function stdWrapArray(array $data, tslib_cObj $cObj=NULL) {

		if (is_null($cObj)) {
			$cObj = $GLOBALS['TSFE']->cObj;
		}

		$newData = array();
		foreach (array_keys($data) as $key) {
			if (substr($key, -1) != '.') {
				if (empty($newData[$key])) {
					$newData[$key] = $cObj->stdWrap($data[$key], $data[$key.'.']);
				}
			} else {
				if (empty($newData[substr($key, 0, -1)])) {
					$newData[substr($key, 0, -1)] = $cObj->stdWrap($data[substr($key, 0, -1)], $data[$key]);
				}
			}
		}
		return $newData;
	}



	/**
	 * Converts a typoscript quickform definition into a definition string
	 *
	 * <FORMDEFINITION> {
	 * 		10 = <ELEMENT-OR-GROUP>
	 * 		20 = <ELEMENT-OR-GROUP>
	 * }
	 *
	 * <ELEMENT> {
	 * 		property = <stdWrap>
	 * 		altLabel = <stdWrap>
	 * 		specialtype = <stdWrap>
	 * 		content = <stdWrap>
	 * 		rules {
	 * 			rule = message <stdWrap>
	 * 			ruleWithoutMessage
	 * 		}
	 * 		attributes {
	 * 			key = value <stdWrap>
	 * 			keyWithoutValue
	 * 		}
	 * 		altType = <type> <- this overrides the type configured in TCA
	 * 		comment = <stdWrap>
	 * 		wrap = <stdWrap> // TODO
	 * }
	 *
	 * <GROUP> {
	 * 		name =
	 * 		label =
	 * 		separator =
	 * 		elements = <FORMDEFINITION>
	 * }
	 *
	 * @param 	array 	typoscript form definition
	 * @return 	string	quickform definition as string
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-26
	 */
	public static function tsToQuickformString(array $conf) {

		tx_pttools_assert::isNotEmptyArray($conf, array('message' => 'Typoscript configuration has to be an array that is not empty!'));

		// TODO: groups!
		$quickform = array();
		uksort($conf, 'strnatcmp'); // this is the missing knatsort

		foreach ($conf as $fieldconf) { /* @var $fieldconf array */
			
			tx_pttools_assert::isNotEmptyArray($fieldconf, array('message' => 'Field configuration has to be an array that is not empty!'));

			$rulesTmp = $fieldconf['rules.'];
			$attrTmp = $fieldconf['attributes.'];
			$elementsTmp = $fieldconf['elements.'];
			$fieldconf = self::stdWrapArray($fieldconf);
			if (is_array($rulesTmp)) {
				$fieldconf['rules.'] = self::stdWrapArray($rulesTmp);
				foreach ($fieldconf['rules.'] as $key => &$value) {
					$value = urlencode($value);
				}
			}
			if (is_array($attrTmp)) {
				$fieldconf['attributes.'] = self::stdWrapArray($attrTmp);
			}
			$fieldconf['elements.'] = $elementsTmp;

			if (is_array($fieldconf['elements.'])) {
				// processing the group...
				$parts = array(
					$fieldconf['name'],
					$fieldconf['label'],
					$fieldconf['separator'],
					self::tsToQuickformString($fieldconf['elements.'])
				);
				$quickform[] = '['.implode('|', $parts).']';
			} else {

				// Rules
				$fieldRules = array();
				foreach ((array)$fieldconf['rules.'] as $rule => $message) {
					// TODO: urlencode messages?
					$fieldRules[] = $rule.(!empty($message) ? '='.$message : '');
				}
				$fieldRules = implode(':', $fieldRules);

				// Attributes
				$fieldAttributes = array();
				foreach ((array)$fieldconf['attributes.'] as $attributeKey => $attributeValue) {
					// TODO: urlencode attributeValues?
					$fieldAttributes[] = $attributeKey.(!empty($attributeValue) ? '='.$attributeValue : '');
				}
				$fieldAttributes = implode(':', $fieldAttributes);

				// urlencode text values
				
				$fieldconf['comment'] = $GLOBALS['TSFE']->cObj->stdWrap($fieldconf['comment'], $fieldconf['comment.']);
				
				$fieldconf['comment'] = urlencode($fieldconf['comment']);
				$fieldconf['content'] = urlencode($fieldconf['content']);

				$parameterArray = array(
					$fieldconf['property'], 
					$fieldconf['altLabel'], 
					$fieldconf['specialtype'], 
					$fieldconf['content'], 
					$fieldRules, 
					$fieldAttributes, 
					$fieldconf['comment'],
					$fieldconf['altType']
				);
				$quickform[] = rtrim(implode(';', $parameterArray),';');
			}
		}
		return implode(',', $quickform);
	}

	/*
	public static function getDefaultCancelSaveButtons() {
		$buttonGroup = array();
		$buttonGroup[] = ';LLL:EXT:scrbl/locallang.xml:form.save;submit;save';
		$buttonGroup[] = ';LLL:EXT:scrbl/locallang.xml:form.cancel;submit;cancel';
		return '[|||'.implode(',',$buttonGroup).']';
	}
*/


	/**
	 * Checks if the foreign table of a given property/column is a mm table
	 *
	 * @param 	string	tablename
	 * @return 	bool
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-02-21
	 */
	public static function ForeignTableIsMmTable($table, $property) {

		tx_pttools_assert::isNotEmptyString($table, array('message' => 'Parameter "table" empty!'));
		tx_pttools_assert::isNotEmptyString($property, array('message' => 'Parameter "property" empty!'));

		if (self::getColumnConfig($table, $property, 'foreign_table') && self::getColumnConfig($table, $property, 'foreign_mm_field') ) {
			return true;
		} else {
			return false;
		}
	}


	
	
	/**
	 * Get the foreign class name
	 * 
	 * @param string $table
	 * @param string $property
	 * @return string classname
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	public static function getForeignClassName($table, $property) {

		tx_pttools_assert::isNotEmptyString($table, array('message' => 'Parameter "table" empty!'));
		tx_pttools_assert::isNotEmptyString($property, array('message' => 'Parameter "property" empty!'));

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
	 * Includes full TCA.
	 * Normally in the frontend only a part of the global $TCA array is loaded, for instance the "ctrl" part. Thus it doesn't take up too much memory.
	 * If you need the FULL TCA available for some reason (like plugins using it) you should call this function which will include the FULL TCA.
	 * Global vars $TCA, $PAGES_TYPES, $LANG_GENERAL_LABELS can/will be affected.
	 * The flag $this->TCAloaded will make sure that such an inclusion happens only once since; If $this->TCAloaded is set, nothing is included.
	 *
	 * @param	boolean		Probably, keep hands of this value. Just don't set it. (This may affect the first-ever time this function is called since if you set it to zero/false any subsequent call will still trigger the inclusion; In other words, this value will be set in $this->TCAloaded after inclusion and therefore if its false, another inclusion will be possible on the next call. See ->getCompressedTCarray())
	 * @return	void
	 * @see getCompressedTCarray()
	 */
	public static function includeTCA($TCAloaded=1)	{

		if (is_object($GLOBALS['TSFE'])) {
			$GLOBALS['TSFE']->includeTCA($TCAloaded);
		} else {

			global $TCA, $PAGES_TYPES, $LANG_GENERAL_LABELS, $TBE_MODULES;

			$TCA = Array();
			include (TYPO3_tables_script ? PATH_typo3conf.TYPO3_tables_script : PATH_t3lib.'stddb/tables.php');
				// Extension additions
			if ($GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'])	{
				include(PATH_typo3conf.$GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'].'_ext_tables.php');
			} else {
				include(PATH_t3lib.'stddb/load_ext_tables.php');
			}
				// ext-script
			if (TYPO3_extTableDef_script)	{
				include (PATH_typo3conf.TYPO3_extTableDef_script);
			}

		}
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