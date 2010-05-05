<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Fabrizio Branca (mail@fabrizio-branca.de)
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


/**
 * class tx_tcaobjects_objectRepository
 *
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2010-04-21
 */
class tx_tcaobjects_genericRepository {
	
	protected static $valueObjects = array();

	/**
	 * Return object 
	 * 
	 * @param string $className
	 * @param int $uid
	 */
	public static function getObjectByUid($className, $uid) {
		tx_pttools_assert::isNotEmptyString($className);
		tx_pttools_assert::isValidUid($uid);
		
		$tableName = tx_tcaobjects_div::getTableName($className);
		if (tx_tcaobjects_div::getCtrl($tableName, 'valueObject')) {
			$hash = $className.'_'.$uid;
			if (isset(self::$valueObjects[$hash])) {
				$object = self::$valueObjects[$hash];
			} else {
				$object = new $className($uid);
				self::$valueObjects[$hash] = $object;
			}
		} else {
			$object = new $className($uid);
		}
		tx_pttools_assert::isInstanceOf($object, $className);
		return $object;
	}
	
	public static function findOneBy($property, $value, $className) {
		
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_object.php'])	{
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_object.php']);
}

?>