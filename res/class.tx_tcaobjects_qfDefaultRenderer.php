<?php

require_once 'HTML/QuickForm/Renderer/Default.php';

class tx_tcaobjects_qfDefaultRenderer extends HTML_QuickForm_Renderer_Default implements tx_tcaobjects_iQuickformRenderer, tx_tcaobjects_iInitializeByTyposcript {



	/**
	 * Constructor
	 *
	 * @param 	string	(optional) templateFile
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-22
	 */
	public function __construct($templateFile = null) {
		parent::HTML_QuickForm_Renderer_Default(); // original constructor
		if (!is_null($templateFile)) {
			$this->setTemplateFile($templateFile);
		}
	}
	

	
	/**
	 * Initialize by typoscript
	 * (Implementing the "tx_tcaobjects_iInitializeByTyposcript" interface)
	 * 
	 * @param array typoscript
	 * @return void
	 */
	public function initializeByTyposcript(array $typoscript) {
		if (!empty($typoscript['templateFile'])) {
			$template = $GLOBALS['TSFE']->cObj->stdWrap($typoscript['templateFile'], $typoscript['templateFile.']);
			$this->setTemplateFile($template);
		}
	}

	

	/**
	 * Set the template from a template file
	 *
	 * @param 	string	path to the template file (EXT:... is supported here)
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
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
    public function startGroup(&$group, $required, $error) {

        $name = $group->getName();
        $this->_groupTemplate = $this->_prepareTemplate($name, $group->getLabel(), $required, $error);

        if (!empty($this->_groupTemplates[$name])) {
        	$this->_groupElementTemplate = $this->_groupTemplates[$name];
        }
        if (!empty($this->_groupWraps[$name])) {
        	$this->_groupWrap            = $this->_groupWraps[$name];
        }

        $this->_groupWrap = $this->replace('comment', $group->getComment(), $this->_groupWrap);

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



    /**
     * Replace marker and remove complete subpart if content is not set
     *
     * @param 	string 	marker
     * @param 	string 	content
     * @param 	string 	html
     * @return 	string	html
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2009-04-19
     */
    public function replace($marker, $content, $html) {
		if (!empty($content)) {
            $html = str_replace('{'.$marker.'}', $content, $html);
            $html = str_replace('<!-- BEGIN '.$marker.' -->', '', $html);
            $html = str_replace('<!-- END '.$marker.' -->', '', $html);
        } else {
        	// if there is no comment the whole "subpart" will be removed
            $html = preg_replace("/([ \t\n\r]*)?<!-- BEGIN ".$marker." -->.*<!-- END ".$marker." -->([ \t\n\r]*)?/isU", '', $html);
        }
        return $html;
    }



   /**
    * Helper method for renderElement
    *
    * @param    string      Element name
    * @param    mixed       Element label (if using an array of labels, you should set the appropriate template)
    * @param    bool        Whether an element is required
    * @param    string      Error message associated with the element
    * @param 	string		(optional) id of the element that will replace {id}, default is ''
    * @access   private
    * @see      renderElement()
    * @return   string      Html for element
    */
    public function _prepareTemplate($name, $label, $required, $error, HTML_QuickForm_element $element = null) {
    	if ($element instanceof tx_tcaobjects_qfRawStatic) {
    		$html = '{element}';
    	} else {
	    	$html = parent::_prepareTemplate($name, $label, $required, $error);
	    	if (!is_null($element)) {
		    	$html = str_replace('{id}', $element->getAttribute('id'), $html);
		    	$html = $this->replace('comment', $element->getComment(), $html);

	    	} else {
	    		// if the element is NULL all markers will be deleted
	    		$html = str_replace('{id}', '', $html);
	    		$html = $this->replace('comment', '', $html);
	    	}
    	}
    	return $html;
    }


    /**
     * Renders an element Html
     * Called when visiting an element
     *
     * Overrides the original method
     *
     * @param HTML_QuickForm_element form element being visited
     * @param bool                   Whether an element is required
     * @param string                 An error message associated with an element
     * @access public
     * @return void
     */
    public function renderElement(HTML_QuickForm_element $element, $required, $error) {
        if (!$this->_inGroup) {

        	// element is not in a group

        	// passes $element to "_prepareTemplate"
            $html = $this->_prepareTemplate($element->getName(), $element->getLabel(), $required, $error, $element);
            $this->_html .= str_replace('{element}', $element->toHtml(), $html);

        } else {

        	// element is in a group

        	if (!empty($this->_groupElementTemplate)) {

        		// TODO: why not use "_prepareTemplate()" here?

        		$html = str_replace('{label}', $element->getLabel(), $this->_groupElementTemplate);
	            // replaces "{id}" with current element's id
	            $html = str_replace('{id}', $element->getAttribute('id'), $html);


		    	$html = $this->replace('comment', $element->getComment(), $html);

	            if ($required) {
	                $html = str_replace('<!-- BEGIN required -->', '', $html);
	                $html = str_replace('<!-- END required -->', '', $html);
	            } else {
	                $html = preg_replace("/([ \t\n\r]*)?<!-- BEGIN required -->.*<!-- END required -->([ \t\n\r]*)?/isU", '', $html);
	            }
	            $this->_groupElements[] = str_replace('{element}', $element->toHtml(), $html);

        	} else {

        		$html = $element->toHtml();

        		$this->_groupElements[] = $html;

        	}
        }
    } // end func renderElement

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_qfDefaultRenderer.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_qfDefaultRenderer.php']);
}

?>