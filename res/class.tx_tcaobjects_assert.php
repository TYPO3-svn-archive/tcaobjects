<?php

require_once t3lib_extMgm::extPath('pt_tools') . 'res/objects/class.tx_pttools_exception.php';

/**
 * Assertion class
 * 
 * @see 	http://www.debuggable.com/posts/assert-the-yummyness-of-your-cake:480f4dd6-7fe0-4113-9776-458acbdd56cb
 */
class tx_tcaobjects_assert {



	/**
	 * Basic test method
	 *
	 * @param 	mixed	first parameter 
	 * @param	mixed	second parameter
	 * @param 	array	(optional) additional info 
	 * @param 	bool	(optional) if true (default), parameters are tested by identy and not only equality 
	 * @return 	void
	 * @throws	tx_pttools_exception	if assertion fails
	 * @access 	public
	 */
	static function test($val, $expected, $info = array(), $strict = true) {

		$success = ($strict) ? $val === $expected : $val == $expected;
		
		if ($success) {
			return true;
		}
		
		$calls = debug_backtrace();
		foreach ($calls as $call) {
			if ($call['file'] !== __FILE__) {
				$assertCall = $call;
				break;
			}
		}
		$triggerCall = current($calls);
		$type = self::underscore($assertCall['function']);
		
		if (is_string($info)) {
			$info = array('type' => $info);
		}
		
		$info = tx_tcaobjects_div::am(array('file' => $assertCall['file'], 
											'line' => $assertCall['line'], 
											'function' => $triggerCall['class'] . '::' . $triggerCall['function'], 
											'assertType' => $type, 
											'val' => $val, 
											'expected' => $expected), 
										$info);
		// TODO: @see http://debuggable.com/posts/exceptional-cake:480f4dd5-1b10-4bc8-931f-49cecbdd56cb
		// throw new AppException($info);
		
		$message = '';
		
		foreach ($info as $key => $value) {
			$message .= '['.$key .': '.$value.'], ';
		}
		$message = trim($message, ' ,');
		throw new tx_pttools_exception('Assertion failed! '.$info['message'], 0, $message);
	}



	/**
	 * undocumented function
	 *
	 * @param	mixed	$val 
	 * @return 	void
	 * @access 	public
	 */
	static function true($val, $info = array()) {

		return self::test($val, true, $info);
	}



	/**
	 * undocumented function
	 *
	 * @param	mixed	$val 
	 * @param 	array	$info 
	 * @return 	void
	 * @access 	public
	 */
	static function false($val, $info = array()) {

		return self::test($val, false, $info);
	}



	/**
	 * undocumented function
	 *
	 * @param unknown $a 
	 * @param unknown $b 
	 * @param 	array	$info 
	 * @return 	void
	 * @access 	public
	 */
	static function equal($a, $b, $info = array()) {

		return self::test($a, $b, $info, false);
	}



	/**
	 * undocumented function
	 *
	 * @param unknown $a 
	 * @param unknown $b 
	 * @param 	array	$info 
	 * @return 	void
	 * @access 	public
	 */
	static function identical($a, $b, $info = array()) {

		return self::test($a, $b, $info, true);
	}



	/**
	 * undocumented function
	 *
	 * @return 	void
	 * @access 	public
	 */
	static function pattern($pattern, $val, $info = array()) {

		return self::test(preg_match($pattern, $val), true, tx_tcaobjects_div::am(array('pattern' => $pattern), $info));
	}



	/**
	 * undocumented function
	 *
	 * @param	mixed	$val 
	 * @param 	array	$info 
	 * @return 	void
	 * @access 	public
	 */
	static function isEmpty($val, $info = array()) {

		return self::test(empty($val), true, $info);
	}



	/**
	 * undocumented function
	 *
	 * @param	mixed	$val 
	 * @param 	array	$info 
	 * @return 	void
	 * @access 	public
	 */
	static function notEmpty($val, $info = array()) {

		return self::test(empty($val), false, $info);
	}



