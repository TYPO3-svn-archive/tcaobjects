<?php

t3lib_extMgm::addStaticFile($_EXTKEY,'static/','TCAobjects');

t3lib_extMgm::addPlugin(array('LLL:EXT:tcaobjects/locallang_db.xml:tt_content.list_type_display_uncachedobject', $_EXTKEY.'_display_uncachedobject'),'list_type');
t3lib_extMgm::addPlugin(array('LLL:EXT:tcaobjects/locallang_db.xml:tt_content.list_type_display_cachedobject', $_EXTKEY.'_display_cachedobject'),'list_type');

t3lib_div::loadTCA('tt_content');

$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_display_uncachedobject']='layout,select_key,pages,recursive';
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_display_cachedobject']='layout,select_key,pages,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_display_uncachedobject'] = 'pi_flexform';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_display_cachedobject'] = 'pi_flexform';
	
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_display_uncachedobject', 'FILE:EXT:'.$_EXTKEY.'/plugins/flexform_display_object.xml');
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_display_cachedobject', 'FILE:EXT:'.$_EXTKEY.'/plugins/flexform_display_object.xml');


?>