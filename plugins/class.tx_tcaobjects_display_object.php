<?php

require_once(PATH_tslib.'class.tslib_pibase.php');

abstract class tx_tcaobjects_display_object extends tslib_pibase {
	public $prefixId      = 'tx_tcaobjects_display_object';
	public $scriptRelPath = 'plugins/class.tx_tcaobjects_display_object.php';	// Path to this script relative to the extension dir.
	public $extKey        = 'tcaobjects';	// The extension key.
	
		
	/**
	 * @var tslib_cObj
	 */
	public $cObj;

	/**
	 * @var tx_smarty_wrapper
	 */
	protected $smarty;
	
	
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	public function main($content, array $conf)	{
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

        try{
        	
        	// tx_tcaobjects_div::$trackTime = true;
        	
        	try {
        		tx_pttools_div::mergeConfAndFlexform($this);	
        	} catch (tx_pttools_exception $excObj) {
        		// it's ok if there's no flexform configuration...
        	}
			
        	// setup smarty
            $this->smarty = tx_smarty::smarty();
            $this->smarty->caching = false;
            $this->smarty->debugging = true;
            $this->smarty->assign('conf', t3lib_div::removeDotsFromTS($this->conf));
    	    $this->smarty->setPathToLanguageFile('EXT:scrbl/locallang.xml');
 	    
		} catch (tx_pttools_exception $excObj) {
		    $GLOBALS['trace']=1; // TODO: only for development. Remove in production environment!
		    $excObj->handleException();
		    $GLOBALS['trace']=0; // TODO: only for development. Remove in production environment!
		} catch (Exception  $excObj){
		    $content .= '[ERROR: '.$excObj->__toString().']';
		}

		return $this->pi_wrapInBaseClass($content);
	}
	
	
}

?>