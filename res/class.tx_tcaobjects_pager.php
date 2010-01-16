<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2008 Fabrizio Branca (mail@fabrizio-branca.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is 
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
* 
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


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
	 * Set pager configuration by typoscript
	 *
	 * @param 	array 	typoscript configuratation
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-10
	 */
	public function set_typoscriptConfiguration(array $conf) {
		
		if (isset($conf['typoLinkConf.'])) {
			$this->set_pagerTypoLinkConf($conf['typoLinkConf.']);
			unset($conf['typoLinkConf.']);
		}
		
		if (isset($conf['additionalMarkers.'])) {
			$additionalMarkers = tx_tcaobjects_div::stdWrapArray($conf['additionalMarkers.']);
			$this->set_templateAdditionalMarkers($additionalMarkers);
			unset($conf['additionalMarkers.']);
		}
		
		if (isset($conf['carryAlongParameters.'])) {
			$carryAlongParameters = tx_tcaobjects_div::stdWrapArray($conf['carryAlongParameters.']);
			$this->set_carryAlongParameter($carryAlongParameters);
			unset($conf['carryAlongParameters.']);
		}
		
		$conf = tx_tcaobjects_div::stdWrapArray($conf);
		
		foreach ($conf as $key => $value) {

			switch ($key) {
				case 'template': 			$this->set_templateForPager($value); break;
				case 'delta': 				$this->set_pagerDelta($value); break;
				case 'languageFile':		$this->set_templateLanguageFile($value); break;
				case 'parameterName': 		$this->set_parameterName($value); break;
				case 'itemsPerPage':		$this->set_itemsPerPage($value); break;
				case 'objCollWhere':		$this->set_objCollWhere($value); break;
				case 'objCollOrder':		$this->set_objCollOrder($value); break;
				case 'classCurrent':		$this->set_classCurrent($value); break;
				case 'classFirst':			$this->set_classFirst($value); break;
				case 'classLast':			$this->set_classLast($value); break;
				case 'classNext':			$this->set_classNext($value); break;
				case 'classPrev':			$this->set_classPrev($value); break;
				default: throw new tx_pttools_exception('"'.$key.'" is an not valid for pager configuration!');
			}
			
		}
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
	
	public function add_templateAdditionalMarkers(array $templateAdditionalMarkers) {
		$this->templateAdditionalMarkers = t3lib_div::array_merge_recursive_overrule($this->templateAdditionalMarkers, $templateAdditionalMarkers);
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
		
		tx_pttools_assert::isArray($this->pagerTypoLinkConf, array('message' => 'Typolink configuration is not an array!'));
		tx_pttools_assert::isNotEmpty($this->objectCollection, array('message' => 'Object collection was not set!'));
		tx_pttools_assert::isNotEmptyString($this->parameterName, array('message' => 'Property parameterName was not set!'));  
        
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
			$this->pagerTypoLinkConf['additionalParams'] .= '&'.$parameter.'='.t3lib_div::_GP($parameter);
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
                    
                    $markerArray['pages'][] = array (
                    	'label' => $i,
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
            
            $markerArray['prev'] = array (  
            	'url' => $url,
            	'class' => $this->classPrev,
            	'alreadyhere' => ($prevpage == $this->currentPageNumber)
            );
    	
    		// "next page" link
            $nextpage = min($this->currentPageNumber + 1, $this->amountPages);
            
            $tmpTypolinkConf = $this->pagerTypoLinkConf;
    	    $tmpTypolinkConf['additionalParams'] .= '&'.$this->parameterName.'='.$nextpage;
    	    $url = $GLOBALS['TSFE']->cObj->typoLink_URL($tmpTypolinkConf);
    	    
            $markerArray['next'] = array (	 
            	'url' =>  $url,
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