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
 * Class "tx_tcaobjects_fe_usersAccessor"
 * 
 * $Id: class.tx_tcaobjects_fe_usersAccessor.php,v 1.3 2008/05/12 13:59:36 ry44 Exp $
 * 
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2008-03-21
 */
class tx_tcaobjects_fe_usersAccessor extends tx_tcaobjects_objectAccessor  {

    static protected $_table = 'fe_users';

    /**
     * Select project data
     *
     * @param 	string	(optional) sql where
     * @param 	string	(optional) sql limit
     * @return 	array	array of dataArrays
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public function selectUsers($where = '', $limit = '') {
        return parent::selectCollection(self::$_table, $where, $limit);
    }


    /**
     * Select object data by its uid (by calling parents selectByUid method)
     *
     * @param 	int		uid
     * @param	bool	(optional) ignore enable fields
     * @return 	array	dataArray
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public static function selectByUid($uid, $ignoreEnableFields = false){
        return parent::selectByUid($uid, self::$_table, $ignoreEnableFields);
    }


    /**
     * Returns object data by uid
     *
     * @param 	string	mail address
     * @return 	array	array of user records (usually only 1 item)
     * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 	2008-02-10
     */
    public static function selectByMail($mail) {
        if (!t3lib_div::validEmail($mail)) {
            throw new tx_pttools_exception('"'.$mail.'" is no valid mail address');
        }
        $mail = $GLOBALS['TYPO3_DB']->fullQuoteStr($mail, self::$_table);
        return parent::selectCollection(self::$_table, 'username='.trim($mail));
    }
    
    



}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_fe_usersAccessor.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_fe_usersAccessor.php']);
}

?>