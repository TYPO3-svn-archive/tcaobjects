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


/**
 * Abstract class tx_tcaobjects_objectRepository
 * This is the case class for all tcaobjects repositories
 *
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2009-11-23
 */
abstract class tx_tcaobjects_objectRepository {
	
	/**
	 * @var string tablename
	 */
	protected $table;

	/**
	 * @var string class name of the tcaobject
	 */
	protected $tcaObjectName = '';

	/**
	 * Get the class name of the tcaobjects this repository will handle
	 *
	 * @return 	string	class name
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2009-11-23
	 */
	protected function getClassName() {
		if (empty($this->tcaObjectName)) {
			// assuming that "fooRepository" contains "foo" objects
			$this->tcaObjectName = str_replace('Repository', '', get_class($this)); 
		}
		return $this->tcaObjectName;
	}



	/**
	 * Get table name
	 *
	 * @return 	string	table name
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2009-11-23
	 */
	protected function getTable() {
		if ($this->table == '') {
			$this->table = tx_tcaobjects_div::getTableNameForClassName($this->getClassName());
		}
		return $this->table;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_object.php'])	{
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_object.php']);
}

?>