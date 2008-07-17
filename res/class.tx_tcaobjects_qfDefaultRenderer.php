<?php

require_once 'HTML/QuickForm/Renderer/Default.php';

class tx_tcaobjects_qfDefaultRenderer extends HTML_QuickForm_Renderer_Default implements tx_tcaobjects_iQuickformRenderer {
	
	
	
	/**
	 * Constructor
	 *
	 * @param 	string	(optional) templateFile
	 * @author	Fabrizio Branca <branca@punkt.de>
	 * @since	2008-06-22
	 */
	public function __construct($templateFile = null) {
		parent::HTML_QuickForm_Renderer_Default(); // original constructor
		if (!is_null($templateFile)) {
			$this->setTemplateFile($templateFile);
		}
	}
	
	
	
	/**
	 * Set the template from a template file
	 *
	 * @param 	string	path to the template file (EXT:... is supported here)
	 * @return 	void
	 * @author	Fabrizio Branca <branca@punkt.de>
	 * @since	2008-04-27
	 */
	public function setTemplateFile($templateFile) {
		
		tx_pttools_assert::isFilePath($templateFile);
		
	    $fileContent = $GLOBALS['TSFE']->cObj->fileResource($templateFile);
	    
	    $subparts = array();
	    $subparts['elementTemplate'] 		= $GLOBALS['TSFE']->cObj->getSubpart($fileContent, '###ELEMENTTEMPLATE###');
		$subparts['groupElementTemplate'] 	= $GLOBALS['TSFE']->cObj->getSubpart($fileContent, '###GROUPELEMENTTEMPLATE###');
		$subparts['formTemplate'] 			= $GLOBALS['TSFE']->cObj->getSubpart($fileContent, '###FORMTEMPLATE###');
		$subparts['headerTemplate'] 		= $GLOBALS['TSFE']->cObj->getSubpart($fileContent, '###HEADERTEMPLATE###');
		$subparts['requiredNoteTemplate'] 	= $GLOBALS['TSFE']->cObj->getSubpart($fileContent, '###REQUIREDNOTETEMPLATE###');
		$subparts['groupTemplate'] 			= $GLOBALS['TSFE']->cObj->getSubpart($fileContent, '###GROUPTEMPLATE###');
		
		if (!empty($subparts['elementTemplate'])) {
			$this->setElementTemplate($subparts['elementTemplate']);
		}
		
		if (!empty($subparts['groupElementTemplate'])) {
			$this->setGroupElementTemplate($subparts['groupElementTemplate']);
		}
		
		if (!empty($subparts['formTemplate'])) {
			$this->setFormTemplate($subparts['formTemplate']);
		}
		
		if (!empty($subparts['headerTemplate'])) {
			$this->setHeaderTemplate($subparts['headerTemplate']);
		}
		
		if (!empty($subparts['requiredNoteTemplate'])) {
			$this->setRequiredNoteTemplate($subparts['requiredNoteTemplate']);
		}
		
		if (!empty($subparts['groupTemplate'])) {
			$this->setGroupTemplate($subparts['groupTemplate']);
		}
		
	}
	
	
	/***************************************************************************
	 * Overwrite some methods from original default renderer
	 **************************************************************************/
	
	
	/**
    * Called when visiting a group, before processing any group elements
    *
    * @param object     An HTML_QuickForm_group object being visited
    * @param bool       Whether a group is required
    * @param string     An error message associated with a group
    * @access public
    * @return void
    */
    public function startGroup (&$group, $required, $error) {
        $name = $group->getName();
        $this->_groupTemplate        = $this->_prepareTemplate($name, $group->getLabel(), $required, $error);
        if (!empty($this->_groupTemplates[$name])) {
        	$this->_groupElementTemplate = $this->_groupTemplates[$name];
        }
        if (!empty($this->_groupWraps[$name])) {
        	$this->_groupWrap            = $this->_groupWraps[$name];	
        }
        
        $this->_groupElements        = array();
        $this->_inGroup              = true;
    } // end func startGroup

    
    
    /**
     * Sets element template for elements within a group
     *
     * @param       string      The HTML surrounding an element 
     * @param       string      Name of the group to apply template for
     * @access      public
     * @return      void
     */
    public function setGroupElementTemplate($html, $group = '') {
    	if (empty($group)) {
    		$this->_groupElementTemplate = $html;	
    	} else {
        	$this->_groupTemplates[$group] = $html;
    	}
    } // end func setGroupElementTemplate


    /**
     * Sets template for a group wrapper 
     * 
     * This template is contained within a group-as-element template 
     * set via setTemplate() and contains group's element templates, set
     * via setGroupElementTemplate()
     *
     * @param       string      The HTML surrounding group elements
     * @param       string      Name of the group to apply template for
     * @access      public
     * @return      void
     */
    public function setGroupTemplate($html, $group = '') {
    	if (empty($group)) {
    		$this->_groupWrap = $html;
    	} else {
        	$this->_groupWraps[$group] = $html;
    	} 
    }
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_qfDefaultRenderer.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_qfDefaultRenderer.php']);
}

?>