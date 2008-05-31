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

require_once PATH_t3lib . 'class.t3lib_userauthgroup.php';
require_once PATH_t3lib . 'class.t3lib_befunc.php';


/**
 * Abstract class tx_tcaobjects_object
 * This is the case class for all tcaobjects
 * 
 * $Id: class.tx_tcaobjects_object.php,v 1.41 2008/05/12 13:59:36 ry44 Exp $
 * 
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2008-03-20
 */
abstract class tx_tcaobjects_object implements ArrayAccess, IteratorAggregate {
	
	/**
	 * @var string	Name of the table. If empty get_class($this) will be used
	 */
	protected $_table = '';
	
	/**
	 * @var array	properties
	 */
	protected $_properties = array();
	
	/**
	 * @var array	configuration array for this class
	 */
	protected $_classConf = array();
	
	/**
	 * @var array	configuration array for the extension
	 */
	protected $_extConf = array();
	
	/**
	 * @var array	aliases
	 */
	protected $_aliasMap = array();
	
	/**
	 * @var array	ignored fields. Add your dynamic getters and setters to this list. Be careful if you inherit from another object then tx_tcaobjects_object. Use array_merge then!
	 */
	protected $_ignoredFields = array();
	
	/**
	 * @var array	values
	 */
	protected $_values = array();
	
	/**
	 * @var array	modifier list
	 */
	protected $_modifierList = array(	'obj', 
										'objColl', 
										'path',
										'explode',
										'rte',
										'sL',
									);
									
	/**
	 * @var string	comma separeted list of potential special fields
	 */
	const potentialSpecialFields = 'tstamp,crdate,cruser_id,delete,sortby';



	// Constructor
	// =========================================================================
	
