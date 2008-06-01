<?php

if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
} 

$TYPO3_CONF_VARS['EXTCONF']['kickstarter']['sections']['tcaobjects'] = array(
	'classname'   => 'tx_kickstarter_section_tcaobjects',
	'filepath'    => 'EXT:tcaobjects/sections/class.tx_kickstarter_section_tcaobjects.php',
	'title'       => 'tcaobjects',
	'description' => 'Prepares object, accessor and collection files.',
	'singleItem'  => '',
);

// these require_onces are needed to get the autoloader running 
require_once t3lib_extMgm::extPath('tcaobjects') . 'res/class.tx_tcaobjects_div.php';
require_once t3lib_extMgm::extPath('pt_tools') . 'res/objects/class.tx_pttools_exception.php';

// Registering autoloaders
if (spl_autoload_register(array('tx_tcaobjects_div', 'autoLoad')) == false) {
	throw new tx_pttools_exception('Registering autoloader for tcaobjects failed.');
}
/*
// Autoloader for PEAR classes (not used for now) 
if (spl_autoload_register(array('tx_tcaobjects_div', 'pearAutoLoad')) == false) {
	throw new tx_pttools_exception('Registering autoloader for PEAR classes failed.');
}
*/

// Using the autoloader for the own classes
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tcaobjects']['autoLoadingPath'][str_replace('_','',$_EXTKEY)] = 'EXT:'.$_EXTKEY.'/res/';

// Autoloader for pt_tools classes
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tcaobjects']['autoLoadingPath']['pttools'] = 'EXT:pt_tools/res/';

// Class map for pt_tools classes
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tcaobjects']['autoLoadingClassMap']['pttools'] = array(

	'tx_pttools_objectCollection' 		=> 'abstract/class.tx_pttools_objectCollection.php',

	'tx_pttools_exception' 				=> 'objects/class.tx_pttools_exception.php',
	'tx_pttools_registry' 				=> 'objects/class.tx_pttools_registry.php',
	'tx_pttools_formReloadHandler' 		=> 'objects/class.tx_pttools_formReloadHandler.php',
	'tx_pttools_sessionStorageAdapter' 	=> 'objects/class.tx_pttools_sessionStorageAdapter.php',

	'tx_pttools_debug'	 				=> 'staticlib/class.tx_pttools_debug.php',
	'tx_pttools_div'	 				=> 'staticlib/class.tx_pttools_div.php',
	'tx_pttools_assert'	 				=> 'staticlib/class.tx_pttools_assert.php',

);

// Extending smarty
$GLOBALS['TYPO3_CONF_VARS']['FE']['XCLASS']['ext/smarty/class.tx_smarty_wrapper.php'] = t3lib_extMgm::extPath($_EXTKEY).'misc/class.ux_tx_smarty_wrapper.php';

// nicht schoen, ich weiss... :)
// geht aber nicht anders, weil in tx_smarty_wrapper die XCLASS nicht required wird, weil dort TYPO3_CONF_VARS nicht global ist...
tx_smarty::_getSmarty();
require_once t3lib_extMgm::extPath($_EXTKEY).'misc/class.ux_tx_smarty_wrapper.php';

?>