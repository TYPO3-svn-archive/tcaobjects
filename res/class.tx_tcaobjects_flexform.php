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
 * Flexform object
 * 
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2010-01-16
 */
class tx_tcaobjects_flexform {
	
	protected $data = array();

	/**
	 * Class constructor
	 * 
	 * @param string type $flexFormString
	 */
	public function __construct($flexFormString) {
		tx_pttools_assert::isNotEmptyString($flexFormString);
		
		$this->data = t3lib_div::xml2array($flexFormString);
		
		// We're expecting an array. If not this is the error message
		tx_pttools_assert::isNotEmptyArray($this->data, array('message' => $this->data, 'flexFormString' => $flexFormString));
	}
		
	/**
	 * Return value from somewhere inside a FlexForm structure
	 *
	 * @param	array		FlexForm data
	 * @param	string		Field name to extract. Can be given like "test/el/2/test/el/field_templateObject" where each part will dig a level deeper in the FlexForm data.
	 * @param	string		Sheet pointer, eg. "sDEF"
	 * @param	string		Language pointer, eg. "lDEF"
	 * @param	string		Value pointer, eg. "vDEF"
	 * @return	string		The content.
	 */
	public function getValue($fieldName, $sheet='sDEF', $lang='lDEF', $value='vDEF') {
		tx_pttools_assert::isNotEmptyString($fieldName, array('message' => 'No fieldname given'));
		tx_pttools_assert::isNotEmptyArray($this->data['data'], array('message' => 'No data found', 'data' => $this->data));
		tx_pttools_assert::isNotEmptyArray($this->data['data'][$sheet], array('message' => sprintf('Sheet "%s" not found', $sheet)));
		tx_pttools_assert::isNotEmptyArray($this->data['data'][$sheet][$lang], array('message' => sprintf('Language "%s" not found', $lang)));
		$sheetArray = $this->data['data'][$sheet][$lang];
		return $this->getValueFromSheetArray($sheetArray, explode('/',$fieldName), $value);
	}
	
	/**
	 * Returns part of $sheetArray pointed to by the keys in $fieldNameArray
	 *
	 * @param	array		Multidimensiona array, typically FlexForm contents
	 * @param	array		Array where each value points to a key in the FlexForms content - the input array will have the value returned pointed to by these keys. All integer keys will not take their integer counterparts, but rather traverse the current position in the array an return element number X (whether this is right behavior is not settled yet...)
	 * @param	string		Value for outermost key, typ. "vDEF" depending on language.
	 * @return	mixed		The value, typ. string.
	 * @access private
	 * @see pi_getFFvalue()
	 */
	protected function getValueFromSheetArray(array $sheetArray, array $fieldNameArr, $value)	{

		$tempArr = $sheetArray;
		foreach($fieldNameArr as $k => $v)	{
			if (t3lib_div::testInt($v))	{
				if (is_array($tempArr))	{
					$c=0;
					foreach($tempArr as $values)	{
						if ($c==$v)	{
							#debug($values);
							$tempArr=$values;
							break;
						}
						$c++;
					}
				} else {
					throw new tx_pttools_exceptionInternal('No array found!');
				}
			} else {
				$tempArr = $tempArr[$v];
			}
		}
		if (!array_key_exists($value, $tempArr)) {
			throw new tx_pttools_exceptionInternal('Key not found!');
		}
		return $tempArr[$value];
	}
		
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_flexform.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_flexform.php']);
}

?>