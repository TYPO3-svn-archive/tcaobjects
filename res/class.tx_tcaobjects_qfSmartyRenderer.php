<?php

class tx_tcaobjects_qfSmartyRenderer extends HTML_QuickForm_Renderer implements tx_tcaobjects_iQuickformRenderer {

	public function __construct() {
		parent::HTML_QuickForm_Renderer();
		
		throw new tx_pttool_exception('Not implemented yet');
	}
	
	public function toHtml() {
		
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_qfSmartyRenderer.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_qfSmartyRenderer.php']);
}

?>