	/**
	 * undocumented function
	 *
	 * @param	mixed	$val 
	 * @param 	array	$info 
	 * @return 	void
	 * @access 	public
	 */
	static function isNumeric($val, $info = array()) {

		return self::test(is_numeric($val), true, $info);
	}



	/**
	 * undocumented function
	 *
	 * @param	mixed	$val 
	 * @param 	array	$info 
	 * @return 	void
	 * @access 	public
	 */
	static function notNumeric($val, $info = array()) {

		return self::test(is_numeric($val), false, $info);
	}



	/**
	 * undocumented function
	 *
	 * @param	mixed	$val 
	 * @param 	array	$info 
	 * @return 	void
	 * @access 	public
	 */
	static function isInteger($val, $info = array()) {

		return self::test(is_int($val), true, $info);
	}



	/**
	 * undocumented function
	 *
	 * @param	mixed	$val 
	 * @param 	array	$info 
	 * @return 	void
	 * @access 	public
	 */
	static function notInteger($val, $info = array()) {

		return self::test(is_int($val), false, $info);
	}



	/**
	 * undocumented function
	 *
	 * @return 	void
	 * @access 	public
	 */
	static function isIntegerish($val, $info = array()) {

		return self::test(is_int($val) || ctype_digit($val), true, $info);
	}



	/**
	 * undocumented function
	 *
	 * @return 	void
	 * @access 	public
	 */
	static function notIntegerish($val, $info = array()) {

		return self::test(is_int($val) || ctype_digit($val), false, $info);
	}



	/**
	 * undocumented function
	 *
	 * @param	mixed	$val 
	 * @param 	array	$info 
	 * @return 	void
	 * @access 	public
	 */
	static function isObject($val, $info = array()) {

		return self::test(is_object($val), true, $info);
	}



	/**
	 * undocumented function
	 *
	 * @param	mixed	$val 
	 * @param 	array	$info 
	 * @return 	void
	 * @access 	public
	 */
	static function notObject($val, $info = array()) {

		return self::test(is_object($val), false, $info);
	}



	/**
	 * undocumented function
	 *
	 * @param	mixed	$val 
	 * @param 	array	$info 
	 * @return 	void
	 * @access 	public
	 */
	static function isBoolean($val, $info = array()) {

		return self::test(is_bool($val), true, $info);
	}



	/**
	 * undocumented function
	 *
	 * @param	mixed	$val 
	 * @param 	array	$info 
	 * @return 	void
	 * @access 	public
	 */
	static function notBoolean($val, $info = array()) {

		return self::test(is_bool($val), false, $info);
	}



	/**
	 * undocumented function
	 *
	 * @param	mixed	$val 
	 * @param 	array	$info 
	 * @return 	void
	 * @access 	public
	 */
	static function isString($val, $info = array()) {

		return self::test(is_string($val), true, $info);
	}



	/**
	 * undocumented function
	 *
	 * @param	mixed	$val 
	 * @param 	array	$info 
	 * @return 	void
	 * @access 	public
	 */
	static function notString($val, $info = array()) {

		return self::test(is_string($val), false, $info);
	}



	/**
	 * undocumented function
	 *
	 * @param	mixed	$val 
	 * @param 	array	$info 
	 * @return 	void
	 * @access 	public
	 */
	static function isArray($val, $info = array()) {

		return self::test(is_array($val), true, $info);
	}



	/**
	 * undocumented function
	 *
	 * @param	mixed	$val 
	 * @param 	array	$info 
	 * @return 	void
	 * @access 	public
	 */
	static function notArray($val, $info = array()) {

		return self::test(is_array($val), false, $info);
	}



	/**	
	 * Returns an underscore-syntaxed ($like_this_dear_reader) version of the $camel_cased_word.
	 * 
	 * @see 	class "Inflector" from CakePHP 
	 * @param 	string 		$camel_cased_word Camel-cased word to be "underscorized"
	 * @return 	string 		Underscore-syntaxed version of the $camel_cased_word
	 * @access 	public
	 */
	static function underscore($camelCasedWord) {
		$replace = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $camelCasedWord));
		return $replace;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_assert.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_assert.php']);
}
?>