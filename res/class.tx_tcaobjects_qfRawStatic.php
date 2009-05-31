<?php

/**
 * Base class for form elements
 */ 
require_once 'HTML/QuickForm/static.php';

class tx_tcaobjects_qfRawStatic extends HTML_QuickForm_static {
	
	
	/**
	 * PHP4 style constructor. Don't use __construct as element.php calls this method directly
	 *
	 * @param 	string	(optional) element name
	 * @param 	string	(optional) element label
	 * @param 	string	(optional) text
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-09-15
	 */
	public function tx_tcaobjects_qfRawStatic($elementName=null, $elementLabel=null, $text=null) {
		parent::HTML_QuickForm_static($elementName, $elementLabel, $text);
	}
  
}
?>