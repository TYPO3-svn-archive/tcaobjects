<?php

########################################################################
# Extension Manager/Repository config file for ext: "tcaobjects"
#
# Auto generated 09-05-2008 12:04
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'tcaobjects',
	'description' => 'Active Record for TYPO3 (including form building with HTML_QuickForm, templating with Smarty and some more features for PHP developers)',
	'category' => 'fe',
	'author' => 'Fabrizio Branca',
	'author_email' => 'mail@fabrizio-branca.de',
	'shy' => '',
	'dependencies' => 'smarty,pt_tools',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.0.1',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.2.0-0.0.0',
			'pt_tools' => '0.2.4-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'pt_mvc' => '',
			'smarty' => '1.2.6-0.0.0',
			'kickstarter' => '0.4.0-',
			'HTML_Quickform' => '',
			'HTML_Quickform advmultiselect' => '',
		),
	),
	'_md5_values_when_last_written' => 'a:29:{s:9:"ChangeLog";s:4:"687c";s:10:"README.txt";s:4:"9fa9";s:12:"ext_icon.gif";s:4:"89b0";s:17:"ext_localconf.php";s:4:"0757";s:14:"ext_tables.php";s:4:"e36d";s:19:"doc/wizard_form.dat";s:4:"3d99";s:20:"doc/wizard_form.html";s:4:"1d71";s:52:"sections/class.tx_kickstarter_section_tcaobjects.php";s:4:"c314";s:34:"res/class.tx_tcaobjects_assert.php";s:4:"8f58";s:31:"res/class.tx_tcaobjects_div.php";s:4:"9b51";s:35:"res/class.tx_tcaobjects_divForm.php";s:4:"f72c";s:36:"res/class.tx_tcaobjects_fe_users.php";s:4:"df8c";s:44:"res/class.tx_tcaobjects_fe_usersAccessor.php";s:4:"bdb8";s:46:"res/class.tx_tcaobjects_fe_usersCollection.php";s:4:"bc56";s:37:"res/class.tx_tcaobjects_iPageable.php";s:4:"1787";s:46:"res/class.tx_tcaobjects_iQuickformRenderer.php";s:4:"9377";s:34:"res/class.tx_tcaobjects_object.php";s:4:"9ec2";s:42:"res/class.tx_tcaobjects_objectAccessor.php";s:4:"9a6a";s:44:"res/class.tx_tcaobjects_objectCollection.php";s:4:"a011";s:33:"res/class.tx_tcaobjects_pager.php";s:4:"655e";s:45:"res/class.tx_tcaobjects_qfDefaultRenderer.php";s:4:"a26b";s:44:"res/class.tx_tcaobjects_qfSmartyRenderer.php";s:4:"1490";s:37:"res/class.tx_tcaobjects_quickform.php";s:4:"1a1b";s:21:"res/formRenderer.html";s:4:"41b1";s:17:"res/locallang.xml";s:4:"7b6f";s:13:"res/pager.tpl";s:4:"6be0";s:20:"static/constants.txt";s:4:"d41d";s:16:"static/setup.txt";s:4:"fa10";s:35:"misc/class.ux_tx_smarty_wrapper.php";s:4:"20e9";}',
	'suggests' => array(
	),
);

?>