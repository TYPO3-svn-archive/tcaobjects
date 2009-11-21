<?php
/***************************************************************
*  Copyright notice
*
*  (c)  2008 Fabrizio Branca (mail@fabrizio-branca.de)  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/



// TODO: add properties _properties, _aliasMap, _values, _ignoreFields


require_once(t3lib_extMgm::extPath('kickstarter').'class.tx_kickstarter_sectionbase.php');
require_once(t3lib_extMgm::extPath('tcaobjects').'lib/spyc.php');

/**
 * Adds a section to the extension kickstarter
 *
 * @author  Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2008-03-15
 */
class tx_tcaobjects_kickstarter_section_yaml extends tx_kickstarter_sectionbase {

	public $sectionID = 'yaml';

	public $pluginnr = -1;

	/**
	 * @var tx_kickstarter_wizard wizard object
	 */
	public $wizard;

	/**
	 * Renders the form in the kickstarter;
	 *
	 * @return	HTML
	 */
	public function render_wizard() {
		$action = explode(':', $this->wizard->modData['wizAction']);
		
		// overwrite current tables with the pared yaml content
		if (!empty($this->wizard->modData['wizArray_upd'][$this->sectionID]['yaml_tables'])) {
			$this->wizard->wizArray['tables'] = Spyc::YAMLLoadString($this->wizard->modData['wizArray_upd'][$this->sectionID]['yaml_tables']);
		}

		if ($action[0]=='edit')	{
			$action[1] = 1;
			$this->regNewEntry($this->sectionID, $action[1]);
		}
		
		$output = '';
		
		$output .= '<strong>Table definition as YAML</strong><br />';
		
		$value = Spyc::YAMLDump($this->wizard->wizArray['tables']);
		$ffPrefix ='['.$this->sectionID.']';
		
		$output .= $this->renderTextareaBox($ffPrefix.'[yaml_tables]', $value);
		
		$output .= '<br /><br />';
		
		return $output;
	}

	/**
	 * Renders the extension PHP code
	 *
	 * @param	string		$k: fieldname (key)
	 * @param	array		$config: pi config
	 * @param	string		$extKey: extension key
	 * @return	void
	 */
	public function render_extPart($k, $config, $extKey) {
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/sections/class.tx_kickstarter_section_tcaobjects.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/sections/class.tx_kickstarter_section_tcaobjects.php']);
}

?>