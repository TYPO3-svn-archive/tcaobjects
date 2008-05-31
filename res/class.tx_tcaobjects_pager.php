<?php



/**
 * Pager object. Creates page browser for object collections that implement the tx_tcaobjects_iPageable interface
 * 
 * $Id: class.tx_tcaobjects_pager.php,v 1.7 2008/05/12 13:59:36 ry44 Exp $
 * 
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2008-03-15
 */
class tx_tcaobjects_pager {
	/**
	 * @var tx_tcaobjects_iPageable
	 */
	protected $objectCollection;
	/**
	 * @var int
	 */
	protected $itemsPerPage;
	/**
	 * @var string
	 */
	protected $templateForPager;
	/**
	 * @var array
	 */
	protected $pagerTypoLinkConf;
	/**
	 * @var int
	 */
	protected $pagerDelta = 5;
	/**
	 * @var int
	 */	
	protected $currentPageNumber;
	/**
	 * @var int
	 */	
	protected $totalItemCount;
	/**
	 * @var string
	 */
	protected $templateLanguageFile;
	/**
	 * @var array
	 */
	protected $templateAdditionalMarkers = array();
	/**
	 * @var int
	 */
	protected $amountPages;
	/**
	 * @var string
	 */
	protected $classCurrent = 'current';
	/**
	 * @var string
	 */
	protected $classFirst = 'first';
	/**
	 * @var string
	 */
	protected $classLast = 'last';
	/**
	 * @var string
	 */
	protected $classNext = 'next';
	/**
	 * @var string
	 */
	protected $classPrev = 'prev';
	/**
	 * @var string
	 */
	protected $classDefault = 'default';
	/**
	 * @var string
	 */
	protected $parameterName;
	/**
	 * @var string
	 */
	protected $objCollWhere = '';
	/**
	 * @var string
	 */
	protected $objCollOrder = '';
	/**
	 * @var array
	 */
	protected $carryAlongParameter = array();
	
	
	
	
	/**
	 * Constructor
	 *
	 * @param 	int	current page number
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-15
	 */
	public function __construct($currentPageNumber) {
		$this->currentPageNumber = empty($currentPageNumber) ? 1 : $currentPageNumber;
	}
	
	
	
	/**
	 * Set object collection (and implicitely set the totelItemCount and the amountPages)
	 *
	 * @param 	tx_tcaobjects_iPageable 	object collection
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-15
	 */
	public function set_objectCollection(tx_tcaobjects_iPageable $objectCollection) {
		$this->objectCollection = $objectCollection;
		$this->totalItemCount = $objectCollection->getTotalItemCount($this->objCollWhere);
		if (empty($this->itemsPerPage)) {
			throw new tx_pttools_exception('Property itemsPerPage is empty. Please set it to a value > 0 before setting the object collection');
		}
		$this->amountPages = ceil($this->totalItemCount / $this->itemsPerPage);
	}
	
	
	
	/***************************************************************************
	 * Simple setters for all remaining properties
	 **************************************************************************/
	
	public function set_itemsPerPage($itemsPerPage) {
		$this->itemsPerPage = $itemsPerPage;
	}
	
	public function set_templateForPager($templateForPager) {
		$this->templateForPager = $templateForPager;
	}
	
	public function set_pagerTypoLinkConf(array $pagerTypoLinkConf) {
		$this->pagerTypoLinkConf = $pagerTypoLinkConf;
	}
	
	public function set_currentPageNumber($currentPageNumber) {
		$this->currentPageNumber = $currentPageNumber;
	}
		
	public function set_templateLanguageFile($templateLanguageFile) {
		$this->templateLanguageFile = $templateLanguageFile;
	}
	
	public function set_templateAdditionalMarkers(array $templateAdditionalMarkers) {
		$this->templateAdditionalMarkers = $templateAdditionalMarkers;
	}
	
	public function set_pagerDelta($pagerDelta) {
		$this->pagerDelta = $pagerDelta;
	}
	
	public function set_classCurrent($classCurrent) {
		$this->classCurrent = $classCurrent;
	}
	
	public function set_classFirst($classFirst) {
		$this->classFirst = $classFirst;
	}
	
	public function set_classLast($classLast) {
		$this->classLast = $classLast;
	}
	
	public function set_classNext($classNext) {
		$this->classNext = $classNext;
	}
	
	public function set_classPrev($classPrev) {
		$this->classPrev = $classPrev;
	}
	
	public function set_classDefault($classDefault) {
		$this->classDefault = $classDefault;
	}
	
	public function set_parameterName($parameterName) {
		$this->parameterName = $parameterName;
	}
	
	public function set_objCollWhere($objCollWhere) {
		$this->objCollWhere = $objCollWhere;
	}
	
	public function set_objCollOrder($objCollOrder) {
		$this->objCollOrder = $objCollOrder;
	}
	