	/**
	 * Constructor
	 *
	 * @param 	int		(optional) uid, if set record will be loaded from db
	 * @param 	array 	(optional) data array, if set properties will be set
	 * @param	bool	(optional) ignore enable fields
	 * @return	void
	 * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	public function __construct($uid = '', array $dataArr = array(), $ignoreEnableFields = false) {
		
		// Load backend class "language" to process language labels
		tx_tcaobjects_div::loadLang();
		
		// TODO: what if not in frontend context?
		$GLOBALS['TSFE']->includeTCA();
		
		// Set table
		if (empty($this->_table)) {
			$this->_table = get_class($this);
		}

		t3lib_div::loadTCA($this->_table);
		
		// Override and extend TCA settings with local settings from (inheriting) class 
		$this->_properties = t3lib_div::array_merge_recursive_overrule($GLOBALS['TCA'][$this->_table]['columns'], $this->_properties);
		
		// Standard fields
		$standardFields = array('uid', 'pid'); 
		foreach ($standardFields as $field) {
			$this->_properties[$field] = true;
		}
		
		// Special fields (tstamp, crdate,...)
		foreach (t3lib_div::trimExplode(',', self::potentialSpecialFields) as $specialField) {
			if (($fieldName = $this->getSpecialField($specialField)) !== false) {
				if (!is_array($this->_properties[$fieldName])) {
					$this->_properties[$fieldName] = true;
				}
			}
		}
		
		// Ignored fields
		foreach ($this->_ignoredFields as $field) {
			$this->_properties[$field] = true;
		}
		
		// New fields from the alias map
		foreach ($this->_aliasMap as $field => $target) {
			$this->_properties[$field] &= $this->_properties[$target];
		}
		
		// set class configuration array if found in registry
		$classConfLabel = get_class($this) . '_conf';
		if (tx_pttools_registry::getInstance()->has($classConfLabel)) {
			$this->_classConf = &tx_pttools_registry::getInstance()->get($classConfLabel);
		}
	
		// set extension configuration array if found in registry
		$extConfLabel = tx_tcaobjects_div::getCondensedExtKeyFromClassName(get_class($this)) . '_conf';
		if (tx_pttools_registry::getInstance()->has($extConfLabel)) {
			$this->_extConf = &tx_pttools_registry::getInstance()->get($extConfLabel);
		}
		
		// Populate with data
		if (!empty($uid)) {
			$this->loadSelf($uid, $ignoreEnableFields);
		} elseif (!empty($dataArr)) {
			$this->setDataArray($dataArr);
		}
		
	}



	// Data / DB handling
	// =========================================================================
	

	/**
	 * Load self by uid from table defined in $this->_table
	 *
	 * @param 	int		uid
	 * @param	bool	(optional) ignore enable fields
	 * @return	void
	 * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	public function loadSelf($uid, $ignoreEnableFields = false) {

		$dataArr = tx_tcaobjects_objectAccessor::selectByUid($uid, $this->_table, $ignoreEnableFields);
		if (is_array($dataArr)) {
			$this->setDataArray($dataArr);
		} else {
			throw new tx_pttools_exception('Record "' . $this->_table . ':' . $uid . '" could not be loaded', 0);
		}
	}



	/**
	 * Stores itself into the database (update or insert depending on if the uid is set)
	 *
	 * @param 	void
	 * @return	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	public function storeSelf($updateSubitems = false, $pidForMmRecords = 0) {

		// TODO: save aggregated objects and dependencies
		
		$dataArray = $this->getDataArray();
		if (($fieldName = $this->getSpecialField('tstamp')) !== false) {
			$dataArray[$fieldName] = time();
		}
		if (empty($dataArray['uid'])) {
			if (($fieldName = $this->getSpecialField('cruser_id')) !== false) {
				$dataArray[$fieldName] = $GLOBALS['TSFE']->fe_user->user['uid'];
			}
			if (($fieldName = $this->getSpecialField('crdate')) !== false) {
				$dataArray[$fieldName] = time();
			}		
		}
		
		$uid = tx_tcaobjects_objectAccessor::store($this->_table, $dataArray);
		$this->__set('uid', $uid);
		
		// process inline relations
		// TODO: only process them if objects were load (do NOT load them here if they were not loaded before!)
		foreach (array_keys($this->_properties) as $property) {
			if ($this->getConfig($property, 'type') == 'inline') {
				
				$foreign_table = $this->getConfig($property, 'foreign_table');
				$foreign_field = $this->getConfig($property, 'foreign_field');
				$foreign_sortby = $this->getConfig($property, 'foreign_sortby');
				$foreign_mm_field = $this->getConfig($property, 'foreign_mm_field');
				$isMM = tx_tcaobjects_div::ForeignTableIsMmTable($this->_table, $property);
				
				t3lib_div::loadTCA($foreign_table);
				
				$childRecords = tx_tcaobjects_objectAccessor::selectByParentUid(
					$this->uid, 
					$foreign_table, 
					$foreign_field, 
					$foreign_sortby
				);
				
				// TODO: what if 1:n and not m:n??
				$existingChilds = array();
				foreach ($childRecords as $child) {
					$existingChilds[] = $child[$foreign_mm_field];
				}
				
				$addedChilds = array();
				/* @var $item tx_tcaobjects_object */
				foreach ($this[$property . '_objColl'] as $item) {
					// update item
					if ($updateSubitems) {
						$subuid = $item->storeSelf($updateSubitems, $pidForMmRecords);
					} else {
						$subuid = $item['uid'];
						if (empty($subuid)) {
							// throw new tx_pttools_exception('Subitem has no uid! (Class: "'.get_class($item).'")');
							echo 'Subitem has no uid! (Class: "' . get_class($item) . '")'; // TODO: fix this 
						}
					}
					$addedChilds[] = $subuid;
					
					// update mm table if exists and relation was not saved yet
					if ($isMM && !in_array($subuid, $existingChilds)) {
						$dataArr = array();
						$dataArr['pid'] = $pidForMmRecords;
						$dataArr[$foreign_field] = $this->uid;
						$dataArr[$foreign_mm_field] = $subuid;
						tx_tcaobjects_objectAccessor::store($foreign_table, $dataArr);
					} // end if is mm-table and not saved yet
				} // end foreach item of the object collection
				

