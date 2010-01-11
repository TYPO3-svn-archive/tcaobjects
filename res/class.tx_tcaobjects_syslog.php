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
				
					  	// $params['time'] = date(DATE_COOKIE);
						$params['HTTP_HOST'] = t3lib_div::getIndpEnv('HTTP_HOST');
						$params['REQUEST_URI'] = t3lib_div::getIndpEnv('REQUEST_URI');
						$params['HTTP_REFERER'] = t3lib_div::getIndpEnv('HTTP_REFERER');
						$params['HTTP_USER_AGENT'] = t3lib_div::getIndpEnv('HTTP_USER_AGENT');
						if ($GLOBALS['TSFE'] instanceof tslib_fe) {
							if ($GLOBALS['TSFE']->loginUser) {
								$params['FE_User'] = $GLOBALS['TSFE']->fe_user->user;
								$params['FE_User']['password'] = '********';
								foreach ($params['FE_User'] as &$value) {
									$value = t3lib_div::fixed_lgd_cs($value, 50);
								}
							} else {
								$params['FE_User'] = 'No user logged in!';							
							}
							if ($GLOBALS['TSFE']->beUserLogin > 0) {
								$params['BE_User'] = $GLOBALS['BE_USER']->user;
								$params['BE_User']['password'] = '********';
								foreach ($params['BE_User'] as &$value) {
									$value = t3lib_div::fixed_lgd_cs($value, 50);
								}
							} else {
								$params['BE_User'] = 'No user logged in!';
							}
						}
						
						$params['trace'] = "\n\n\n" . implode("\n", self::trace2Array($params['backTrace']));
						unset($params['backTrace']);
						
						$message = var_export($params, true);
						
						$message = "\n\n" . $message . "\n\n" . str_repeat('=', 80); 
						
						if (!empty($mailAddress)) {
							$subject = sprintf('Error in TYPO3 installation on "%s"', $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLogHost']);
							mail($mailAddress, $subject, $message);
						}
						if (!empty($logFile)) {
							file_put_contents($logFile, $message, FILE_APPEND);
						}
					}
					
				}
			}
		}
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
	
	        	// Clean up argument List
	        	foreach ($row['args'] as &$arg) {
	        		if (is_object($arg)) {
	        			$arg = '['.get_class($arg).']';
	        		} elseif (is_array($arg)) {
	        			$arg = '[Array]';
	        		}
	        	}
	        	
	            $lines[] = sprintf('%s%s%s (%s)', $row['class'], $row['type'], $row['function'], implode(', ', $row['args']));
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