	public function set_carryAlongParameter(array $carryAlongParameter) {
		$this->carryAlongParameter = $carryAlongParameter;
	}
	
	
	
	/**
	 * Returns the object collection for the current page
	 *
	 * @param 	void
	 * @return 	tx_tcaobject_objectCollection	current object collection
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-15
	 */
	public function getObjectCollectionForPage() {
	    $offset = (($this->itemsPerPage) * ($this->currentPageNumber - 1 ));
        $rowcount = $this->itemsPerPage;

        $limit = $offset.','.$rowcount;
        
		return $this->objectCollection->getItems($this->objCollWhere, $limit, $this->objCollOrder);
	}
	
	
	
	/**
	 * Renders the pager
	 *
	 * @return 	string 	HTML Output
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-13
	 */
	public function renderPager() {
    	
    	if (!is_array($this->pagerTypoLinkConf)) {
    		throw new tx_pttools_exception('Typolink configuration is not an array!');
    	}
    	
    	if (empty($this->objectCollection)) {
    		throw new tx_pttools_exception('Object collection was not set!');
    	}
    	
		if (empty($this->parameterName)) {
    		throw new tx_pttools_exception('Property parameterName was not set!');
    	}
        
    	// Create smarty object
        $smarty = tx_smarty::smarty();
        
        if (!empty($this->templateAdditionalMarkers)) {
        	$smarty->assign($this->templateAdditionalMarkers);
        }
        if (!empty($this->templateLanguageFile)) {
        	$smarty->setPathToLanguageFile($this->templateLanguageFile);	
        }
        
        // Prepare typolink
        $this->pagerTypoLinkConf['additionalParams'] = $GLOBALS['TSFE']->cObj->stdWrap($this->pagerTypoLinkConf['additionalParams'], $this->pagerTypoLinkConf['additionalParams.']);
        
        // This can be handled by typolink.addQueryString, too 
		foreach ($this->carryAlongParameter as $parameter) {
			$this->pagerTypoLinkConf['additionalParams'] .= '&'.$parameter.'='.t3lib_div::GPvar($parameter);
		}
        
        if ($this->amountPages > 1) {
        
            $markerArray = array();
                        
            for ($i = 1; $i <= $this->amountPages; $i++) {
                if (abs($i-$this->currentPageNumber) <= $this->pagerDelta || ($i == 1) || ($i == $this->amountPages) ) {
                    
                    if ($i == $this->currentPageNumber) {
                        $class = $this->classCurrent;
                    } elseif ($i == 1) {
                        $class = $this->classFirst;
                    } elseif ($i == $this->amountPages) {
                        $class = $this->classLast;
                    } else {
                        $class = $this->classDefault;
                    }

                    $tmpTypolinkConf = $this->pagerTypoLinkConf;
		    	    $tmpTypolinkConf['additionalParams'] .= '&'.$this->parameterName.'='.$i;
		    	    $url = $GLOBALS['TSFE']->cObj->typoLink_URL($tmpTypolinkConf);
                    
                    $markerArray['pages'][] = array ('label' => $i,
                                                     'url' =>  $url,
                    								 'class' => $class
                                                    );
                } elseif (end($markerArray['pages']) != 'fill') {
                    $markerArray['pages'][] = 'fill';
                }
                
            }
            
            // "previous page" link
            $prevpage = max($this->currentPageNumber - 1, 1);
            
            $tmpTypolinkConf = $this->pagerTypoLinkConf;
    	    $tmpTypolinkConf['additionalParams'] .= '&'.$this->parameterName.'='.$prevpage;
    	    $url = $GLOBALS['TSFE']->cObj->typoLink_URL($tmpTypolinkConf);
            
            $markerArray['prev'] = array (   'url' => $url,
            								 'class' => $this->classPrev,
            								 'alreadyhere' => ($prevpage == $this->currentPageNumber)
                                            );
    	
    		// "next page" link
            $nextpage = min($this->currentPageNumber + 1, $this->amountPages);
            
            $tmpTypolinkConf = $this->pagerTypoLinkConf;
    	    $tmpTypolinkConf['additionalParams'] .= '&'.$this->parameterName.'='.$nextpage;
    	    $url = $GLOBALS['TSFE']->cObj->typoLink_URL($tmpTypolinkConf);
    	    
            $markerArray['next'] = array (	 'url' =>  $url,
                                    		 'class' => $this->classNext,
            								 'alreadyhere' => ($nextpage == $this->currentPageNumber)
                                            );
            
            foreach ($markerArray as $markerKey => $markerValue) {
                $smarty->assign($markerKey, $markerValue);
            }
            
            return $smarty->fetch('file:'.t3lib_div::getFileAbsFileName($this->templateForPager));
        } else {
            return ''; // no pager   
        }
    }	
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_pager.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_pager.php']);
}

?>