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

/**
 * Adds a section to the extension kickstarter
 *
 * @author  Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2008-03-15
 */
class tx_tcaobjects_kickstarter_section_tcaobjects extends tx_kickstarter_sectionbase {

	public $sectionID = 'tcaobjects';

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
		
		if ($action[0] == 'edit') {
			$action[1] = 1;
			$this->regNewEntry($this->sectionID, $action[1]);
		}
		
		$output = '';
		
		$output .= '<strong>This will create class files for tcaobjects<strong><br /><br />';
		
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
		
		$piConf   = $this->wizard->wizArray['tables'];
		
		$pathSuffix = 'model/';

		$this->wizard->ext_localconf[] = '
// setting up tcaobjects autoloader
$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'tcaobjects\'][\'autoloader\'][$_EXTKEY] = array(
	\'partsMatchDirectories\' => true,
	// \'classMap\' => array(
	//		\'<classname>\' => \'<path relative to the extension directory>\',
	// ),
	\'classPaths\' => array(
		// ordered by where the most classes are to be autoloaded
		\'model\',
		// \'misc\',
		// \'controller\', // this is not needed, as controller classes are loaded by TYPO3 directly
		// \'view\' // this is not needed, as view classes are loaded by the controller
	)
);

';

		$tables = array();
		foreach ((array)$piConf as $table) {
			$tables[] = array (
				'tablename' => $table['tablename'],
				'isexttable' => false,
				'conf' => $table
			);
		}

		$piConfFields = $this->wizard->wizArray['fields'];
		foreach ((array)$piConfFields as $table) {
			$tables[] = array (
				'tablename' => $table['which_table'],
				'isexttable' => true
			);
		}

		foreach ($tables as $table) {

			$cN = $this->returnName($extKey, 'class', $table['tablename']);

			$cnO = $cN;
			$cnA = $cN.'Accessor';
			$cnC = $cN.'Collection';

			$fnO = $pathSuffix.'class.'.$cnO.'.php';
			$fnA = $pathSuffix.'class.'.$cnA.'.php';
			$fnC = $pathSuffix.'class.'.$cnC.'.php';


			// accessor
			if ($table['isexttable']) $tableproperty = "\n\t".'static protected $_table = \''.$table['tablename'].'\';'."\n";
			
			$properties = "\n\t/**\n";
			$properties .= "\t * Fields (auto-generated in kickstarter):\n";
			$properties .= "\t * \n";
			
			$standardFields = array('uid', 'pid', 'tstamp', 'crdate', 'cruser_id');
			$properties .= "\t * (Standard fields)\n";
			foreach ($standardFields as $standardField) {
				$properties .= "\t * $standardField\n";
			}
			
			
			$config = $table['conf'];
			
			if (is_array($config)) {
				
				if ($config['versioning']) {
					$versioningFields = array('t3ver_oid', 't3ver_id', 't3ver_wsid', 't3ver_label', 't3ver_state', 't3ver_stage', 't3ver_count', 't3ver_tstamp', 't3_origuid');
					$properties .= "\t * \n";
					$properties .= "\t * (Versioning fields)\n";
					foreach ($versioningFields as $versioningField) {
						$properties .= "\t * $versioningField\n";
					}
				}
	
				if ($config['localization']) {
					$localizationFields = array('sys_language_uid', 'l10n_parent', 'l10n_diffsource');
					$properties .= "\t * \n";
					$properties .= "\t * (Localization fields)\n";
					foreach ($localizationFields as $localizationField) {
						$properties .= "\t * $localizationField\n";
					}
				}
				
				$properties .= "\t * \n";
				$properties .= "\t * (Other fields)\n";
				if ($config['sorting'])	{
					$properties .= "\t * sorting\n";
				}
				
				if ($config['add_deleted'])	{
					$properties .= "\t * deleted\n";
				}
				if ($config["add_hidden"])	{
					$properties .= "\t * hidden\n";
				}
				if ($config["add_starttime"])	{
					$properties .= "\t * starttime\n";
				}
				if ($config["add_endtime"])	{
					$properties .= "\t * endtime\n";
				}
				if ($config["add_access"])	{
					$properties .= "\t * fe_group\n";
				}
			}
			
			if (is_array($config['fields'])) {
				$properties .= "\t * \n";
				$properties .= "\t * (Custom fields)\n";
				foreach ($config['fields'] as $field) {
					
					$type = $field['fieldname'];
					if ($field['type'] == 'rel') {
						$type .= '['.$field['conf_relations'].']';
						$type .= ' ('.$field['conf_rel_table'].')';
					}
					
					$properties .= "\t * ".$type."\n";
				}
			}
			
			
			$properties .= "\t */\n";


			$class = "class $cnA extends tx_tcaobjects_objectAccessor {\n$tableproperty\n}";
			$description = 'Accessor class for "'.$cnO.'"';
			$file = $this->PHPclassFile($extKey, $fnA, $class, $description);
			$this->addFileToFileArray($fnA, $file);

			// object
			$class = "class $cnO extends tx_tcaobjects_object {\n$properties\n}";
			$description = 'tcaobject for table "'.$table['tablename'].'"';
			$file = $this->PHPclassFile($extKey, $fnO, $class, $description);
			$this->addFileToFileArray($fnO, $file);

			// collection
			// TODO: remove because this is not needed (is set in the constructor)
			$classContent = "\n";
			/*
			$classContent =  "

	protected \$restrictedClassName = '$cnO';

	";
			*/
			$class = "class $cnC extends tx_tcaobjects_objectCollection {\n$classContent\n}";
			$description = 'Collection of "'.$cnO.'" objects';
			$file = $this->PHPclassFile($extKey, $fnC, $class, $description);
			$this->addFileToFileArray($fnC, $file);

			// single view
			// $this->addFileToFileArray($pathSuffix.'templates/'.$table['tablename'].'_single.tpl.html', '...');

			// list view
			// $this->addFileToFileArray($pathSuffix.'templates/'.$table['tablename'].'_list.tpl.html', '...');
		}

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/sections/class.tx_kickstarter_section_tcaobjects.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/sections/class.tx_kickstarter_section_tcaobjects.php']);
}

?>