				// delete items not in the collection anymore
				if ($isMM) {
					foreach ($existingChilds as $child) {
						if (!in_array($child, $addedChilds)) {
							// delete relation
							$where = $foreign_field.'='.intval($this->uid);
							$where .= ' AND '.$foreign_mm_field.'='.intval($child);
							tx_tcaobjects_objectAccessor::deleteWhere($foreign_table, $where);
						}
					}
				}
			
			} // end if type is inline
		} // end foreach ($this->_properties
	} // end method
	
	
	
	/**
	 * Checks if this object is marked as deleted in the database
	 *
	 * @return 	bool
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-22
	 */
	public function isDeleted() {
		$deletedField = $GLOBALS['TCA'][$this->_table]['ctrl']['delete'];
		return $this->__get($deletedField);
	}
	
	/**
	 * Checks if this object is marked as disabled (hidden) in the database
	 *
	 * @return 	bool
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-22
	 */
	public function isDisabled() {
		$disabledField = $GLOBALS['TCA'][$this->_table]['ctrl']['enablecolumns']['disabled'];
		if (empty($disabledField)) {
			throw new tx_pttools_exception('No disabled field set in tca!');
		}
		return $this->__get($disabledField);
	}

	/**
	 * Deletes itself from the database
	 *
	 */
	public function deleteSelf($markOnlyAsDeleted = true) {

		// delete relations too
		foreach (array_keys($this->_properties) as $property) {
			if ($this->getConfig($property, 'type') == 'inline') {
				
				$foreign_table = $this->getConfig($property, 'foreign_table');
				$foreign_field = $this->getConfig($property, 'foreign_field');
				$foreign_mm_field = $this->getConfig($property, 'foreign_mm_field');
				t3lib_div::loadTCA($foreign_table);
				
				if (tx_tcaobjects_div::ForeignTableIsMmTable($this->_table, $property)) {
					
					$childRecords = tx_tcaobjects_objectAccessor::selectByParentUid(
						$this->uid, 
						$foreign_table, 
						$foreign_field, 
						$this->getConfig($property, 'foreign_sortby')
					);

					foreach ($childRecords as $child) {
						// delete relation
						$where = $foreign_field.'='.intval($this->uid);
						$where .= ' AND '.$foreign_mm_field.'='.intval($child[$foreign_mm_field]);
						tx_tcaobjects_objectAccessor::deleteWhere($foreign_table, $where);
					}
				}
			
			} // end if type is inline
		} // end foreach ($this->_properties
		

		if ($markOnlyAsDeleted) {
			if (($deletedFieldName = $this->getSpecialField('delete')) !== false) {
				tx_tcaobjects_objectAccessor::update($this->_table, array($deletedFieldName => 1, 'uid' => $this->uid));
			} else {
				throw new tx_pttools_exception('"'.$this->getIdentifier().'" cannot be marked as deleted as there is no delete field defined in the tca for the table');
			}
		} else {
			tx_tcaobjects_objectAccessor::delete($this->_table, $this->uid);
		}
	} // end method



	/**
	 * Set the object's property from the data of an array
	 *
	 * @param 	array 	dataArray
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	public function setDataArray(array $data) {
			
		foreach ($data as $calledProperty => $value) {
			
			if (substr($calledProperty, 0, 4) != 'zzz_') { // ignore old fields
				
				$property = $this->resolveAlias($calledProperty);
				
				if (!$this->offsetExists($property)) {
					// throw new tx_pttools_exception('Property "' . $property . '" (called property was: "' . $calledProperty . '") not valid!');
					echo 'Property "' . $property . '" (called property was: "' . $calledProperty . '") not valid!'.' ['.get_class($this).']<br />'.chr(10);
				} else {
							
					$this->__set($property, $value);
				}
			}
		}
	}



	/**
	 * Get the object's properties in an array
	 *
	 * @param 	void
	 * @return 	array	dataArray
	 * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	public function getDataArray() {

		$data = array();
		
		foreach (array_keys($this->_properties) as $property) {
			if (is_array($this->_properties[$property]) && $this->getConfig($property, 'type') != 'inline' || in_array($property, array('uid', 'pid', 'sorting'))) {
				// TODO: no data from aliases!					
				$data[$property] = $this->__get($property);
			}
		}
		return $data;
	}


	// Helper functions
	// =========================================================================





	/**
	 * Get raw property and special modifier (if any)
	 *
	 * @param 	string	property
	 * @return 	array	0 => raw property, 1 = special
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-20
	 */
	protected function getSpecial($property) {

		$propertyParts = explode('_', $property);
		$special = end($propertyParts);
		if (in_array($special, $this->_modifierList)) {
			array_pop($propertyParts);
		} else {
			$special = '';
		}
		$property = implode('_', $propertyParts);
		return array(0 => $property, 1 => $special);
	}
	

	/**
	 * Returns the value of the label field definied in the tca
	 *
	 * TODO: make accessible by ArrayAccess?
	 *
	 * @param 	void
	 * @return 	string
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-01-27
	 */
	public function getLabel() {
		return $this->__get($this->getLabelField());
	}



	/**
	 * Returns the name of the field that holds the label (defined in the TCA)
	 *
	 * @param 	void
	 * @return 	string	field name
	 * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	public function getLabelField() {
		return $GLOBALS['TCA'][$this->_table]['ctrl']['label'];
	}
	
	
	public function getSpecialField($specialField) {
		
		tx_pttools_assert::inArray(
			$specialField, 
			t3lib_div::trimExplode(',', self::potentialSpecialFields),
			array('message' => '"'.$specialField.'" is an invalid field name')
		);
		
		$fieldName = $GLOBALS['TCA'][$this->_table]['ctrl'][$specialField];
		return (!empty($fieldName) ? $fieldName : false ); 
	}
	
	
	/**
	 * Get the caption (label) for a property from the TCA
	 *
	 * @param 	string	property name
	 * @return 	string	caption
	 * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	public function getCaption($property) {
		return $this->_properties[$property]['label'];
	}
	
	
	/**
	 * Get the config for a property from the TCA
	 *
	 * @param 	string	property name
	 * @param 	string	config element
	 * @return 	string	internal type
	 * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	public function getConfig($property, $config) {
		
		tx_pttools_assert::notEmpty($property, array('message' => 'Parameter "property" empty!'));
		tx_pttools_assert::notEmpty($config, array('message' => 'Parameter "config" empty!'));
		
		return $this->_properties[$property]['config'][$config];
	}



	/**
	 * Returns the table
	 *
	 * @param 	void
	 * @return 	string	table name
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	public function getTable() {
		return $this->_table;
	}



	/**
	 * Returns an identifier for this record
	 *
	 * @param 	void
	 * @return 	string	identifier
	 * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	public function getIdentifier() {
		return get_class($this) . ':' . (!empty($this->uid) ? $this->uid : 'NEW'.time());
	}



	/**
	 * Returns an array of all field evaluation keys
	 *
	 * @param 	string	property
	 * @return 	array	evaluation keys
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-08
	 */
	public function getEval($property) {
		
		tx_pttools_assert::notEmpty($property, array('message', 'Parameter "property" empty!'));
		
		return t3lib_div::trimExplode(',', $this->getConfig($property, 'eval'));
	}



	/**
	 * Checks if the given property is a valid field or an alias of a valid field
	 * and returns the actual property
	 *
	 * @param 	string	property name
	 * @param 	bool	(optional) if true, then "false" is returned if property is not an alias
	 * @return  false|string	false or string with the property name
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-20
	 */
	public function resolveAlias($property, $returnFalseIfNoAlias = false) {

		if (!empty($this->_aliasMap[$property])) {
			return $this->_aliasMap[$property];
		} elseif ($returnFalseIfNoAlias == true) {
			return false;
		} else {
			return $property;
		}
	}

	
	
	/**
	 * Processes modifier "objColl". (Resolves table relations and returns an object
	 * collection with the loaded children)
	 * 
	 * Valid for TCA types 
	 * - "inline" 
	 * - "group" with internal_type "db"
	 * 
	 * @param 	string		property name
	 * @param 	string		property name that was originally called
	 * @return 	tx_tcaobjects_objectCollection 	object collection
	 * @throws	tx_pttools_exception
	 * @author	Fabrizio Branca <branca@punkt.de>
	 * @since	2008-04-27
	 */
	protected function processModifier_objColl($property, $calledProperty) {
		
		$type 			= $this->getConfig($property, 'type');
		$internal_type 	= $this->getConfig($property, 'internal_type');
		
		tx_pttools_assert::true(
			($type == 'inline') || (($type = 'group') && ($internal_type == 'db')), 
			array(
				'message' 			=> 'Invalid modifier for called property!',
				'modifier'			=> 'objColl',
				'type' 				=> $type,
				'internal_type' 	=> $internal_type,
				'property'			=> $property,
				'calledProperty' 	=> $calledProperty
			)
		);
		
		
		if ($type == 'inline') {
			/*******************************************************************
			 * Case 1: TCA type: "inline"
			 ******************************************************************/
			
			// create object collection
			$propertyCollectionName = $property . 'Collection';
			
			// Fallback if collection class does not exist
			$propertyCollectionName = class_exists($propertyCollectionName) ? $propertyCollectionName : 'tx_tcaobjects_objectCollection';
			
			$value = new $propertyCollectionName(); /* @var $value tx_tcaobjects_objectCollection */
			
			$foreign_table = $this->getConfig($property, 'foreign_table'); 
			tx_pttools_assert::notEmpty($foreign_table, array('message', 'No "foreign_table" defined for property "'.$property.'" in table "'.$this->_table.'"!'));
			
			$foreign_field = $this->getConfig($property, 'foreign_field');
			tx_pttools_assert::notEmpty($foreign_field, array('message', 'No "foreign_field" defined for property "'.$property.'" in table "'.$this->_table.'"!'));
			// TODO: If no foreign_field is defined, the field in the original table conatains a comma separeted list of uids in the foreign_table. See TYPO3 Core Apis. This may be implemented here...
			
			
			// array of records of the foreign table (mm records with uids or final data)
			$dataArr = tx_tcaobjects_objectAccessor::selectByParentUid(
				$this->uid, 
				$foreign_table,  
				$foreign_field, 
				$this->getConfig($property, 'foreign_sortby')
			);
			
			if (!empty($dataArr)) {
				
				$classname = tx_tcaobjects_div::getForeignClassName($this->_table, $property);
				
				$isMM = tx_tcaobjects_div::ForeignTableIsMmTable($this->_table, $property);
				
				if ($isMM == true) {
					$foreign_mm_field = $this->getConfig($property, 'foreign_mm_field');
				}
				
				// add items to the collection
				foreach ($dataArr as $data) {
					try {	
						// create object directly or by its uid (in case of an mm table)							
						$tmpObj = $isMM ? new $classname($data[$foreign_mm_field]) : new $classname('', $data); /* @var $tmpObj tx_tcaobjects_object */
						
						if ($tmpObj->isDeleted() == false) {
							$value->addItem($tmpObj);
						}
					} catch (tx_pttools_exception $exceptionObj) {
						$exceptionObj->handleException();
						$uid = $isMM ? $data[$foreign_mm_field] : $data['uid'];
						throw new tx_pttools_exception('Was not able to construct object "'.$classname.':'.$uid.'" (over mm-table: '. ($isMM ? 'yes' : 'no').') and add it to the collection!');
					}
				}
			}
			
		
		} elseif (($type == 'group') && ($internal_type == 'db')) {
			/*******************************************************************
			 * Case 2: TCA type "group"
			 ******************************************************************/
			throw new tx_pttools_exception('Not implemented yet :)');
		} 
		
		return $value;
	}
	
	
	
	/**
	 * Processes modifier "obj". (Returns object instead of uid)
	 * 
 	 * Valid for TCA type
 	 * - "group" with internal_type "db" and maxitems "1"
	 * 
	 * @param 	string		property name
	 * @param 	string		property name that was originally called
	 * @return 	tx_tcaobjects_object 	object
	 * @throws	tx_pttools_exception
	 * @author	Fabrizio Branca <branca@punkt.de>
	 * @since	2008-04-27
	 */
	protected function processModifier_obj($property, $calledProperty) {
		
		$type 			= $this->getConfig($property, 'type');
		$internal_type 	= $this->getConfig($property, 'internal_type');
		$maxitems 		= $this->getConfig($property, 'maxitems');
		
		tx_pttools_assert::true(
			($type == 'group') && ($internal_type == 'db') && ($maxitems == 1), 
			array(
				'message' 			=> 'Invalid modifier for called property!',
				'modifier'			=> 'obj',
				'type' 				=> $type,
				'internal_type' 	=> $internal_type,
				'maxitems' 			=> $maxitems,
				'property'			=> $property,
				'calledProperty' 	=> $calledProperty
			)
		);
	
		$classname = tx_tcaobjects_div::getClassname($this->_table, $property); 
		
		// TODO: can this be something like "tt_content_18"?
		return new $classname($this->_values[$property]);
	}
	
	
	
	/**
	 * Processes modifier "path". (Returns full path(s) in case of files)
	 * 
 	 * Valid for TCA type
 	 * - "group" with internal_type "file"
 	 * in case of maxitems > 1 this method will return a csl of full paths
	 * 
	 * @param 	string		property name
	 * @param 	string		property name that was originally called
	 * @return 	string		path, csl of paths or empty string if empty property
	 * @throws	tx_pttools_exception
	 * @author	Fabrizio Branca <branca@punkt.de>
	 * @since	2008-04-27
	 */
	protected function processModifier_path($property, $calledProperty) {
		
		$type 			= $this->getConfig($property, 'type');
		$internal_type 	= $this->getConfig($property, 'internal_type');
		
		tx_pttools_assert::true(
			($type == 'group') && ($internal_type == 'file'), 
			array(
				'message' 			=> 'Invalid modifier for called property!',
				'modifier'			=> 'path',
				'type' 				=> $type,
				'internal_type' 	=> $internal_type,
				'property'			=> $property,
				'calledProperty' 	=> $calledProperty
			)
		);
		
		$files = array();

		if (!empty($this->_values[$property])) {

			$uploadFolder = $this->getConfig($property, 'uploadfolder'); 
			
			tx_pttools_assert::notEmpty($uploadFolder, array('message' => 'Configuration "uploadfolder" was not defined for property "'.$property.'" in table "'.$this->_table.'"!'));
						
			foreach (t3lib_div::trimExplode(',', $this->_values[$property]) as $singleFile) {
				$files[] = $uploadFolder . DIRECTORY_SEPARATOR . $singleFile;
			}
			return implode(',', $files); 
		} else {
			return '';
		}
	}
	
	
	
	/**
	 * Processes modifier "explode". (Returns an array (value => label) instead of 
	 * a comma separated list in case of a "select" field
	 * 
 	 * Valid for TCA type
 	 * - "select"
	 * 
	 * @param 	string		property name
	 * @param 	string		property name that was originally called
	 * @return 	array		value => label
	 * @throws	tx_pttools_exception
	 * @author	Fabrizio Branca <branca@punkt.de>
	 * @since	2008-04-27
	 */
	protected function processModifier_explode($property, $calledProperty) {
		
		$type = $this->getConfig($property, 'type');
		
		tx_pttools_assert::true(
			($type == 'select'), 
			array(
				'message' 			=> 'Invalid modifier for called property!',
				'modifier'			=> 'explode',
				'type' 				=> $type,
				'property'			=> $property,
				'calledProperty' 	=> $calledProperty
			)
		);
			
		$valueArray = array();
		
		if (!empty($this->_values[$property])) {
			foreach (t3lib_div::trimExplode(',', $this->_values[$property]) as $value) {
				// search item
				foreach ($this->getConfig($property, 'items') as $item) {
					if ($item[1] == $value)
						break;
				}
				$valueArray[$value] = $GLOBALS['LANG']->sL($item[0]);
			}
		}
		return $valueArray;
	}
	
	
	
	/**
	 * Processes modifier "sL". (Returns the language label in case of a 
	 * single "select" value)
	 * 
 	 * Valid for TCA type
 	 * - "select" with maxitems "1"
	 * 
	 * @param 	string		property name
	 * @param 	string		property name that was originally called
	 * @return 	string		label or empty string
	 * @throws	tx_pttools_exception
	 * @author	Fabrizio Branca <branca@punkt.de>
	 * @since	2008-04-27
	 */
	protected function processModifier_sL($property, $calledProperty) {
		
		$type 		= $this->getConfig($property, 'type');
		$maxitems 	= $this->getConfig($property, 'maxitems');
		
		tx_pttools_assert::true(
			($type == 'select') && ($maxitems == 1), 
			array(
				'message' 			=> 'Invalid modifier for called property!',
				'modifier'			=> 'sL',
				'type' 				=> $type,
				'maxitems'			=> $maxitems,
				'property'			=> $property,
				'calledProperty' 	=> $calledProperty
			)
		);
		
		$value = $this->_values[$property];
		 	
		if (!empty($value)) {
			
			// search item
			foreach ($this->getConfig($property, 'items') as $item) {
				if ($item[1] == $value) {
					break;
				}
			}
			return $GLOBALS['LANG']->sL($item[0]);
		} else {
			return '';						
		}
	}
	
	
	
	/**
	 * Processes modifier "rte". (Renders the value as an Rich Text Editor field)
	 * 
 	 * Valid for all fields
	 * 
	 * @param 	string		property name
	 * @param 	string		property name that was originally called
	 * @return 	string		HTML Output
	 * @throws	tx_pttools_exception
	 * @author	Fabrizio Branca <branca@punkt.de>
	 * @since	2008-04-27
	 */
	protected function processModifier_rte($property, $calledProperty) {
		$parseFunc = $GLOBALS['TSFE']->tmpl->setup['config.']['tx_tcaobjects.']['parseFunc_RTE.'];
		
		tx_pttools_assert::isArray(
			$parseFunc, 
			array(
				'message' 			=> 'No parseFunc defined under "config.tx_tcaobjects.parseFunc_RTE"!',
				'calledProperty' 	=> $calledProperty,
				'property'			=> $property,
				'modifier'			=> 'rte'
			)
		);
		
		return $GLOBALS['TSFE']->cObj->parseFunc($this->__get($property), $parseFunc);		
	}
	
	
	
	
	
	

	// Magic Methods: __get(), __set(), __call()
	// =========================================================================
	

	/**
	 * Getting raw or modified (with special modifiers) property values.
	 * "Lazy loading" for modifier values
	 *
	 * @param 	string		property name
	 * @return 	mixed		property value
	 * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	protected function __get($calledProperty) {
		
		list ($orig_property, $modifier) = $this->getSpecial($calledProperty);
		
		$property = $this->resolveAlias($orig_property);
				
		if (!$this->offsetExists($calledProperty)) {
			throw new tx_pttools_exception('Property "' . $property . '" (called property was: "' . $calledProperty . '") not valid!');
		}
		
		$calledProperty = $property.((!empty($modifier)) ? '_'.$modifier : '');
		
		// process modifier if any
		if (!empty($modifier) && empty($this->_values[$calledProperty])) {
			$modifierMethodName = 'processModifier_'.$modifier;
			if (method_exists($this, $modifierMethodName)) {
				$this->_values[$calledProperty] = $this->$modifierMethodName($property, $calledProperty);
			} else {
				throw new tx_pttools_exception('No handler method found for modifier "'.$modifier.'"!');
			}
		}
						
		return $this->_values[$calledProperty];
	}



	/**
	 * Setting properties directly with type check ($this->user = $userObj)
	 *
	 * @param 	string	property
	 * @param 	mixed	value to be set
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-20
	 */
	protected function __set($calledProperty, $value) {

		$property = $this->resolveAlias($calledProperty);
		
		if (!$this->offsetExists($property)) {
			throw new tx_pttools_exception('Property "' . $property . '" (called property was: "' . $calledProperty . '") not valid and cannot be set!'.' ['.__CLASS__."::".__FUNCTION__.'(...)]');
		}
		
		$this->_values[$property] = $value;
		
		// TODO: type checking with information from tca
		// TODO: special "objColl", "obj",... ?
		
	}
	
	
	/**
	 * get_* / set_* for properties
	 *
	 * @param 	string	method name
	 * @param 	array	array of paramaters, use index "0" for setting values!
	 * @return 	mixed/void	in case of getter the value of the property will be returned
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	protected function __call($methodName, $parameters) {

		$methodParts = explode('_', $methodName);
		
		$prefix = array_shift($methodParts);
		
		if (in_array($prefix, array('get', 'set'))) {
			
			$calledProperty = implode('_', $methodParts);
			
			$property = $this->resolveAlias($calledProperty);
			
			if (!$this->offsetExists($property)) {
				throw new tx_pttools_exception('Property "' . $property . '" (called property was: "' . $calledProperty . '") not valid!'.' ['.__CLASS__."::".__FUNCTION__.'(...)]');
			}
			
			switch ($prefix){
				case 'get': {
					/**
					 * Dynamic getter
					 */
					return $this->__get($property);
				} break;
				case 'set': {
					/**
					 * Dynamic setter
					 */
					$this->__set($property, $parameters[0]);
					return '';
				}
			}
		} elseif (t3lib_div::isFirstPartOfStr($methodName, 'find_by_')) {
			/**
			 * "Dynamic finder"
			 */
			// TODO: to be tested and improved!
			$fieldname = str_replace('find_by_', '', $methodName);
			if (!$this->offsetExists($fieldname)) {
				throw new tx_pttools_exception('Field "'.$fieldname.'" (called method was: "'.$methodName.'") does not exist!');	
			}
			$where = $fieldname.'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($parameters[0], $this->_table);
			$dataArr = tx_tcaobjects_objectAccessor::selectCollection($this->_table, $where, 1);
			if (is_array($dataArr)) {
				$this->setDataArray($dataArr);
			} else {
				throw new tx_pttools_exception('No record found "' . $fieldname . ':' . $parameters[0] . '"');
			}
		} else {
			throw new ReflectionException('"'.$methodName.'" is no valid method (Only getters, setters and special methods allowed!)');
		}
		return '';
	}



	// Interface: ArrayAccess
	// =========================================================================
	
	/**
	 * Checks if the offset exists
	 *
	 * @param 	string	offset
	 * @return 	bool
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-20
	 */
	public function offsetExists($offset) {

		$propertyParts = explode('_', $offset);
		
		$special = end($propertyParts);
		if (in_array($special, $this->_modifierList)) {
			array_pop($propertyParts);
		}
		
		$property = implode('_', $propertyParts);
		
		return key_exists($property, $this->_properties);
	}


	
	/**
	 * Gets the value of the offst
	 *
	 * @param 	string	offset
	 * @return 	mixed
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-20
	 */
	public function offsetGet($offset) {
		return $this->__get($offset);
	}

	

	/**
	 * Set value for an offset	
	 *
	 * @param 	string	offset
	 * @param 	mixed	value
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-20
	 */
	public function offsetSet($offset, $value) {
		$this->__set($offset, $value);
	}


	
	/**
	 * Unset an offset value
	 *
	 * @param 	string	offset
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-20
	 */
	public function offsetUnset($offset) {
		unset($this->_values[$offset]);
	}



	// Interface: IteratorAggregate
	// =========================================================================
	
	/**
	 * Returns an iterator object
	 *
	 * @param 	void
	 * @return 	Iterator
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-03-20
	 */
	public function getIterator() {
		return new ArrayIterator($this); // works because this class implements ArrayAccess :)
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_object.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_object.php']);
}

?>