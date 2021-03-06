<?php

if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TYPO3_CONF_VARS['EXTCONF']['kickstarter']['sections']['tcaobjects'] = array(
	'classname'   => 'tx_tcaobjects_kickstarter_section_tcaobjects',
	'filepath'    => 'EXT:tcaobjects/sections/class.tx_tcaobjects_kickstarter_section_tcaobjects.php',
	'title'       => 'Create tcaobjects classes',
	'description' => 'Prepares object, accessor and collection files.',
	'singleItem'  => '1',
);

$TYPO3_CONF_VARS['EXTCONF']['kickstarter']['sections']['yaml'] = array(
	'classname'   => 'tx_tcaobjects_kickstarter_section_yaml',
	'filepath'    => 'EXT:tcaobjects/sections/class.tx_tcaobjects_kickstarter_section_yaml.php',
	'title'       => 'Edit tables and fields via YAML',
	'description' => 'Edit tables and files via a YAML dump.',
	'singleItem'  => '1',
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

// t3lib_extMgm::addPItoST43($_EXTKEY,'plugins/class.tx_tcaobjects_display_uncachedobject.php','_display_uncachedobject','list_type',0);
// t3lib_extMgm::addPItoST43($_EXTKEY,'plugins/class.tx_tcaobjects_display_cachedobject.php','_display_cachedobject','list_type',1);


// creating our own cObjects...
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClassDefault'][] = 'EXT:tcaobjects/misc/class.tx_tcaobjects_cObjects.php:tx_tcaobjects_cObjects';

// custom syslog handler
$baseConfArr = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['tcaobjects']);
if ($baseConfArr['activateCustomSysLogHandler']) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['systemLog'][] = 'EXT:tcaobjects/res/class.tx_tcaobjects_syslog.php:tx_tcaobjects_syslog->user_syslog';
}

?>