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
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tcaobjects']['autoloader'][$_EXTKEY] = array(
	'classPaths' => array('res', 'plugins'),
);

// Autoloader for pt_tools classes
if (t3lib_extMgm::isLoaded('pt_tools')) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tcaobjects']['autoloader']['pt_tools'] = array(
		'basePath' => 'EXT:pt_tools/res/',
		'classPaths' => array(), // no classPaths here, because we're using a classMap
		'classMap' => array(
			'tx_pttools_objectCollection' 		=> 'abstract/class.tx_pttools_objectCollection.php',
		
			'tx_pttools_exception' 				=> 'objects/class.tx_pttools_exception.php',
			'tx_pttools_registry' 				=> 'objects/class.tx_pttools_registry.php',
			'tx_pttools_formReloadHandler' 		=> 'objects/class.tx_pttools_formReloadHandler.php',
			'tx_pttools_sessionStorageAdapter' 	=> 'objects/class.tx_pttools_sessionStorageAdapter.php',
			'tx_pttools_smartyAdapter' 			=> 'objects/class.tx_pttools_smartyAdapter.php',
		
			'tx_pttools_debug'	 				=> 'staticlib/class.tx_pttools_debug.php',
			'tx_pttools_div'	 				=> 'staticlib/class.tx_pttools_div.php',
			'tx_pttools_assert'	 				=> 'staticlib/class.tx_pttools_assert.php',
		)
	);
}

// setting up the tcaobjects autoloader
if (t3lib_extMgm::isLoaded('pt_mail')) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tcaobjects']['autoloader']['pt_mail'] = array(
		'partsMatchDirectories' => true,
		'classPaths' => array(
			'res',
		)
	);
}

// Extending smarty
$GLOBALS['TYPO3_CONF_VARS']['FE']['XCLASS']['ext/smarty/class.tx_smarty_wrapper.php'] = t3lib_extMgm::extPath($_EXTKEY).'misc/class.ux_tx_smarty_wrapper.php';

// nicht schoen, ich weiss... :)
// geht aber nicht anders, weil in tx_smarty_wrapper die XCLASS nicht required wird, weil dort TYPO3_CONF_VARS nicht global ist...
tx_smarty::_getSmarty();
require_once t3lib_extMgm::extPath($_EXTKEY).'misc/class.ux_tx_smarty_wrapper.php';

// t3lib_extMgm::addPItoST43($_EXTKEY,'plugins/class.tx_tcaobjects_display_uncachedobject.php','_display_uncachedobject','list_type',0);
// t3lib_extMgm::addPItoST43($_EXTKEY,'plugins/class.tx_tcaobjects_display_cachedobject.php','_display_cachedobject','list_type',1);


?>