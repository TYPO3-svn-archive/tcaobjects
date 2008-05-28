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
 * Class "tx_tcaobjects_fe_users"
 * 
 * $Id: class.tx_tcaobjects_fe_users.php,v 1.3 2008/05/12 13:59:36 ry44 Exp $
 * 
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2008-03-21
 */
class tx_tcaobjects_fe_users extends tx_tcaobjects_object {

	// TODO: export to tcaobjects

    protected $_table = 'fe_users';
    
    protected $_ignoredFields = array(	// fields not found in tca
	    									'fe_cruser_id', 
	    									'uc', 
	    									'lastlogin', 
	    									'is_online', 
	    									'felogin_redirectPid',
    										'ses_id', 
    										'ses_name', 
    										'ses_iplock', 
    										'ses_hashlock', 
    										'ses_userid', 
    										'ses_tstamp', 
    										'ses_data', 
    										'ses_permanent',
    );
        
    /**
     * Constructor. Extends the constructor by the possibility to create an fe_user object of the actual logged in user
     *
     * @param 	int		(optional) uid of the fe_user
     * @param 	array 	(optional) dataArr
     * @param 	bool	(optional) load active fe_user complete from session
     * @param 	bool	(optional) use uid from fe_user in session, but load data from database
     * @throws	tx_pttools_exception	if fromSession == true and no logged user found
     * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public function __construct($uid = '', array $dataArr = array(), $fromSession = false, $fromSessionUid = false){
        
        if ($fromSession){
            if ($GLOBALS['TSFE']->loginUser) {
                if ($fromSessionUid){
                    parent::__construct($GLOBALS['TSFE']->fe_user->user['uid']);    
                } else {
                    parent::__construct('', $GLOBALS['TSFE']->fe_user->user);
                }
            } else {
                throw new tx_pttools_exception('No user is logged in');   
            }
        } else {
        	try {
            	parent::__construct($uid, $dataArr);
        	} catch (tx_pttools_exception $exceptionObj) {
        		// Was not able to load user from db. Maybe deleted?        		
        		// $this['deleted'] = 1;
        		$this['uid'] = 0;
        	}
        }
    }

    
    
    /**
     * Login user (without checking password)
     *
     * @param 	void
     * @return 	bool	true if login was successful
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-03-22
     */
    public function login($withoutPassword = true, $password = '') {
    	$username = $this->__get('username');
    	if (empty($username)) {
    		throw new tx_pttools_exception('No username set.');
    	}
    	
		$loginData=array(
			'uname' => $username, // username
			'uident'=> $password, //password
			'status' =>'login'
		);

		$GLOBALS['TSFE']->fe_user->checkPid = 0; //do not use a particular pid
		$info = $GLOBALS['TSFE']->fe_user->getAuthInfoArray();
		$user = $GLOBALS['TSFE']->fe_user->fetchUserRecord($info['db_user'],$loginData['uname']);
		if ($withoutPassword || $GLOBALS['TSFE']->fe_user->compareUident($user, $loginData)) {
			$GLOBALS['TSFE']->fe_user->createUserSession($user);
			return true;
		} else {
			return false;
		}
    }
    
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_fe_users.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_fe_users.php']);
}

?>