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
 * Class "tx_tcaobjects_sys_languageCollection"
 * 
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2010-03-31
 */
class tx_tcaobjects_sys_languageCollection extends tx_tcaobjects_objectCollection {
	
	/**
	 * Add sys_language object representing the default language
	 * 
	 * @param int (optional) pid from where to retrieve pagets for default language settings
	 * @return void
	 * @author Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	public function addDefaultLanguage($pid=NULL) {
		$defaultLanguage = new tx_tcaobjects_sys_language();
		$defaultLanguage['uid'] = 0;
		$pageTS = t3lib_BEfunc::getPagesTSconfig($pid ? $pid : $GLOBALS['TSFE']->id);
		$defaultLanguage['flag'] = $pageTS['mod.']['SHARED.']['defaultLanguageFlag'];
		$defaultLanguage['title'] = $pageTS['mod.']['SHARED.']['defaultLanguageLabel'] ? $pageTS['mod.']['SHARED.']['defaultLanguageLabel'] : 'Default';
		$this->addItem($defaultLanguage);
	}
    
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_sys_languageCollection.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_sys_languageCollection.php']);
}

?>