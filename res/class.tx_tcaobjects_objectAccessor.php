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
 * Object Accessor base class
 *
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 */
class tx_tcaobjects_objectAccessor {

    /**
     * Select object data by its uid
     *
     * @param 	int		uid
     * @param	string	table
     * @param	bool	(optional) ignore enable fields
     * @return 	array	dataArray
     * @throws  tx_pttools_exception	if query fails
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public static function selectByUid($uid, $table, $ignoreEnableFields = false){
        // query preparation
        $select  = '*';
        $from    = $table;
        $where   = 'uid = '.intval($uid);
        if (!$ignoreEnableFields) $where .= ' '.tx_pttools_div::enableFields($from);
        $groupBy = '';
        $orderBy = '';
        $limit   = '';

        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        tx_pttools_assert::isMySQLRessource($res);
        
        $a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        return $a_row;
    }



    /**
     * Selects object data by parent uid
     *
     * @param 	int		parent uid
     * @param 	string	foreign table
     * @param 	string	foreign field
     * @param 	string	foreign sortby
     * @param 	string	(optional) additional where clause
     * @return 	array	array of dataArrays
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-03-16
     */
    public static function selectByParentUid($parentUid, $foreign_table, $foreign_field, $foreign_sortby, $additionalWhere = ''){
    	
    	tx_pttools_assert::isValidUid($parentUid);
    	tx_pttools_assert::isNotEmptyString($foreign_table);
    	tx_pttools_assert::isNotEmptyString($foreign_field);
    	
        // query preparation
        $select  = '*';
        $from    = $foreign_table;
        $where   = $foreign_field .' = '.intval($parentUid);
        $where .= ' '.tx_pttools_div::enableFields($from);
        if (($languageField = tx_tcaobjects_div::getLanguageField($foreign_table)) == true) {
        	$where .= ' AND ' . $foreign_table . '.' . $languageField . ' in (0,-1)';
        }
        if (!empty($additionalWhere)) {
        	$where .= ' AND '.$additionalWhere;
        }
        
        $groupBy = '';
        $orderBy = $foreign_sortby;
        $limit   = '';

        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        tx_pttools_assert::isMySQLRessource($res);
        
        $rows = array();
        while (($a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) == true) {
            $rows[] = $a_row;
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        return $rows;
    }

    
    
    /**
     * Select translation record
     * 
     * @param string $table
     * @param string $transOrigPointerField
     * @param int $origUid
     * @param string $languageField
     * @param int $sysLanguageUid
     * @param string $select (optional)
     * @return array row
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-03-16
     */
    public function selectTranslation($table, $transOrigPointerField, $origUid, $languageField, $sysLanguageUid, $select='*') {
    	// query preparation
        $from   = $table;
        $where  = $transOrigPointerField . ' = '.intval($origUid);
        $where .= ' AND ' . $languageField . ' = ' . intval($sysLanguageUid);
        $where .= ' '.tx_pttools_div::enableFields($from);

        $groupBy = '';
        $limit   = '';

        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        tx_pttools_assert::isMySQLRessource($res);
        
        $a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        return $a_row;
    }


    /**
     * Select collection
     *
     * @param 	string	table
     * @param 	string	(optional) where
     * @param 	string	(optional) limit
     * @param 	string	(optional) orderBy
     * @param	bool	(optional) ignore enable fields, default: false
     * @param	string	(optional) select fields
     * @return	array 	array of data arrays
     * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public function selectCollection($table, $where = '', $limit = '', $orderBy = '', $ignoreEnableFields = false, $select='*') {
        // query preparation
        $from    = $table;
        $where   = ($where != '') ? $where : '1';
        if (!$ignoreEnableFields) {
            $where .= ' '.tx_pttools_div::enableFields($from);
        }
        $groupBy = '';
        // $orderBy = '';
        // $limit   = '';

        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        tx_pttools_assert::isMySQLRessource($res);
        
        // if (TYPO3_DLOG) t3lib_div::devLog($GLOBALS['TYPO3_DB']->debug_lastBuiltQuery, 'TYPO3_DB', 0);
        
        $rows = array();
        while (($a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) == true) {
            $rows[] = $a_row;
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        return $rows;
    }



    /**
     * Count collection items
     *
     * @param 	string	table
     * @param 	string	(optional) where
     * @param	bool	(optional) ignore enable fields, default: false
     * @return	array 	array of data arrays
     * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public function selectCollectionCount($table, $where = '', $ignoreEnableFields = false) {

        // query preparation
        $select  = 'count(*) as quantity';
        $from    = $table;
        $where   = ($where != '') ? $where : '1';
        if (!$ignoreEnableFields) {
            $where .= ' '.tx_pttools_div::enableFields($from);
        }
        $groupBy = '';
        $orderBy = '';
        $limit   = '';

        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        tx_pttools_assert::isMySQLRessource($res);

        $a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        return $a_row['quantity'];
    }



    /**
     * Stores record to an table
     *
     * @param 	string	table
     * @param 	array	dataArray
     * @return 	int		uid of the record
     */
    public function store($table, array $dataArray) {
        if (isset($dataArray['uid'])) {
            return self::updateExistingRecord($table, $dataArray);
        } else {
        	return self::insert($table, $dataArray);
        }
    }



    /**
     * Insert a new record
     *
     * @param 	string	table
     * @param 	array 	data
     * @return 	int		uid of the new record
     * @throws	tx_pttools_exception	if insert fails
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public function insert($table, array $insertFieldsArr) {

    	// check if all values are scalar or null
    	foreach($insertFieldsArr as $key => $value) {
    		if (!(is_scalar($value) || is_null($value))) {
    			throw new tx_pttools_exception(sprintf('Value in key "%s" is not scalar but "%s"!', $key, gettype($value)));
    		}
    	}

        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $insertFieldsArr);
        tx_pttools_assert::isMySQLRessource($res);

        $lastInsertedId = $GLOBALS['TYPO3_DB']->sql_insert_id();

        return $lastInsertedId;
    }



    /**
     * Delete record
     *
     * @param 	string	table
     * @param 	int		uid
     * @return 	void
     * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public function delete($table, $uid) {
        self::deleteWhere($table, 'uid = '.intval($uid));
    }



    /**
     * ATTENTION: If ($where==true) the whole table will be deleted!
     *
     * @param 	string	table
     * @param 	string	where
     */
    public function deleteWhere($table, $where) {
        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_DELETEquery($table, $where);
        tx_pttools_assert::isMySQLRessource($res);
    }



    /**
     * Updates an existing record
     *
     * @param 	string	table
     * @param 	array 	data (data['uid'] contains the uid of the record to update)
     * @return 	int		uid of the updated record
     * @throws	tx_pttools_exception if uid is empty
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public function updateExistingRecord($table, array $updateFieldsArr) {

        if (empty($updateFieldsArr['uid'])) {
            throw new tx_pttools_exception('No uid set (needed for updating the record)!');
        }

        $where = 'uid = '.intval($updateFieldsArr['uid']);

        self::updateTable($table, $where, $updateFieldsArr);

        return $updateFieldsArr['uid'];
    }



    /**
     * Updates a table
     *
     * @param 	string	table name
     * @param 	string	where clause
     * @param 	array 	update fields
     * @return 	void
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-10-27
     */
    public function updateTable($table, $where, array $updateFieldsArr) {

    	// exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $updateFieldsArr);
        tx_pttools_assert::isMySQLRessource($res);
    }



    /**
     * Return the positioning parameter for t3lib_TCEmain::getSortNumber() to move a record one position up
     *
     * @param 	string	table
     * @param 	int		pid
     * @param 	int		uid
     * @param 	string	(optional) sorting field name, default: sorting
     * @return 	mixed	int with the positioning parameter, false if the record is already the last
     */
    public static function selectMoveOneUpPosition($table, $pid, $uid, $sortingFieldName = 'sorting') {
    	// TODO: is the pid parameter really needed here?

		// query preparation
        $select  = 'b.uid as position';
        $from    = $table.' as a, '.$table.' as b';
        $where   = 'a.uid = '.intval($uid);
        $where  .= ' AND a.pid = '.intval($pid);
        $where  .= ' AND a.pid = b.pid';
        $where  .= ' AND a.'.$sortingFieldName.' >= b.'.$sortingFieldName;
        $where  .= ' '.tx_pttools_div::enableFields($table, 'a');
        $where  .= ' '.tx_pttools_div::enableFields($table, 'b');
        $groupBy = '';
        $orderBy = 'b.'.$sortingFieldName.' desc';
        $limit   = '2,1';

        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        tx_pttools_assert::isMySQLRessource($res);

        $a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        if (empty($a_row['position'])) {
        	// TODO: get the pid from the uid
        	// or even return false, because it is already the first one?
        	$position = $pid;
        } else {
        	$position = '-'.(string)$a_row['position'];
        }

        return $position;

    }



    /**
     * Return the positioning parameter for t3lib_TCEmain::getSortNumber() to move a record one position down
     *
     * @param 	string	table
     * @param 	int		pid
     * @param 	int		uid
     * @param 	string	(optional) sorting field name, default: sorting
     * @return 	mixed	int with the positioning parameter, false if the record is already the last
     */
    public static function selectMoveOneDownPosition($table, $pid, $uid, $sortingFieldName = 'sorting') {
		// TODO: is the pid parameter really needed here?

		// query preparation
        $select  = 'b.uid as position';
        $from    = $table.' as a, '.$table.' as b';
        $where   = 'a.uid = '.intval($uid);
        $where  .= ' AND a.pid = '.intval($pid);
        $where  .= ' AND a.pid = b.pid';
        $where  .= ' AND a.'.$sortingFieldName.' <= b.'.$sortingFieldName;
        $where  .= ' '.tx_pttools_div::enableFields($table, 'a');
        $where  .= ' '.tx_pttools_div::enableFields($table, 'b');
        $groupBy = '';
        $orderBy = 'b.'.$sortingFieldName.' asc';
        $limit   = '1,1';

        // exec query using TYPO3 DB API
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $from, $where, $groupBy, $orderBy, $limit);
        tx_pttools_assert::isMySQLRessource($res);
        
        $a_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        if (empty($a_row['position'])) {
        	$position = false;
        } else {
        	$position = '-'.(string)$a_row['position'];
        }

        return $position;
    }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_objectAccessor.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_objectAccessor.php']);
}

?>