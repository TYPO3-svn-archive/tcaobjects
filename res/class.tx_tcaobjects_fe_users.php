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
    
    protected $_ignoredFields = array(	
    	// fields not found in tca
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
     * @param 	bool	(optional) ignore enable fields, default is false
     * @throws	tx_pttools_exception	if fromSession == true and no logged user found
     * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public function __construct($uid = '', array $dataArr = array(), $fromSession = false, $fromSessionUid = false, $ignoreEnableFields = false){
    	
        if ($fromSession) {
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
        	// try {
            	parent::__construct($uid, $dataArr, $ignoreEnableFields);
        	/*
        	} catch (tx_pttools_exception $exceptionObj) {
        		echo $exceptionObj->getMessage();
        		// Was not able to load user from db. Maybe deleted?        		
        		// $this['deleted'] = 1;
        		$this['uid'] = 0;
        	}
			*/
        }
    }

    
    
    /**
     * Login user
     *
     * @param 	bool	(optional) no password check, default is true
     * @param 	string	(optional) password to be checked against is first parameter is false
     * @return 	bool	true if login was successful
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-03-22
     */
    public function login($withoutPassword = true, $password = '') {
    	$username = $this->__get('username');
    	if (empty($username)) {
    		throw new tx_pttools_exception('No username set.');
    	}
    	
		$loginData = array(
			'uname' => $username, // username
			'uident'=> $password, //password
			'status' =>'login'
		);

		$GLOBALS['TSFE']->fe_user->checkPid = 0; //do not use a particular pid
		$info = $GLOBALS['TSFE']->fe_user->getAuthInfoArray();
		$user = $GLOBALS['TSFE']->fe_user->fetchUserRecord($info['db_user'],$username);
		
		if ($withoutPassword || $this->checkPassword($password)) {
			$GLOBALS['TSFE']->fe_user->createUserSession($user);
			return true;
		} else {
			return false;
		}
    }
    
    
    
    /**
	 * Check users password
	 * 
	 * @param string password
	 * @return bool
     */
    public function checkPassword($password) {
    	
    	tx_pttools_assert::isEqual($GLOBALS['TSFE']->fe_user->user['username'], $this->get_username(), array('message' => 'Can only check password if the current user is the user matching this object'));
    	
    	$loginData = array(
			'uname' => $GLOBALS['TSFE']->fe_user->user['username'], // username
			'uident'=> $password, //password
    		'uident_text' => $password,
			'status' =>'login'
		);
		
		$tempuser = $GLOBALS['TSFE']->fe_user->user;
		
		if (TYPO3_DLOG) t3lib_div::devLog('Checking password ' . $password, 'tcaobjects', 1, $tempuser);
		
    	$serviceChain='';
		$subType = 'authUserFE';
		while (is_object($serviceObj = t3lib_div::makeInstanceService('auth', $subType, $serviceChain))) {
			
			$serviceKey = $serviceObj->getServiceKey();
			
			$serviceChain.=','.$serviceKey;
			$serviceObj->initAuth($subType, $loginData, $authInfo, $GLOBALS['TSFE']->fe_user);
			
			$ret = $serviceObj->authUser($tempuser);
			
			if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Asking service "%s", returning: "%s"', $serviceKey, $ret), 'tcaobjects');
			
			if ($ret > 0) {

					// if the service returns >=200 then no more checking is needed - useful for IP checking without password
				if (intval($ret) >= 200)	{
					$authenticated = TRUE;
					break;
				} elseif (intval($ret) >= 100) {
					// Just go on. User is still not authenticated but there's no reason to stop now.
				} else {
					$authenticated = TRUE;
				}

			} else {
				$authenticated = FALSE;
				break;
			}
			unset($serviceObj);
		}
		unset($serviceObj);
		
		return $authenticated;
    }
    
    
    
    /**
     * Checks if this user objects is the current logged in user
     *
     * @return 	bool	true, if this is the current user, otherwise false
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-06-08
     */
    public function isCurrentUser() {
    	return ($this['uid'] == $GLOBALS['TSFE']->fe_user->user['uid']);
    }
    
    
    
    /**
     * Copies this user to the session
     * 
     * @param 	void
     * @return 	void
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-06-19
     */
    public function copyToSessionUser() {
    	
    	tx_pttools_assert::isInstanceOf($GLOBALS['TSFE'], 'tslib_fe');
			
	    // updating data in the session        
        foreach ($this as $property => $value) {
        	$GLOBALS['TSFE']->fe_user->user[$property] = $value;
		}
    }
    
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_fe_users.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_fe_users.php']);
}

?>