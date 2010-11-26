<?php

require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php';

class tx_tcaobjects_syslog {
	
	/**
	 * Syslog userfunction 
	 *
	 * @param 	array	keys: msg, extKey, backTrace
	 * @return 	void 
	 */
	public static function user_syslog(array $params) {
		if (!$params['initLog']) {
			
			$conf = tx_pttools_div::returnExtConfArray('tcaobjects');
			
			$minSeverity = $conf['customSysLogHandlerMinSeverity'];
			$mailAddress = $conf['customSysLogHandlerMailAddress'];
			$logFile = $conf['customSysLogHandlerLogFile'];
			$customSysLogAnonymize = $conf['customSysLogAnonymize'];
			
			if (!empty($mailAddress) || !empty($logFile)) {
			
				// the "severity" is available only in TYPO3 versions > 4.3 (Or patch add this to your sources manually)
				if ((!isset($params['severity']) || $params['severity'] >= $minSeverity)) {
					
					
					$pattern = $conf['customSysLogHandleExcludeMessages'];
					if (!empty($pattern)) {
						if (preg_match($pattern, $params['msg'])) {
							return;
						}
					}
	
					if ((strpos($params['msg'], 'Possible abuse of t3lib_formmail: temporary file') === false) &&
					  	(strpos($params['msg'], '$TSFE->set_no_cache() was triggered by') === false) &&
					  	(strpos($params['msg'], '&no_cache=1 has been supplied, so caching is disabled!') === false) &&
					  	(strpos($params['msg'], 'Acquired lock') === false) &&
					  	(strpos($params['msg'], 'Released lock') === false) &&
					  	(strpos($params['msg'], 'The requested page does not exist!') === false) &&
					  	(strpos($params['msg'], 'Login-attempt from') === false)
					  	) {
					  		
					  	// clean message
					  	$matches = array();
					  	preg_match('/(.*)\[(.*)\]/', $params['msg'], $matches);
					  	
					  	if (count($matches) == 3) {
					  		$params['msg'] = trim($matches[1]);
					  		list($class, $debugMsg) = explode(':', $matches[2], 2);
					  		$params['exceptionClass'] = trim($class);
					  		$debugMsg = trim($debugMsg);
					  		if (!empty($debugMsg)) {
					  			$params['debugMsg'] = $debugMsg;	
								$pattern = '/<span class="label">(.*)<\/span><span class="value (.*)">(.*)<\/span>/U';
								$allMatches = array();
								if (preg_match_all($pattern, $params['debugMsg'], $allMatches)) {
									$params['debugMsg'] = array();
									foreach ($allMatches[0] as $key => $match) {
										$params['debugMsg'][$allMatches[1][$key]] = $allMatches[3][$key]; 	
									} 	
								}
					  		}
					  	}
					  	
					  	// $params['time'] = date(DATE_COOKIE);
						$params['Server']['TYPO3_REQUEST_URL'] = t3lib_div::getIndpEnv('TYPO3_REQUEST_URL');
						$params['Server']['HTTP_REFERER'] = t3lib_div::getIndpEnv('HTTP_REFERER');
						if (!empty($_POST)) {
							$params['Server']['POST'] = $customSysLogAnonymize ? '(anonymized)' : $_POST;	
						} else {
							$params['Server']['POST'] = '-- none --';
						}
						if (!empty($_COOKIE)) {
							$params['Server']['COOKIE'] = $customSysLogAnonymize ? '(anonymized)' : $_COOKIE;	
						} else {
							$params['Server']['COOKIE'] = '-- none --';
						}
						
						$params['Client']['HTTP_USER_AGENT'] = t3lib_div::getIndpEnv('HTTP_USER_AGENT');
						$params['Client']['Spider'] = self::checkIfSpider($params['Client']['HTTP_USER_AGENT']) ? ' Yes' : 'No';
						$params['Client']['REMOTE_HOST'] = $customSysLogAnonymize ? '(anonymized)' : t3lib_div::getIndpEnv('REMOTE_HOST');
						$params['Client']['REMOTE_ADDR'] = $customSysLogAnonymize ? '(anonymized)' : t3lib_div::getIndpEnv('REMOTE_ADDR');
						
						if ($GLOBALS['TSFE'] instanceof tslib_fe) {
							if ($GLOBALS['TSFE']->loginUser) {
								// Username is enough in most cases. For full details use the commented part
								$params['User']['FE_User'] = $customSysLogAnonymize ? '(anonymized)' : $GLOBALS['TSFE']->fe_user->user['username'];
								if ($GLOBALS['CNMode']) {
									$params['User']['FE_User'] .= ' (ADMIN-MODE)';	
								}
								/*
								$params['FE_User'] = $GLOBALS['TSFE']->fe_user->user;
								$params['FE_User']['password'] = '********';
								foreach ($params['FE_User'] as &$value) {
									$value = t3lib_div::fixed_lgd_cs($value, 50);
								}
								*/
							} else {
								$params['User']['FE_User'] = '-- no user --';							
							}
							if ($GLOBALS['TSFE']->beUserLogin > 0) {
								// Username is enough in most cases. For full details use the commented part
								$params['User']['BE_User'] = $customSysLogAnonymize ? '(anonymized)' : $GLOBALS['BE_USER']->user['username'];
								/*
								$params['BE_User'] = $GLOBALS['BE_USER']->user;
								$params['BE_User']['password'] = '********';
								foreach ($params['BE_User'] as &$value) {
									$value = t3lib_div::fixed_lgd_cs($value, 50);
								}
								*/
							} else {
								$params['User']['BE_User'] = '-- no user --';
							}
						}
						
						$params['Trace'] = "\n\n\n" . implode("\n", self::trace2Array($params['backTrace']));
						unset($params['backTrace']);
						
						$message = self::plainTextArray($params);
						
						if (!empty($mailAddress)) {
							
							if ($params['Client']['Spider'] == 'No') {
								$subject = sprintf('[ERROR][%s] %s', $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLogHost'], $params['msg']);
								mail($mailAddress, $subject, $message);
							}
						}
						if (!empty($logFile)) {
							$message = "\n\n" . $message . "\n\n" . str_repeat('=', 80); 
							file_put_contents($logFile, $message, FILE_APPEND);
						}
					}
					
				}
			}
		}
	}
	
	
	/**
	 * Check if the user agent string matches a spider
	 * 
	 * @param string $userAgent
	 * @return bool true if spider was detected
	 */
	public static function checkIfSpider($userAgent) {  
         $spiders = array(  
			'Googlebot', 'Yammybot', 'Openbot', 'Yahoo', 'Slurp', 'msnbot',  
            'ia_archiver', 'Lycos', 'Scooter', 'AltaVista', 'Teoma', 'Gigabot',  
            'Googlebot-Mobile', 'Yandex', 'DotBot', 'FAST Enterprise Crawler' 
		);  
   
		foreach ($spiders as $spider) {
             if (stripos($userAgent, $spider) !== false) {  
                 return true;  
             }  
         }  
         return false;  
     }  
	
	
	/**
	 * Plain text output of an array
	 * 
	 * @param array $array
	 * @param int (optional) $padding
	 * @author Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 2010-05-27
	 */
	public static function plainTextArray(array $array, $padding=0) {
		
		$plainText = '';
		
		foreach ($array as $key => $value) {
			$plainText .= str_repeat(' ', $padding) . $key.': ';
			if (is_scalar($value)) {
				$plainText .= $value . "\n";	
			} elseif (is_array($value)) {
				$plainText .= "\n" . self::plainTextArray($value, $padding + 2);
			} elseif (is_object($value)) {
				$plainText .=  get_class($value). "\n";
			} elseif (is_null($value)) {
				$plainText .=  '[null]' . "\n";
			} else {
				$plainText .=  '[undefined]' . "\n";
			}
		}
		
		return $plainText;
	}
	
	/**
	 * Convert backtrace to an array of plaintext lines
	 * 
	 * @param array $trace
	 * @return array plaintext lines
	 * @author Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 2009-10-06
	 */
    public static function trace2Array(array $trace) {
    	$lines = array();
        foreach ($trace as $key => $row) {
        	
        	if ($row['class'] != 't3lib_div' && $row['function'] != 'sysLog'
        		// && $row['class'] != 'tx_pttools_exception-' && $row['function'] != 'handle'
        		) {
        	
	        	$lines[] = '-- #'.$key.' -------------------------------------------------------';
	
	        	$cleanedArguments = array();
	        	
	        	// Clean up argument List
	        	foreach ($row['args'] as $arg) {
	        		if (is_object($arg)) {
	        			$cleanedArguments[] = '['.get_class($arg).']';
	        		} elseif (is_array($arg)) {
	        			$cleanedArguments[] = '[Array]';
	        		} else {
	        			$cleanedArguments[] = $arg;
	        		}
	        	}
	        	
	            $lines[] = sprintf('%s%s%s (%s)', $row['class'], $row['type'], $row['function'], implode(', ', $cleanedArguments));
	            if (!empty($row['file']) || !empty($row['line'])) {
	            	$lines[] = sprintf('    %s (%s)', $row['file'], $row['line']);
	            }
	
	        	// Create Trace on exception
	       		if ($row['object'] instanceOf Exception) {
	       			$lines[] = '';
	       			$lines[] = '    == Exception trace ===================';
	       			$lines[] = str_replace("\n", "    \n", $row['object']->getTraceAsString()); 
	       			$lines[] = '    == /Exception trace ==================';
	       			$lines[] = '';
	       		}
	       		
	       		$lines[] = '';
        	}
        }
        return $lines;
    }
	
}

?>