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
 * Class "tx_tcaobjects_fe_usersCollection"
 * 
 * $Id: class.tx_tcaobjects_fe_usersCollection.php,v 1.3 2008/05/12 13:59:36 ry44 Exp $
 * 
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2010-03-10
 */
class tx_tcaobjects_tt_contentCollection extends tx_tcaobjects_objectCollection {
    
	/**
	 * @var string unique property name
	 */
	protected $uniqueProperty = 'uid';
	
	
	
	/**
	 * Load content elements by pid
	 * 
	 * @param int pid
	 * @return void
	 */
	public function loadByPid($pid) {
		tx_pttools_assert::isValidUid($pid, false, array('message' => 'Invalid pid'));	
		$this->loadItems('pid = ' . $pid);
	}
	
	
	
	/**
	 * Load items
	 *
	 * @param 	string	(optional) where
	 * @param 	string	(optional) limit
	 * @param 	string	(optional) order, if empty "sorting" field is used
	 * @param	bool	(optional) ignore enable fields, default is false
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2010-10-07
	 */
	public function loadItems($where = '', $limit = '', $order = '') {
		if ($order == '') {
			$order = 'sorting';
		}
		parent::loadItems($where, $limit, $order);
	}
	
	
	
	/**
	 * Sort collection items by an external array
	 * 
	 * @param array array containing the uids in the order to be sorted
	 * @return void
	 * @author Fabrizio Branca <fabrizio@scrbl.net>
	 * @since 2009-08-13
	 */
	public function sortByArray(array $sorting) {
		tx_pttools_assert::isValidUidArray($sorting, false, array('message' => 'Given sorting array is invalid'));
		$uids = $this->extractProperty('uid');
		tx_pttools_assert::isValidUidArray($uids, false, array('message' => 'Retrieved uids are invalid'));
		
		$sortingUids = $sorting;
		
		sort($sortingUids);
		sort($uids);
		
		tx_pttools_assert::isEqual($sortingUids, $uids, array('message' => 'Sorting array did not contain the same elements as the collection.'));
		
		foreach ($sorting as $key => $uid) {
			$this->getItemByProperty('uid', $uid)->set_sorting(pow(2, $key));
		}
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_tt_contentCollection.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_tt_contentCollection.php']);
}

?>