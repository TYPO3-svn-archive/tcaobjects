<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2010 Fabrizio Branca (mail@fabrizio-branca.de)
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
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2008-03-20
 */
abstract class tx_tcaobjects_object implements ArrayAccess, IteratorAggregate {
    /**
     * @var string	Name of the table. If empty $this->getClassName() will be used
     */
    protected $_table = '';
    
    /**
     * @var string class name
     */
    protected $_className = null;

    /**
     * @var array	properties
     */
    protected $_properties = array();

    /**
     * @var array	"dynamic properties" (implement a get_<dynamicPropertyName>() method for each of those)
     */
    protected $_dynamicProperties = array();

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
    protected $_modifierList = array(
    	'obj',
        'objColl',
        'path',
        'explode',
        'rte',
        'sL',
    	'label', // returns the label of the field as defined in TCA
    	'values', // returns all possible values for this field (in case of selects)
    	'multilang', // returns all property in all languages <span class="lang-0">Default language</span><span class="lang-1">Translation</span>
    	'multilanghsc' // same as multilang, but applies hsc on all values before wrapping them into spans
	);
	
	/**
	 * @var array specialFieldKey => fieldName
	 */
	protected $_specialFields = array();
	
	/**
	 * @var array enableColumnKey => fieldName
	 */
	protected $_enableColumns = array();
	
	/**
	 * @var bool enable cheks (tranalateable fields, field available in type) while setting value
	 */
	protected $_enableSetChecks = true;

	/**
	 * @var tx_tcaobjects_objectCollection	object versions are stored here if versioning is enabled for this table
	 */
	protected $versions;
	
	/**
	 * @var tx_tcaobjects_objectCollection	translations  are stored here if translation is enabled for this table
	 */
	protected $translations;

	/**
	 * @var bool	if true, there are changes that aren't stored to database yet
	 */
	protected $notStoredChanges = false;
	
	/**
	 * @var array	properties that where changed since last save
	 */
	protected $dirtyFields = array();
	
	/**
	 * @var array array of tx_tcaobjects_object 
	 */
	protected $languageObjects = array();
	
	/**
	 * @var array methods of this object that should be asked for resolving a __call request
	 */
	protected $callMethods = array('call_get_', 'call_set_', 'call_get', 'call_set', 'call_find_by_');
	
	/**
	 * @var bool validate values while setting
	 */
	protected $validateWhiteSetting = true;
	
	/**
	 * @var tx_tcaobject_object translation parent
	 */
	protected $translationParent;
	
	/**
	 * @var array cache storing the fields available in a type
	 */
	protected $fieldsForTypeValueCache = array();
	
	/**
	 * @var array storage for validation errors
	 */
	protected $validationErrors = array();
	
	/**
	 * @var array array of field names that should not be considered in set checks
	 */
	protected $ignoreSetCheckFields = array();

	/**
	 * @var	string comma separated list of standard field names
	 */
	const standardFields = 'uid,pid';

    /**
     * @var string comma separated list of potential special fields
     */
    const potentialSpecialFields = 'tstamp,crdate,cruser_id,delete,sortby,origUid,transOrigPointerField,transOrigDiffSourceField';
    
    /**
     * @var string comma separated list of potential enable columns
     */
    const potentialEnableColumns = 'disabled,starttime,endtime,fe_group';

    /**
     * @var string comma separated list of field names used for versioning
     */
    const versioningFields = 't3ver_oid,t3ver_id,t3ver_wsid,t3ver_label,t3ver_state,t3ver_stage,t3ver_count,t3ver_tstamp,t3ver_move_id,t3ver_swapmode';


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
    public function __construct($uid='', array $dataArr=array(), $ignoreEnableFields=false) {

        // write to ts log
        if ($GLOBALS['TT'] instanceof t3lib_timeTrack) {
            $GLOBALS['TT']->setTSlogMessage('Creating new "'.$this->getClassName().'" object.', 0);
        }

        // Load backend class "language" to process language labels
        tx_tcaobjects_div::loadLang();

        tx_tcaobjects_div::includeTCA();
        
        // Set table
        if (empty($this->_table)) {
            $this->_table = $this->getClassName();
        }

        t3lib_div::loadTCA($this->_table);

        // Override and extend TCA settings with local settings from (inheriting) class
        tx_pttools_assert::isArray($GLOBALS['TCA'][$this->_table]['columns'], array('message' => 'No columns found in TCA for class: "'.$this->getClassName().'" / table: "'.$this->_table.'"'));
        $this->_properties = t3lib_div::array_merge_recursive_overrule($GLOBALS['TCA'][$this->_table]['columns'], $this->_properties);

        // Ignored fields
        foreach ($this->_ignoredFields as $field) {
        	if (!isset($this->_properties[$field])) { // do not overwrite existing local configuration
            	$this->_properties[$field] = true;
        	}
        }

        // Standard fields
        foreach (t3lib_div::trimExplode(',', self::standardFields) as $field) {
        	if (!isset($this->_properties[$field])) {
            	$this->_properties[$field] = true;
        	}
        }

        // Required fields for versioning
        if ($this->isVersionable()) {
            foreach (t3lib_div::trimExplode(',', self::versioningFields) as $field) {
            	if (!isset($this->_properties[$field])) {
                	$this->_properties[$field] = true;
            	}
            }
        }

        // Special fields (tstamp, crdate,...)
        foreach (t3lib_div::trimExplode(',', self::potentialSpecialFields) as $specialField) {
            if (($fieldName = $this->getSpecialField($specialField)) !== false) {
            	// track these fields in an array
            	$this->_specialFields[$specialField] = $fieldName;
                if (!is_array($this->_properties[$fieldName])) {
                    $this->_properties[$fieldName] = true;
                }
            }
        }

        // Enable fields (tstamp, crdate,...)
        foreach (t3lib_div::trimExplode(',', self::potentialEnableColumns) as $enableField) {
            if (($fieldName = $this->getEnableColumn($enableField)) !== false) {
            	// track these fields in an array
            	$this->_enableColumns[$enableField] = $fieldName;
                if (!is_array($this->_properties[$fieldName])) {
                    $this->_properties[$fieldName] = true;
                }
            }
        }
        
        // Default language uid
        $this->_properties['dluid'] = true;

        // New fields from the alias map
        foreach ($this->_aliasMap as $field => $target) {
            $this->_properties[$field] &= $this->_properties[$target];
        }

        // set class configuration array if found in registry
        $classConfLabel = $this->getClassName() . '_conf';
        if (tx_pttools_registry::getInstance()->has($classConfLabel)) {
            $this->_classConf = &tx_pttools_registry::getInstance()->get($classConfLabel);
        }

        // set extension configuration array if found in registry
        $extConfLabel = tx_tcaobjects_div::getCondensedExtKeyFromClassName($this->getClassName()) . '_conf';
        if (tx_pttools_registry::getInstance()->has($extConfLabel)) {
            $this->_extConf = &tx_pttools_registry::getInstance()->get($extConfLabel);
        }

        // Populate with data
        if (!empty($uid)) {
        	// load from uid
        	$this->_enableSetChecks = false;
            $this->loadSelf($uid, $ignoreEnableFields);
            $this->_enableSetChecks = true;
        } elseif (!empty($dataArr)) {
        	// set given data
        	$this->_enableSetChecks = false;
            $this->setDataArray($dataArr);
            $this->_enableSetChecks = true;
        } else {
        	// new object
        	$this->setDefaultValues();
        }
        
        $this->resetDirtyFields();

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
    	
    	if ($this->notStoredChanges) {
    		throw new tx_pttools_exception('There are unstored changed in this object. You cannot reload this object! Those changes would get lost.');
    	}

		tx_pttools_assert::isValidUid($uid, false, array('message' => sprintf('"%s" is not a valid uid!', $uid)));
        $dataArr = tx_tcaobjects_objectAccessor::selectByUid($uid, $this->_table, $ignoreEnableFields);
        if (is_array($dataArr)) {
            $this->setDataArray($dataArr);
        } else {
            throw new tx_pttools_exception(sprintf('Record "%s:%s" could not be loaded (ignoring enable fields: "%s")', $this->_table, $uid, $ignoreEnableFields ? 'true' : 'false'), 0);
        }
        
        tx_pttools_assert::isTrue($this->checkReadAccess(), array('message' => sprintf('No read access on "%s"', $this->getIdentifier())));
    }
    
    
    
    /**
     * Set default values
     * 
     * @return void
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-04-01
     */
    protected function setDefaultValues() {
    	foreach ($this->getProperties() as $property) {
    		if (($defaultValue = $this->getConfig($property, 'default')) !== false) {
    			if (!isset($this->_values[$property])) {
    				$this->__set($property, $defaultValue);
    			}
    		}
    	}
    }

    /**
     * Set flag "validate while setting"
     * If unset the values will not be validated while setting them
     * 
     * @param bool
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-04-14
     */
    public function setValidateWhileSetting($validateWhileSetting) {
    	$this->validateWhiteSetting = $validateWhileSetting;
    }

    /**
     * Get flag "validate while setting"
     * If unset the values will not be validated while setting them
     * 
     * @return bool
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-04-14
     */
	public function getValidateWhileSetting() {
    	return $this->validateWhiteSetting;
    }
    
    /**
     * Stores itself into the database (update or insert depending on if the uid is set)
     *
     * @param 	bool	(optional) update subitems, default is false
     * @param	int		(optional) pid where to store possibly needed mm records
     * @return	void
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public function storeSelf($updateSubitems = false, $pidForMmRecords = 0) {
    	
    	tx_pttools_assert::isTrue($this->checkWriteAccess(), array('message' => sprintf('No write access on "%s"', $this->getIdentifier())));
    	
    	// special translation overlay actions
    	if ($this->isTranslationOverlay()) {
    		// get the pid from the temporary translation parent
    		if (!$this->__get('pid') && !is_null($this->translationParent)) {
    			$this->__set('pid', $this->translationParent->__get('pid'));
    		}
    		if (!$this->getDefaultLanguageUid() && !is_null($this->translationParent)) {
    			$this->setDefaultLanguageUid($this->translationParent->__get('uid'));
    		}
    		tx_pttools_assert::isValidUid($this->getDefaultLanguageUid(), false, array('message' => 'No default language uid found'));
    	}
    	
        // TODO: save aggregated objects and dependencies

        $dataArray = $this->getDataArray();
        
        // check if all properties are valid
        $validationErrors = $this->validate(); 
        if (count($validationErrors) > 0) {
        	$tmp = '';
        	foreach ($validationErrors as $fied => $errors) {
        		$tmp .= $fied . ': ' . implode(', ', $errors) . '; ';
        	}
        	throw new tx_pttools_exception(sprintf('Validation errors "%s" while saving "%s"', $tmp, $this->getIdentifier()));
        }

        if (($fieldName = $this->getSpecialField('tstamp')) !== false) {
            $dataArray[$fieldName] = $GLOBALS['EXEC_TIME'];
        }

        // fill cruser_id and crdate if this is a new record
        if (empty($dataArray['uid'])) {
            if (($fieldName = $this->getSpecialField('cruser_id')) !== false) {
                // TODO: what if not in frontend context?
                $dataArray[$fieldName] = $GLOBALS['TSFE']->fe_user->user['uid'];
            }
            if (($fieldName = $this->getSpecialField('crdate')) !== false) {
                $dataArray[$fieldName] = $GLOBALS['EXEC_TIME'];
            }
        } else {
        	// not possible to change these values when updating (reason: policy, not technical!)
            unset($dataArray['crdate']);
            unset($dataArray['cruser_id']);
            // unset($dataArray['pid']); uncommenting allows moving record by changing the pid and storing the record
        }

        // if "pid" if empty try to get storagePid from classConf or extConf (out of the registry)
        if (empty($dataArray['pid'])) {
            if (!empty($this->_classConf['storagePid'])) {
                $dataArray['pid'] = intval($this->_classConf['storagePid']);
            } elseif (!empty($this->_extConf['storagePid'])) {
                $dataArray['pid'] = intval($this->_extConf['storagePid']);
            } else {
                throw new tx_pttools_exception('No "pid" defined!');
            }
        }

        $uid = tx_tcaobjects_objectAccessor::store($this->_table, $dataArray);
        
        // write new uid back to property
        $this->__set('uid', $uid);
        
        // reset dirtyFields
        $this->resetDirtyFields();
        
        // process inline relations
        // TODO: only process them if objects were load (do NOT load them here if they were not loaded before!)
        foreach (array_keys($this->_properties) as $property) {
            if ($this->getConfig($property, 'type') == 'inline' && $this->getConfig($property, 'foreign_field')) {

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

                foreach ($this[$property . '_objColl'] as $item) { /* @var $item tx_tcaobjects_object */
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

        $this->notStoredChanges = false;

    } // end method
    
    
    
    /**
     * Overwrite this method in your inheriting class to control access on write operations (insert, update, delete)
     * 
     * @return bool
     */
    public function checkWriteAccess() {
    	return true;
    }
    
    
    
    /**
     * Overwrite this method in your inheriting class to control access on read operations (select)
     * 
     * @return bool
     */
    public function checkReadAccess() {
    	return true;
    }



    /**
     * Checks if this object is marked as deleted in the database
     *
     * @return 	bool
     * @throws	tx_pttools_exception 	if there is not "delete" field in TCA
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-03-22
     */
    public function isDeleted() {
        $deletedField = $GLOBALS['TCA'][$this->_table]['ctrl']['delete'];
        tx_pttools_assert::isNotEmpty($deletedField, array('message' => 'No "delete" field set in TCA!'));
        return $this->__get($deletedField) ? true : false;
    }



    /**
     * Checks if this object is marked as disabled (hidden) in the database
     *
     * @return 	bool
     * @throws	tx_pttools_exception 	if there is not "disabled" field in TCA
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-03-22
     */
    public function isDisabled() {
        $disabledField = $GLOBALS['TCA'][$this->_table]['ctrl']['enablecolumns']['disabled'];
        tx_pttools_assert::isNotEmpty($disabledField, array('message' => 'No "disabled" field set in TCA!'));
        return $this->__get($disabledField) ? true : false;
    }



    /**
     * Sets the deleted flag
     *
     * @param	bool (optional)
     * @return 	void
     * @throws	tx_pttools_exception 	if there is not "delete" field in TCA
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-03-22
     */
    public function setDeleted($deleted = true) {
        $deletedField = $GLOBALS['TCA'][$this->_table]['ctrl']['delete'];
        tx_pttools_assert::isNotEmpty($deletedField, array('message' => 'No "delete" field set in TCA!'));
        $this->__set($deletedField, $deleted);
    }



    /**
     * Sets the disabled falg
     *
     * @param 	bool (optional)
     * @return 	void
     * @throws	tx_pttools_exception 	if there is not "disabled" field in TCA
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-03-22
     */
    public function setDisabled($disabled = true) {
        $disabledField = $GLOBALS['TCA'][$this->_table]['ctrl']['enablecolumns']['disabled'];
        tx_pttools_assert::isNotEmpty($disabledField, array('message' => 'No "disabled" field set in TCA!'));
        $this->__set($disabledField, $disabled);
    }
    
    
    
    /**
     * Check if this record is active (regarding starttime/endtime)
     * 
     * @return bool
     */
    public function isActive() {
    	$startTimeField = $GLOBALS['TCA'][$this->_table]['ctrl']['enablecolumns']['starttime'];
    	if ($startTimeField) {
    		$startTime = $this->__get($startTimeField);
    		if ($startTime && $startTime > $GLOBALS['EXEC_TIME']) {
    			return false;
    		}
    	}
    	$endTimeField = $GLOBALS['TCA'][$this->_table]['ctrl']['enablecolumns']['endtime'];
        if ($endTimeField) {
    		$endTime = $this->__get($endTimeField);
    		if ($endTime && $endTime < $GLOBALS['EXEC_TIME']) {
    			return false;
    		}
    	}
    	return true;
    }
    
    
    
    /**
     * Get type field
     * 
     * @return string
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-05-05
     */
    public function getTypeField() {
    	return tx_tcaobjects_div::getCtrl($this->_table, 'type');
    }
    
    
    
    /**
     * Get type value
     * 
     * @param bool (optional) throw exception
     * @return mixed type field value
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-05-05
     */
    public function getTypeValue($throwExceptionIfNoTypeField=true) {
    	$typeField = $this->getTypeField();
    	if ($throwExceptionIfNoTypeField) {
    		tx_pttools_assert::isNotEmptyString($typeField, array('message' => 'No type field defined in TCA'));
    	}
    	return empty($typeField) ? '' : $this->__get($typeField);	
    }
    
    
    
    /**
     * Check if the given property is available in the current type
     * 
     * @param string $property
     * @return bool true if this property is available
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-05-05
     */
    public function isAvailableInCurrentType($property) {
    	
    	$typeValue = $this->getTypeValue(false);
    	if (empty($typeValue)) { // no types are used, field is always available
    		return true;
    	}
    	
    	// following fields are available in all types (even if not configured excplicitely)
    	if (t3lib_div::inList(self::versioningFields . ',' . self::standardFields, $property)) {
 			return true;   		
    	}
    	if (in_array($property, $this->_specialFields) || in_array($property, $this->_enableColumns)) {
    		return true;
    	}
    	
    	$fields = tx_tcaobjects_div::getFieldsForTypeValue($typeValue, $this->_table);
    	
    	return in_array($property, $fields);
    }
    
    /***************************************************************************
     * Language methods (only if translations are for this class)
     *
     * Hint: Access the versioning fields 't3ver_oid', 't3ver_id', 't3ver_wsid',...
     * like normal properties (e.g. $this['t3ver_label'] = 'new label';)
     **************************************************************************/
    
    /**
     * Check if this record supports translations
     * 
     * @return bool
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-03-15
     */
    public function supportsTranslations() {
    	return tx_tcaobjects_div::supportsTranslations($this->_table); 
    }
    
    /**
     * Check if this is a translation overlay
     * 
     * @return bool
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-03-16
     */
    public function isTranslationOverlay() {
    	return $this->supportsTranslations() && ($this->getLanguageUid() != 0);
    }
    
    /**
     * Check if this article is in default language
     * 
     * @return bool
     */
    public function isDefaultLanguage() {
    	return !$this->isTranslationOverlay(); 
    }
    
    /**
     * Returns a collection of all translations
     * 
     * @param bool ignore enable fields
     * @return tx_tcaobjects_objectCollection
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-03-26
     */
    public function getTranslations($ignoreEnableFields=false) {
    	if (empty($this->translations)) {
	    	tx_pttools_assert::isTrue($this->supportsTranslations(), array('message' => sprintf('Translation is not supported for this table "%s"', $this->_table)));
    		$defaultLanguageUid = $this->getDefaultLanguageUid();
    		tx_pttools_assert::isValidUid($defaultLanguageUid, false, array('message' => 'This object was not saved before'));

	        $collectionClassname = $this->getCollectionClassName();
	        
	        $this->translations = new $collectionClassname('translations', array(
	        	'uid' => $defaultLanguageUid,
	        	'ignoreEnableFields' => $ignoreEnableFields,
	        ));
	        $this->translations->writeLanguageUidsToKeys();
	        
	        // find current object and select it
	        foreach ($this->translations as $key => $translation) { /* @var $translation tx_tcaobject_object */
	        	if ($translation->get_uid() == $this->get_uid()) {
	        		$this->translations->set_selectedId($key);
	        	}
	        }
    	}
        return $this->translations;
    }

	/**
	 * Creates a new translation of this object
	 *
	 * @param 	int	languageUid
	 * @return 	tx_tcaobjects_object	instance of the same class
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2010-04-15
	 */
    public function createNewTranslation($languageUid) {
        tx_pttools_assert::isTrue($this->supportsTranslations(), array('message' => sprintf('Translation is not supported for this table "%s"', $this->_table)));
        tx_pttools_assert::isValidUid($languageUid, false, array('message' => 'No valid language uid given'));
        
        // check if a translation already exists for the given uid
        if ($this->getLanguageVersion($languageUid, true) !== false) {
        	throw new tx_pttools_exception('A translation already exists');
        }
        
        $defaultTranslation = $this->getDefaultLanguageObject();
        tx_pttools_assert::isValidUid($defaultTranslation['uid'], false, array('message' => 'Default language object has no uid'));
        tx_pttools_assert::isValidUid($defaultTranslation['pid'], false, array('message' => 'Default language object has no pid'));

		$className = $this->getClassName();
		$translation = new $className(); /* @var $translation tx_tcaobjects_object */
		
		$translation['pid'] = $defaultTranslation['pid']; // store in the same sysfolder
		$translation->setLanguageUid($languageUid);
		$translation->setDefaultLanguageUid($defaultTranslation['uid']);
		
		return $translation;
    }
    
    /**
     * Set temporary translation parent. While store data will be read from this object 
     * to retrieve defaultLanguageUid and pid. This data may not be placed at this time
     * 
     * @param tx_tcaobjects_object $translationParent
     * @return void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2010-04-16
     */
    public function setTemporaryTranslationParent(tx_tcaobjects_object $translationParent) {
    	tx_pttools_assert::isTrue($this->supportsTranslations(), array('message' => sprintf('Translation is not supported for this table "%s"', $this->_table)));
    	tx_pttools_assert::isInstanceOf($translationParent, $this->getClassName());
    	$this->translationParent = $translationParent;
    }
    
    /**
     * Get language field
     * 
     * @return string language field name
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-03-15
     */
    public function getLanguageField() {
    	tx_pttools_assert::isTrue($this->supportsTranslations(), array('message' => sprintf('Translation is not supported for this table "%s"', $this->_table)));
    	return tx_tcaobjects_div::getLanguageField($this->_table);
    }
    
    /**
     * Get language uid
     * 
     * @return int
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-03-15
     */
    public function getLanguageUid() {
    	tx_pttools_assert::isTrue($this->supportsTranslations(), array('message' => sprintf('Translation is not supported for this table "%s"', $this->_table)));
    	return intval($this[$this->getLanguageField()]);
    }
    
    /**
     * Set language uid
     * 
     * @param $languageUid
     * @return void
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-04-13
     */
    public function setLanguageUid($languageUid) {
    	tx_pttools_assert::isTrue($this->supportsTranslations(), array('message' => sprintf('Translation is not supported for this table "%s"', $this->_table)));
    	tx_pttools_assert::isValidUid($languageUid, true, array('message' => 'Invalid language uid'));
    	$this[$this->getLanguageField()] = $languageUid;
    }
        
    /**
     * Get transOrigPointer field (this is the field that contains to uid of the default language record)
     * 
     * @return string field name
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-03-15
     */
    public function getTransOrigPointerField() {
    	tx_pttools_assert::isTrue($this->supportsTranslations(), array('message' => sprintf('Translation is not supported for this table "%s"', $this->_table)));
    	return tx_tcaobjects_div::getTransOrigPointerField($this->_table);
    }
    
    
    
    /**
     * Get default language object
     * 
     * @return tx_tcaobjects_object
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-03-15
     */
    public function getDefaultLanguageObject() {
    	return $this->getLanguageVersion(0);
    }
    
    
    
    /**
     * Get the uid of the default language object
     * 
     * @return int uid
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-03-16
     */
    public function getDefaultLanguageUid() {
    	if (!$this->isTranslationOverlay()) {
    		$defaultLanguageUid = $this->__get('uid');
    	} else {
    		// we're in a translation record
    		$defaultLanguageUid = $this->__get($this->getTransOrigPointerField());
    	}
    	return $defaultLanguageUid;
    }
    
    
    
    /**
     * Set default language uid
     * 
     * @param $defaultLanguageUid
     * @return void
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-04-13
     */
    public function setDefaultLanguageUid($defaultLanguageUid) {
    	tx_pttools_assert::isValidUid($defaultLanguageUid);
    	$this->__set($this->getTransOrigPointerField(), $defaultLanguageUid);
    }
    
    
    
    /**
     * Get language version
     * If no translation is found for the given uid the method returns $this unless the second parameter is set to true
     * (then false will be returnes)
     * 
     * @param int $sysLanguageUid
     * @param bool (optional) $returnFalseIfNoTranslationFound
     * @return tx_tcaobjects_object|false
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2009-12-14
     */
    public function getLanguageVersion($sysLanguageUid=NULL, $returnFalseIfNoTranslationFound=false) {
    	tx_pttools_assert::isTrue($this->supportsTranslations(), array('message' => 'Translation is not supported for this table'));

    	// if no sysLanguageUid is given the current frontend's language uid will be used
    	if (is_null($sysLanguageUid)) {
    		tx_pttools_assert::isInstanceOf($GLOBALS['TSFE'], 'tslib_fe', array('message' => 'No TSFE found!'));
			$sysLanguageUid = $GLOBALS['TSFE']->sys_language_content;
    	}
    	
    	tx_pttools_assert::isValidUid($sysLanguageUid, true, array('message' => 'Invalid sysLanguageUid'));
    	
    	$sysLanguageUid = intval($sysLanguageUid);
    	$className = $this->getClassName(); 
    	
    	// retrieve language object if it wasn't retrieved before
    	if (is_null($this->languageObjects[$sysLanguageUid])) {
    		
	    	if ($sysLanguageUid == $this->getLanguageUid()) { // this is already the requested translation
	    		
	    		$this->languageObjects[$sysLanguageUid] = $this;
	    		
	    	} elseif ($sysLanguageUid == 0) { // default language was requested
	    		
	    		$dlUid = $this->getDefaultLanguageUid();
	    		tx_pttools_assert::isValidUid($dlUid, false, array('message' => 'No default language uid found'));
	    		$this->languageObjects[$sysLanguageUid] = tx_tcaobjects_genericRepository::getObjectByUid($className, $dlUid);
	    		
	    		// set reference to current object for performance reasons
	    		$this->languageObjects[$sysLanguageUid]->setLanguageVersion($this->getLanguageUid(), $this);
	    		
	    	} else { // translation other than default was requested
		    	
		    	// load data
		    	$dataArr = tx_tcaobjects_objectAccessor::selectTranslation(
		    		$this->_table, 
		    		$this->getTransOrigPointerField(), 
		    		$this->getDefaultLanguageUid(),
		    		$this->getLanguageField(), 
		    		$sysLanguageUid
		    	);
		    	
		    	if ($dataArr === false) {
		    		// no translation record found
		    		$this->languageObjects[$sysLanguageUid] = false;
		    	} else {
			    	// merge data
			    	$defaultLanguageObject = $this->getDefaultLanguageObject();
			    	$origDataArr = $defaultLanguageObject->getDataArray();
			    	// var_dump($origDataArr);
			    	$translatedData = array();
			    	foreach ($origDataArr as $field => $value) {
			    		if ($this->excludedFromTranslation($field) /* || in_array($field, array('uid')) */ ) {
			    			// echo $field . ': '. $value . '<br />';
			    			// Original field value
			    			$translatedData[$field] = $value;
			    		} else {
			    			// translated field value
			    			$translatedData[$field] = $dataArr[$field];
			    		}
			    	}	
			    	$this->languageObjects[$sysLanguageUid] = new $className('', $translatedData);
			    	
			    	// set reference to current object for performance reasons
	    			$this->languageObjects[$sysLanguageUid]->setLanguageVersion($this->getLanguageUid(), $this);
		    	}
	    	}
    	}
    	
    	if ($this->languageObjects[$sysLanguageUid] == false && $returnFalseIfNoTranslationFound == false) {
    		return $this;
    	} else {
    		return $this->languageObjects[$sysLanguageUid];
    	}
    }
    
    
    /**
     * Set language version directly (this is used so that a back reference can be set)
     * 
     * @param int $sysLanguageUid
     * @param tx_tcaobjects_object 
     */
    protected function setLanguageVersion($sysLanguageUid, tx_tcaobjects_object $languageVersion) {
    	tx_pttools_assert::isValidUid($sysLanguageUid, true, array('message' => 'Invalid sysLanguageUid'));
    	$sysLanguageUid = intval($sysLanguageUid);
    	$this->languageObjects[$sysLanguageUid] = $languageVersion;
    }


    /***************************************************************************
     * Versioning methods (only if versioning is available for this class)
     *
     * Hint: Access the versioning fields 't3ver_oid', 't3ver_id', 't3ver_wsid',...
     * like normal properties (e.g. $this['t3ver_label'] = 'new label';)
     **************************************************************************/


    /**
     * Check if versioning is enabled for this class
     *
     * @return 	bool
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-06-01
     */
    public function isVersionable() {
        $versionable = $GLOBALS['TCA'][$this->_table]['ctrl']['versioningWS'];
        return ($versionable == true);
    }



    /**
     * Check if this record if the online version
     *
     * @return 	bool
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-06-01
     */
    public function isOnlineVersion() {
        tx_pttools_assert::isTrue($this->isVersionable(), array('message' => 'Versioning is not enabled for this class'));
        return ($this['pid'] != -1);
    }



    /**
     * Returns the uid of the online version ("oid")
     *
     * @param 	void
     * @return 	int		uid of the online version
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-10-27
     */
    public function getOid() {
    	$oid = $this->getOnlineVersion()->__get('uid'); // this is the same as $this if $this is already the online version
    	tx_pttools_assert::isValidUid($oid, false, array('message' => 'No valid "oid" found!'));
    	return $oid;
    }



    /**
     * Returns a collection of all versions (including the current one)
     *
     * @param	void
     * @return 	tx_tcaobjects_objectCollection
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-10-27
     */
    public function getVersions() {
    	if (empty($this->versions)) {
	        tx_pttools_assert::isTrue($this->isVersionable(), array('message' => 'Versioning is not enabled for this class'));

	        $collectionClassname = $this->getCollectionClassName();
	        $this->versions = new $collectionClassname('versions', array('uid' => $this->getOid()));
	        // find current object and select it
	        foreach ($this->versions as $key => $version) { /* @var $translation tx_tcaobject_object */
	        	if ($version->get_uid() == $this->get_uid()) {
	        		$this->versions->set_selectedId($key);
	        	}
	        }
	        if ($this->versions->get_selectedId() == 0) {
	        	throw new tx_pttools_exception('No selected id found. (There should be one!)');
	        }
    	}
        return $this->versions;
    }
    
    
    
    /**
     * Get a list of fields that have changed since the last save
     *  
     * @param void
     * @return array
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2009-10-18
     */
    public function getDirtyFields() {
    	return $this->dirtyFields;
    }
    
    
    
    /**
     * Reset dirty fields
     *  
     * @param void
     * @return void
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2009-10-18
     */
    public function resetDirtyFields() {
    	$this->dirtyFields = array();
    }



    /**
     * Make this version the online version.
     * Current version and online version will be swapped.
     * The object is reloaded afterwards to hold the new data
     *
     * @param 	void
     * @return 	void
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-10-27
     */
    public function makeThisTheOnlineVersion() {
        tx_pttools_assert::isTrue($this->isVersionable(), array('message' => 'Versioning is not enabled for this class'));
        tx_pttools_assert::isFalse($this->isOnlineVersion(), array('message' => 'This is already the online version'));

        if ($this->notStoredChanges) {
        	throw new tx_pttools_exception('There are unstored changed in this object. You cannot reload this object! Those changes would get lost.');
        }

        $tce = t3lib_div::makeInstance('t3lib_TCEmain'); /* @var $tce t3lib_TCEmain */
		$tce->stripslashes_values = 0;
		$tce->start(array(), array(), tx_tcaobjects_div::createFakeBeUser());
        $tce->bypassAccessCheckForRecords = 1;

		$oid = $this->getOid();
		$currentUid = $this->__get('uid');
		
		$tce->version_swap($this->getTable(), $oid, $currentUid);

		// reset versions and values
		$this->versions = NULL;
		$this->_values = array();
		
		// reload self
		$this->loadSelf($oid);
    }



    /**
     * Returns an object with the online version of this record
     *
     * @param 	bool					(optional) throws an exception if this object is already the online version
     * @return 	tx_tcaobjects_object	online version of this record
     * @throws	tx_pttools_exception	if versioning is not enabled for this record
     * @throws	tx_pttools_exception	if this is already the online version and first parameter is true
     * @throws	tx_pttools_exception	if t3ver_oid is not set
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-06-01
     */
    public function getOnlineVersion($throwExceptionIfAlreadyOnline = false) {
        tx_pttools_assert::isTrue($this->isVersionable(), array('message' => 'Versioning is not enabled for this class'));

        if (!$this->isOnlineVersion()) {
        	tx_pttools_assert::isNotEmpty($this->__get('t3ver_oid'), array('message' => 'No t3ver_oid defined'));
			$onlineVersion = tx_tcaobjects_genericRepository::getObjectByUid($this->getClassName(), $this->__get('t3ver_oid'));
        } else {
        	if ($throwExceptionIfAlreadyOnline) {
        		throw new tx_pttools_exception('This is already the online version');
        	}
        	$onlineVersion = $this;
        }
		return $onlineVersion;
    }


	/**
	 * Creates a new version of this object
	 *
	 * @param 	string	(optional) label, will be autogenerated if empty
	 * @return 	tx_tcaobjects_object	instance of the same class
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-27
	 */
    public function createNewVersion($label = '') {
        tx_pttools_assert::isTrue($this->isVersionable(), array('message' => 'Versioning is not enabled for this class'));

		$tce = t3lib_div::makeInstance('t3lib_TCEmain'); /* @var $tce t3lib_TCEmain */
		$tce->stripslashes_values = 0;
		$tce->start(Array(),Array(), tx_tcaobjects_div::createFakeBeUser());

		$newUid = $tce->versionizeRecord($this->getTable(), $this->getOid(), $label);
		
		tx_pttools_assert::isValidUid($newUid, false, array('message' => 'No valid new uid!'));
		tx_pttools_assert::isNotEqual($newUid, $this->get_uid(), array('message' => 'Old and new uid are the same!'));

		$object = tx_tcaobjects_genericRepository::getObjectByUid($this->getClassName(), $newUid);
		
		tx_pttools_assert::isEqual($newUid, $object->get_uid(), array('message' => 'Object does not have the correct uid'));
		return $object;
    }



    /**
     * Saves itself as a new version
     *
     * @param 	string	(optional) label, will be autogenerated if empty
     * @param 	bool	(optional) if set the new created version will be the online version
	 * @return 	tx_tcaobjects_object	instance of the same class
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-10-29
     */
    public function saveAsNewVersion($label = '', $makeNewVersionTheOnlineVersion = false) {
    	tx_pttools_assert::isTrue($this->isVersionable(), array('message' => 'Versioning is not enabled for this class'));

    	$newVersion = $this->createNewVersion($label);

    	// copy content to the new object
    	$dataArray = $this->getDataArray();

    	$dontCopyFields = t3lib_div::trimExplode(',', self::versioningFields . ',' . self::standardFields . ',' . self::potentialSpecialFields);

    	$dataArray = array_diff_key($dataArray, array_flip($dontCopyFields));
    	
    	// set remaining fields (the fields that actually contain the domain data) to the new object
    	$newVersion->setDataArray($dataArray);
    	
    	$newVersion->storeSelf();
    	
    	if ($makeNewVersionTheOnlineVersion == true) {
    		$newVersion->makeThisTheOnlineVersion();
    	}
    	
    	return $newVersion;
    }



    /**
     * Get collection classname
     * 
     * @return string collection classname
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-03-26
     */
	public function getCollectionClassName() {
		return tx_tcaobjects_div::getCollectionClassName($this->getClassName());
	}

	
	
    /**
     * Deletes itself from the database
     *
     * @param 	bool	(optional) mark only as deleted instead of really deleting the record, default is true
     * @return 	void
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-10-27
     */
    public function deleteSelf($markOnlyAsDeleted = true) {
    	
    	tx_pttools_assert::isTrue($this->checkWriteAccess(), array('message' => sprintf('No write access on "%s"', $this->getIdentifier())));

    	tx_pttools_assert::isValidUid($this->__get('uid'), false, array('message' => 'This record cannot be deleted as it does not have a valid uid!'));

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
                tx_tcaobjects_objectAccessor::updateExistingRecord($this->_table, array($deletedFieldName => 1, 'uid' => $this->uid));

		        // delete versions, too
		        if ($this->isVersionable()) {
		        	$where = 't3ver_oid = ' . $this->__get('uid');
		        	tx_tcaobjects_objectAccessor::updateTable($this->_table, $where, array($deletedFieldName => 1));
		        }
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
    	
    	// if there's a type field set this first
    	$typeField = $this->getTypeField();
    	if (!empty($typeField)) {
    		if (isset($data[$typeField])) {
    			$value = $data[$typeField];
    			unset($data[$typeField]);
    			$data = array_merge(array($typeField => $value), $data);
    		}
    	}
    	
    	// set all values
        foreach ($data as $calledProperty => $value) {
            if (substr($calledProperty, 0, 4) != 'zzz_') { // ignore old fields
                $property = $this->resolveAlias($calledProperty);
                if (!$this->offsetExists($property)) {
                    throw new tx_pttools_exception('Property "' . $property . '" (called property was: "' . $calledProperty . '") not valid');
                } else {
                    $this->__set($property, $value);
                }
            }
        }
    }
    

	
    /**
     * Set properties from xml object
     * 
     * @param SimpleXMLElement $xml
     * @param bool $lowerCaseProperties if true all attributes names will be converted to lowercase
     * @return void
     */
	public function setFromXml(SimpleXMLElement $xml, $lowerCaseProperties=false) {
		foreach ($xml->attributes() as $name => $value) {
			$this[$lowerCaseProperties ? strtolower($name) : $name] = (string) $value;
		}
	}



    /**
     * Get the object's properties and values in an array
     *
     * @param 	void
     * @return 	array	dataArray
     * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public function getDataArray() {
        $data = array();
        foreach ($this->getProperties() as $property) {
			$data[$property] = $this->__get($property);
        }
        return $data;
    }
    
    

    /**
     * Get the object's properties and values in an array
     *
     * @return 	array array of properties
     * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-04-01
     */
    public function getProperties() {
    	$properties = array();
		foreach (array_keys($this->_properties) as $property) {
            if (is_array($this->_properties[$property]) 
            	// && $this->getConfig($property, 'type') != 'inline' 
            	&& !in_array($property, $this->_ignoredFields) || in_array($property, array('uid', 'pid', 'sorting'))) {
                if (!$this->resolveAlias($property, true)) {
                	$properties[] = $property;
                }
            }
        }
    	return $properties;
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



    /**
     * Returns the field name of a "special field"
     *
     * @param 	string			special field
     * @return 	string|bool		name of the special field in this table or false if this special field doesn't exist for this table
     * @throws	tx_pttools_exception if specialField is no valid special field (see self::potentialSpecialFields)
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public function getSpecialField($specialField) {
        tx_pttools_assert::isInList(
            $specialField,
            self::potentialSpecialFields,
            array('message' => '"'.$specialField.'" is an invalid field name')
        );
        $fieldName = $GLOBALS['TCA'][$this->_table]['ctrl'][$specialField];
        return (!empty($fieldName) ? $fieldName : false );
    }



    /**
     * Returns the field name of an "enable column"
     *
     * @param 	string			special field
     * @return 	string|bool		name of the special field in this table or false if this special field doesn't exist for this table
     * @throws	tx_pttools_exception if specialField is no valid special field (see self::potentialSpecialFields)
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public function getEnableColumn($enableColumn) {
        tx_pttools_assert::isInList(
            $enableColumn,
            self::potentialEnableColumns,
            array('message' => '"'.$enableColumn.'" is an invalid field name')
        );
        $fieldName = $GLOBALS['TCA'][$this->_table]['ctrl']['enablecolumns'][$enableColumn];
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
     * @return 	false|string false if value was not set, otherwise the value
     * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public function getConfig($property, $config) {

        tx_pttools_assert::isNotEmpty($property, array('message' => 'Parameter "property" empty!'));
        tx_pttools_assert::isNotEmpty($config, array('message' => 'Parameter "config" empty!'));

        return isset($this->_properties[$property]['config'][$config]) ? $this->_properties[$property]['config'][$config] : false;
    }
    
    
    
    /**
     * Check if this property is excluded from translation
     * 
     * @param string $property
     * @return bool
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public function excludedFromTranslation($property) {
    	tx_pttools_assert::isTrue($this->supportsTranslations(), array('message' => 'Translation is not supported for this table'));
    	return ($this->_properties[$property]['l10n_mode'] == 'exclude');
    }



    /**
     * Returns the class name
     *
     * @param 	void
     * @return 	string	class name
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public function getClassName() {
    	if (is_null($this->_className)) {
    		$this->_className = get_class($this);
    	}
        return $this->_className;
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
    	$uid = $this->_values['uid'];
    	// TODO: store this temporary identifier
        return $this->getClassName() . ':' . (!empty($uid) ? $uid : 'NEW'. $GLOBALS['EXEC_TIME']);
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
        tx_pttools_assert::isNotEmpty($property, array('message' => 'Parameter "property" empty!'));
        return t3lib_div::trimExplode(',', $this->getConfig($property, 'eval'), true);
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
     * Process file upload
     * 
     * @param string $property
     * @param string $tmpName
     * @param string $name
     * @return void
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-03-31
     */
	public function processFileUpload($property, $tmpName, $name) {
		$type = $this->getConfig($property, 'type');
		tx_pttools_assert::isEqual($type, 'group', array('message' => sprintf('Type "%s" (property "%s") is not supported. (only "group")', $type, $property)));
        $internal_type = $this->getConfig($property, 'internal_type');
        tx_pttools_assert::isEqual($internal_type, 'file');
        $maxitems = $this->getConfig($property, 'maxitems');
        tx_pttools_assert::isEqual($maxitems, '1');
        
        
		$uploadFolder = $this->getConfig($property, 'uploadfolder');
		tx_pttools_assert::isNotEmptyString($uploadFolder);		
		
		// check for collisions with existing files (and append counting postfix in this case)
		// There is room for optimazation: On file name collision compare both files (md5-wise). 
		// On the other hand: Reusing files could cause some more errors and would not be consistent to
		// how TYPO3 handles files (when not using dam)
		$counter = 0;
		do {
			$postFix = ($counter==0) ? '' : '_' . $counter;
			$targetFileName = tx_tcaobjects_div::createSaveFileName($name, $postFix);
			$targetFilePath = $uploadFolder . DIRECTORY_SEPARATOR . $targetFileName;
			$counter++;
		} while (is_file($targetFilePath));
		
        // only white lists are supported! "disallowed" will not be read
        $allowedFileTypes = $this->getConfig($property, 'allowed');
		$pathInfo = pathinfo($targetFileName);
		$extension = strtolower($pathInfo['extension']);
		
		if (t3lib_div::inList($allowedFileTypes, $extension)) {
			t3lib_div::upload_copy_move($tmpName, $targetFilePath);
			$this->__set($property, $targetFileName);
			
			tx_pttools_assert::isNotEmptyString($this->__get($property));
			tx_pttools_assert::isFilePath($this[$property.'_path']);
		} else {
			$this->setValidationError($property, array('unallowedFileExtension' => 'unallowedFileExtension'));
		}
	}
    
    
    
    /**
	 * Returns the classname of the object to construct in the "_obj" modifier
	 * By default this is configured in TCA in "foreign_tcaobject_class"
	 * Overrride this method if you want a different behaviour
	 * 
     * @param 	string		property name
     * @param 	string		property name that was originally called
     * @return	string		classname
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    protected function getClassNameForObjModifier($property, $calledProperty) {
    	return $this->getConfig($property, 'foreign_tcaobject_class');
    }



    /**
     * Processes modifier "objColl". (Resolves table relations and returns an object
     * collection with the loaded children)
     *
     * Valid for TCA types
     * - "inline"
     * - "group" with internal_type "db"
     * - "select" with foreign_table
     *
     * @param 	string		property name
     * @param 	string		property name that was originally called
     * @return 	tx_tcaobjects_objectCollection 	object collection
     * @throws	tx_pttools_exception
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-04-27
     */
    protected function processModifier_objColl($property, $calledProperty) {

        $type 			= $this->getConfig($property, 'type');
        $internal_type 	= $this->getConfig($property, 'internal_type');
        $foreign_table	= $this->getConfig($property, 'foreign_table');

        tx_pttools_assert::isTrue(
            ($type == 'inline') || (($type == 'group') && ($internal_type == 'db') || (($type == 'select') && ($foreign_table))),
            array(
                'message' 			=> 'Invalid modifier "objColl" for called property! ' . $property,
                'modifier'			=> 'objColl',
                'type' 				=> $type,
                'internal_type' 	=> $internal_type,
                'property'			=> $property,
                'calledProperty' 	=> $calledProperty
            )
        );
        
        $classname = tx_tcaobjects_div::getForeignClassName($this->_table, $property);

        $propertyCollectionName = tx_tcaobjects_div::getCollectionClassName($classname);

        // create object collection
        $value = new $propertyCollectionName(); /* @var $value tx_tcaobjects_objectCollection */
        
        
        
        
        if ($type == 'inline') {
        	
            /*******************************************************************
             * Case 1: TCA type: "inline"
             ******************************************************************/
        	
            tx_pttools_assert::isNotEmpty($foreign_table, array('message' => 'No "foreign_table" defined for property "'.$property.'" in table "'.$this->_table.'"!'));

            $foreign_field = $this->getConfig($property, 'foreign_field');
            
            if (!empty($foreign_field)) {
            	
	            // array of records of the foreign table (mm records with uids or final data)
	            $dataArr = tx_tcaobjects_objectAccessor::selectByParentUid(
	                $this->__get('dluid'), // default language uid
	                $foreign_table,
	                $foreign_field,
	                $this->getConfig($property, 'foreign_sortby')
	                // TODO: consider foreign_table_field if available!
	            );
	            
	            if (!empty($dataArr)) {
	
	                $isMM = tx_tcaobjects_div::ForeignTableIsMmTable($this->_table, $property);
	
	                // TODO: Document how and when to use the "foreign_mm_field"!
	                if ($isMM == true) {
	                    $foreign_mm_field = $this->getConfig($property, 'foreign_mm_field');
	                }
	
	                // add items to the collection
	                foreach ($dataArr as $data) {
	                    try {
	                    	
	                        // create object directly or by its uid (in case of an mm table)
	                    	if ($isMM) {
	                    		$tmpObj = tx_tcaobjects_genericRepository::getObjectByUid($classname, $data[$foreign_mm_field]); 
	                    	} else {
	                    		$tmpObj = new $classname('', $data);
	                    	}
	                        
	                    } catch (tx_pttools_exception $exceptionObj) {
	                        $exceptionObj->handleException();
	                        $uid = $isMM ? $data[$foreign_mm_field] : $data['uid'];
	                        throw new tx_pttools_exception('Was not able to construct object "'.$classname.':'.$uid.'" (over mm-table: '. ($isMM ? 'yes' : 'no').') and add it to the collection! Original exception message: '.$exceptionObj->getMessage());
	                    }
	                    
	                	if ($tmpObj->isDeleted() == false) {
							$value->addItem($tmpObj);
						}
	                }
	            }

            } else {
            	// If no foreign_field is defined, the field in the original table contains a comma separated list of uids in the foreign_table. 
            	// See TYPO3 Core Apis. This may be implemented here...

            	if (($tmpPropertyValue = $this->__get($property)) == true) {
	            	$uids = t3lib_div::trimExplode(',', $tmpPropertyValue);
	            	tx_pttools_assert::isValidUidArray($uids);
	            	foreach ($uids as $uid) {
	            		try {
	                        // create object
	                        $tmpObj = tx_tcaobjects_genericRepository::getObjectByUid($classname, $uid, true); // ignoring enable fields
	
	                        if ($tmpObj->isDeleted() == false) {
	                            $value->addItem($tmpObj);
	                        }
	                    } catch (tx_pttools_exception $exceptionObj) {
	                        $exceptionObj->handleException();
	                        throw new tx_pttools_exception('Was not able to construct object "'.$classname.':'.$uid.'" and add it to the collection!');
	                    }
	            	}
            	}
            	
            }

        } elseif (($type == 'group') && ($internal_type == 'db')) {
            /*******************************************************************
             * Case 2: TCA type "group"
             ******************************************************************/
            throw new tx_pttools_exception('Not implemented yet :)');
        } elseif (($type == 'select') && ($foreign_table)) {
        	/*******************************************************************
             * Case 3: TCA type "select"
             ******************************************************************/
        	
        	$tmpPropertyValue = $this->__get($property);
        	if (!empty($tmpPropertyValue)) {
	        	$uids = t3lib_div::trimExplode(',', $tmpPropertyValue);
	        	tx_pttools_assert::isValidUidArray($uids, false, array('message' => sprintf('Value was "%s"', $tmpPropertyValue)));
	            foreach ($uids as $uid) {
	            	try {
	                    // create object
	                    $tmpObj = tx_tcaobjects_genericRepository::getObjectByUid($classname, $uid, true); // ignoring enable fields
	
	                    if ($tmpObj->isDeleted() == false) {
	                        $value->addItem($tmpObj);
	                    }
	                } catch (tx_pttools_exception $exceptionObj) {
	                    $exceptionObj->handleException();
	                    throw new tx_pttools_exception('Was not able to construct object "'.$classname.':'.$uid.'" and add it to the collection!');
	                }
	            }
        	}
        } else {
        	throw new tx_pttools_exception('Not supported!');
        }

        tx_pttools_assert::isInstanceOf($value, 'tx_tcaobjects_objectCollection');
        // replace collection by translated collection if translation is supported
        if ($value->supportsTranslation() && $this->supportsTranslations()) {
        	$value = $value->translate(true, $this->getLanguageUid());
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
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-04-27
     */
    protected function processModifier_obj($property, $calledProperty) {

        $type 			= $this->getConfig($property, 'type');
        $internal_type 	= $this->getConfig($property, 'internal_type');
        $maxitems 		= $this->getConfig($property, 'maxitems');

        tx_pttools_assert::isTrue(
            (($type == 'group') && ($internal_type == 'db') && ($maxitems == 1))
            || (($type == 'select') && ($maxitems == 1))
            || (($type == 'inline') && ($maxitems == 1)),
            array(
                'message' 			=> 'Invalid modifier "obj" for called property!',
                'modifier'			=> 'obj',
                'type' 				=> $type,
                'internal_type' 	=> $internal_type,
                'maxitems' 			=> $maxitems,
                'property'			=> $property,
                'calledProperty' 	=> $calledProperty
            )
        );
        
        $uid = $this->__get($property);
        
        tx_pttools_assert::isValidUid($uid, false, array('message' => 'No valid uid found for _obj modifier (Current object: '.$this->getIdentifier().', Property '.$property.')'));

        // TODO: is this step needed (as it will be retrivied in tx_tcaobjects_div::getClassname aswell
        $classname = $this->getClassNameForObjModifier($property, $calledProperty);

        if (empty($classname)) {
        	$classname = tx_tcaobjects_div::getClassname($this->_table, $property);
        }

        // TODO: can this be something like "tt_content_18"?
        $object = tx_tcaobjects_genericRepository::getObjectByUid($classname, $uid);
        tx_pttools_assert::isInstanceOf($object, 'tx_tcaobjects_object');
        return $object;
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
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-04-27
     */
    protected function processModifier_path($property, $calledProperty) {

        $type 			= $this->getConfig($property, 'type');
        $internal_type 	= $this->getConfig($property, 'internal_type');

        tx_pttools_assert::isTrue(
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

            tx_pttools_assert::isNotEmpty($uploadFolder, array('message' => 'Configuration "uploadfolder" was not defined for property "'.$property.'" in table "'.$this->_table.'"!'));

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
     * - "select_checkboxes"
     *
     * @param 	string		property name
     * @param 	string		property name that was originally called
     * @return 	array		value => label
     * @throws	tx_pttools_exception
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-04-27
     */
	protected function processModifier_explode($property, $calledProperty) {

        $type = $this->getConfig($property, 'type');

        tx_pttools_assert::isTrue(
            ($type == 'select') || ($type == 'select_checkboxes'),
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
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-04-27
     */
    protected function processModifier_sL($property, $calledProperty) {

        $type 		= $this->getConfig($property, 'type');
        $maxitems 	= $this->getConfig($property, 'maxitems');

        tx_pttools_assert::isTrue(
            ($type == 'select') && ($maxitems == 1),
            array(
                'message' 			=> 'Invalid modifier "sL" for called property!',
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
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-04-27
     */
    protected function processModifier_rte($property, $calledProperty) {
    	// class and field specific rte configuration
    	$parseFunc = tx_pttools_div::getTS(sprintf('config.tx_tcaobjects.%s.%s.parseFunc_RTE.', $this->getClassName(), $property));
    	if (empty($parseFunc)) {
    		// class specific rte configuration
    		$parseFunc = tx_pttools_div::getTS(sprintf('config.tx_tcaobjects.%s.parseFunc_RTE.', $this->getClassName()));
    	}
    	if (empty($parseFunc)) {
    		// generic rte configuration
	        $parseFunc = tx_pttools_div::getTS('config.tx_tcaobjects.parseFunc_RTE.');
    	}

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
    
    
    
    /**
     * Processes modifier "label".
     * 
     * @param 	string		property name
     * @param 	string		property name that was originally called
     * @return 	string		field label
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    protected function processModifier_label($property, $calledProperty) {
    	return $this->getCaption($property);
    }
    
    
    
    /**
     * Processes modifier "label".
     * 
     * @param 	string		property name
     * @param 	string		property name that was originally called
     * @return 	array		tx_tcaobjects_objectCollection
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    protected function processModifier_values($property, $calledProperty) {
    	
        $type = $this->getConfig($property, 'type');
        $internal_type = $this->getConfig($property, 'internal_type');
        $foreign_table = $this->getConfig($property, 'foreign_table');
        $foreign_table_where = $this->getConfig($property, 'foreign_table_where'); 

        tx_pttools_assert::isTrue(
            /* ($type == 'inline') || (($type == 'group') && ($internal_type == 'db') || */ (($type == 'select') && ($foreign_table)) /* ) */,
            array(
                'message' 			=> 'Invalid modifier "objColl" for called property!',
                'modifier'			=> 'objColl',
                'type' 				=> $type,
                'internal_type' 	=> $internal_type,
                'property'			=> $property,
                'calledProperty' 	=> $calledProperty
            )
        );
        
		$classname = tx_tcaobjects_div::getForeignClassName($this->_table, $property);
        $propertyCollectionName = tx_tcaobjects_div::getCollectionClassName($classname);
 
        // replace markers
        $replaceArray = array();
        foreach ($this->getDataArray() as $key => $value) {
        	$replaceArray['###REC_FIELD_'.$key.'###'] = $value;
        }
        $foreign_table_where = str_replace(array_keys($replaceArray), array_values($replaceArray), $foreign_table_where);
        $foreign_table_where = trim($foreign_table_where);
        if (t3lib_div::isFirstPartOfStr(strtolower($foreign_table_where), 'and')) {
        	$foreign_table_where = trim(substr($foreign_table_where, 3));
        }
        
        list($where, $order) = preg_split('/order.+by/i', $foreign_table_where);
        
        // create object collection
        $values = new $propertyCollectionName('items', array(
        	'where' => $where,
        	'order' => $order
        )); /* @var $values tx_tcaobjects_objectCollection */
        
    	return $values;
    }
    
    
    
    /**
     * Process modifier "multilang".
     * This modifier returns the content of the field in all available languages 
     *
     * @param $property
     * @param $calledProperty
     * @return string
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-05-05
     */
    protected function processModifier_multilang($property, $calledProperty) {
    	tx_pttools_assert::isFalse($this->excludedFromTranslation($property), array('message' => 'This property is excluded from translation'));
    	$translations = $this->getTranslations();
    	
    	$values = $translations->extractProperty($property);
    	$output = '';
    	
    	// TODO: what about language uid "-1" for "all"?
    	foreach ($values as $uid => $value) {
    		if (!empty($value)) {
	    		// TODO: this could be made configurable by a class property and setter/getter 
	    		$output .= sprintf('<span class="lang-%s">%s</span>', htmlspecialchars($uid), $value);
    		}
    	}
    	return $output;
    }
    
    
    
    /**
     * Process modifier "multilang".
     * This modifier returns the content of the field in all available languages 
     *
     * @param $property
     * @param $calledProperty
     * @return string
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-05-05
     */
    protected function processModifier_multilanghsc($property, $calledProperty) {
    	tx_pttools_assert::isFalse($this->excludedFromTranslation($property), array('message' => 'This property is excluded from translation'));
    	$translations = $this->getTranslations();
    	
    	$values = $translations->extractProperty($property);
    	$output = '';
    	
    	// TODO: what about language uid "-1" for "all"?
    	foreach ($values as $uid => $value) {
    		if (!empty($value)) {
	    		// TODO: this could be made configurable by a class property and setter/getter 
	    		$output .= sprintf('<span class="lang-%s">%s</span>', htmlspecialchars($uid), htmlspecialchars($value));
    		}
    	}
    	return $output;
    }
    







    // Magic Methods: __get(), __set(), __call()
    // =========================================================================


    /**
     * Getting raw or modified (with special modifiers) property values.
     * "Lazy loading" for modifier values
     *
     * @param 	string		property name
     * @return 	mixed		property value
     * @throws	tx_pttools_exception 	if trying to call a invalid property
     * @throws	tx_pttools_exception 	if trying to invoke a invalid modifier to a property
     * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public function __get($calledProperty) {
    	
    	// dynamic getter get_<calledProperty>
    	if (in_array($calledProperty, $this->_dynamicProperties)) {
    		$methodName = 'get_'.$calledProperty;
    		if (method_exists($this, $methodName)) {
    			return $this->$methodName();
    		} /* else {
    			throw new tx_pttools_exception(sprintf('Method "%s" for dynamic property "%s" not found!', $methodName, $calledProperty));
    		} */
    	}
    	
    	// get default language uid
    	if ($calledProperty == 'dluid') {
    		return $this->getDefaultLanguageUid();
    	}
    	
    	// get default language obj
    	if ($calledProperty == 'dlobj') {
    		return $this->getDefaultLanguageObject();
    	}
    	
    	// get translation
    	if ($calledProperty == '_translate') {
    		return $this->getLanguageVersion();
    	}

        list($orig_property, $modifier) = $this->getSpecial($calledProperty);

        $property = $this->resolveAlias($orig_property);

        if (!$this->offsetExists($calledProperty)) {
            throw new tx_pttools_exception(sprintf('Property "%s" (called property was: "%s") not valid!', $property, $calledProperty));
        }

        $calledProperty = $property.((!empty($modifier)) ? '_'.$modifier : '');

        // return default language object's value if this field is excluded from translation
        if ($this->supportsTranslations() && ($property != $this->getLanguageField()) && ($property != $this->getTypeField())) {
        	
        	// Hint: typeField is excluded from this check because it will be queries everytime a value is set to retrieve if
        	// this value can be set in this type. 
        	
	        // check if this property is excluded from translation
	        if ($this->isTranslationOverlay() && $this->excludedFromTranslation($property)) {
	        	return $this->getDefaultLanguageObject()->__get($calledProperty);
	        	// TODO: or should we return the default language value here?
	        	// How to distinguish then where the value comes from?
	        	// throw new tx_pttools_exception(sprintf('Property "%s" is excluded from translation and cannot be read', $property));
	        }
        }
        
        // process modifier if any
        if (!empty($modifier) && empty($this->_values[$calledProperty])) {

        	// check if modifier is allowed (i.e. is value in $this->_modifierList)
        	tx_pttools_assert::isInArray($modifier, $this->_modifierList, array('message' => sprintf('Modifier "%s" is not allowed in this object!', $modifier)));

            $modifierMethodName = 'processModifier_'.$modifier;
            if (method_exists($this, $modifierMethodName)) {
                $this->_values[$calledProperty] = $this->$modifierMethodName($property, $calledProperty);
            } else {
                throw new tx_pttools_exception(sprintf('No handler method found for modifier "%s"!', $modifier));
            }
        }
               
        /*
        // check if this property is available in the current type
        if ($property != $this->getTypeField() && !$this->isAvailableInCurrentType($property)) {
        	throw new tx_pttools_exception(sprintf('Property "%s" is not available in type "%s"', $property, $this->getTypeValue(false)));
        }
        */

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
    public function __set($calledProperty, $value) {
        
    	// dynamic setter set_<calledProperty>
    	if (in_array($calledProperty, $this->_dynamicProperties)) {
    		$methodName = 'set_'.$calledProperty;
    		if (method_exists($this, $methodName)) {
    			return $this->$methodName($value);
    		} /* else {
    			throw new tx_pttools_exception(sprintf('Method "%s" for dynamic property "%s" not found!', $methodName, $calledProperty));
    		} */
    	}
    	

    	$this->notStoredChanges = false;

        $property = $this->resolveAlias($calledProperty);
        
        if (!$this->offsetExists($property)) {
            throw new tx_pttools_exception('Property "' . $property . '" (called property was: "' . $calledProperty . '") not valid and cannot be set!'.' ['.__CLASS__."::".__FUNCTION__.'(...)]');
        }

        // check if value is different from current value
        if ($value !== $this->_values[$property]) {
        	
        	
        	if ($this->_enableSetChecks && !in_array($property, $this->ignoreSetCheckFields)) {
        	
	        	// check if this property is excluded from translation
	            if ($this->isTranslationOverlay() && $this->excludedFromTranslation($property)) {
	            	if ($value) { // only if value is a "real" values. No "0" or "null"
						throw new tx_pttools_exception(sprintf('Property "%s" cannot be written in a translation overlay. Object: "%s"', $property, $this->getIdentifier()));
	            	}	            	
	    		}       
	
	    		// check if this property is available in the current type
		        if (!$this->isAvailableInCurrentType($property)) {
		        	if ($value) { 
			        	$typeValue = $this->getTypeValue(false);
			        	$availableFields = implode(', ', tx_tcaobjects_div::getFieldsForTypeValue($typeValue, $this->_table));
			        	$objId = $this->getIdentifier();
			        	throw new tx_pttools_exception(sprintf('Property "%s" cannot be written in type "%s" (object: "%s", available fields: "%s")', $property, $typeValue, $objId, $availableFields));
		        	}
		        }
	        	
	        	// validate value
	        	if ($this->getValidateWhileSetting()) {
		        	$validationErrors = $this->getValidationErrorsForProperty($property, $value); 
		        	if (count($validationErrors) > 0) {
		        		throw new tx_pttools_exception(sprintf('"%s" is not a valid value for property "%s" (Errors: %s) Object: %s', $value, $property, implode(', ', $validationErrors), $this->getIdentifier()));
		        	}
	        	}
        	
        	}
        	
        	// save original value to dirtyFields
			if (!array_key_exists($property, $this->dirtyFields)) {
        	 	$this->dirtyFields[$property] = $this->_values[$property];     		
         	}
        	
        	$this->_values[$property] = $value;
        }

        // TODO: type checking with information from tca
        // TODO: special "objColl", "obj",... ?

    }


    /**
     * get_* / set_* / find_by_* for properties
     *
     * @param 	string	method name
     * @param 	array	array of paramaters, use index "0" for setting values!
     * @return 	mixed/void	in case of getter the value of the property will be returned
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     */
    public function __call($methodName, $parameters) {
    	$value = null;
    	foreach ($this->callMethods as $callMethod) {
			if ($this->$callMethod($methodName, $parameters, $value)) {
				return $value;    		
			}
    	}
    	return $value;
    }
    
    
    
    protected function call_get_($methodName, $parameters, &$value) {
    	if (t3lib_div::isFirstPartOfStr($methodName, 'get_')) {
    		$property = substr($methodName, 4);
    		$property = $this->resolveAlias($property);
    		$value = $this->__get($property); 
    		return true;
    	}
    	return false;
    }
    
    protected function call_set_($methodName, $parameters, &$value) {
    	if (t3lib_div::isFirstPartOfStr($methodName, 'set_')) {
    		$property = substr($methodName, 4);
    		$property = $this->resolveAlias($property);
    		$value = $this->__set($property, $parameters[0]); 
    		return true;
    	}
    	return false;
    }
    
    protected function call_get($methodName, $parameters, &$value) {
    	if (t3lib_div::isFirstPartOfStr($methodName, 'get') && (substr($methodName, 3, 1) != '_')) {
    		$property = substr($methodName, 3);
    		$property[0] = strtolower($property[0]); // $property = lcfirst($property); in PHP 5.3
        	$property = $this->resolveAlias($property);
            $value = $this->__get($property);
            return true;
    	}
    	return false;
    }
    
    protected function call_set($methodName, $parameters, &$value) {
    	if (t3lib_div::isFirstPartOfStr($methodName, 'set') && (substr($methodName, 3, 1) != '_')) {
    		$property = substr($methodName, 3);
    		$property[0] = strtolower($property[0]); // $property = lcfirst($property); in PHP 5.3
    		$property = $this->resolveAlias($property);
    		$value = $this->__set($property, $parameters[0]); 
    		return true;
    	}
    	return false;
    }
    
    /**
     * Find an object by a property value and set this objects data to the found values
     * TODO: This should go into a repository object!
     * 
     * @param string $methodName
     * @param array $parameters
     * @param int $value (reference)
     * @return bool true if an object was found
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     */
    protected function call_find_by_($methodName, array $parameters, &$value) {
    	if (t3lib_div::isFirstPartOfStr($methodName, 'find_by_')) {
    		$fieldname = substr($methodName, 8);
            if (!$this->offsetExists($fieldname)) {
                throw new tx_pttools_exception('Field "'.$fieldname.'" (called method was: "'.$methodName.'") does not exist!');
            }
            $where = $fieldname.'='.$GLOBALS['TYPO3_DB']->fullQuoteStr($parameters[0], $this->_table);
            $dataArr = tx_tcaobjects_objectAccessor::selectCollection($this->_table, $where, 1);
            // TODO: what if more than 1 result found? This should/could be a method for a collection
            if (is_array($dataArr[0])) {
            	$this->_enableSetChecks = false;
                $this->setDataArray($dataArr[0]);
                $this->_enableSetChecks = true;
            }
            $value = count($dataArr); // amount of records found
            return true;
    	}
    	return false;
    }
    
    /**
     * Apply filters on property
     * 
     * @param string $property
     * @param mixed $value
     * @return mixed $value
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-04-01
     */
    protected function applyFiltersOnProperty($property, $value) {
    	$eval = $this->getEval($property);
    	foreach ($eval as $filterString) {
    		list($filter, $param1, $param2) = t3lib_div::trimExplode(':', $filterString);
    		tx_pttools_assert::isAlphaNum($filter);
    		$filterFunction = 'filter_' . $filter;
    		if (method_exists('tx_tcaobjects_divForm', $filterFunction)) {
    			$value = tx_tcaobjects_divForm::$filterFunction($value, $property, $param1, $param2);
    		}
    	}
    	return $value;
    }
    
    
    /**
     * Validate property values
     * 
     * @param string $property
     * @param mixed $value
     * @return array validation errors empty array if everything is ok
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2010-03-29
     */
    protected function getValidationErrorsForProperty($property, $value) {
    	$validationErrors = array();
    	
    	$available = true;
    	
    	if ($this->isTranslationOverlay() && $this->excludedFromTranslation($property)) {
    		$available = false;
    		if (!empty($value)) {
    			$error = true;
    			if (($defaultValue = $this->getConfig($property, 'default')) !== false) {
    				if ($value == $defaultValue) {
    					// everything is fine
    					$error = false;
    				}
    			}
    			if ($error) {
	    			$validationErrors[] = 'notAllowedInTranslation';
    			}
    		}
    	} 
    	
        if (!$this->isAvailableInCurrentType($property)) {
        	$available = false;
        	if (!empty($value)) {
        		$validationErrors[] = 'notAllowedInType_'.$this->getTypeValue();
        	}
        }
    	
        
        if ($available) {
	    	$eval = $this->getEval($property);
	    	foreach ($eval as $ruleString) {
	    		$ruleString = str_replace('<comma>', ',', $ruleString);
	    		$ruleString = str_replace('<colon>', ':', $ruleString);
	    		list($rule, $param1, $param2) = t3lib_div::trimExplode(':', $ruleString);
	    		tx_pttools_assert::isAlphaNum($rule);
	    		$ruleFunction = 'rule_' . $rule;
	    		if (method_exists('tx_tcaobjects_divForm', $ruleFunction)) {
	    			if (!tx_tcaobjects_divForm::$ruleFunction($value, $property, $param1, $param2)) {
	    				$validationErrors[] = $ruleString;
	    			}
	    		}
	    	}
        }
    	
    	return $validationErrors;
    }
    
    public function removeValidationError($property, $key) {
    	unset($this->validationErrors[$property][$key]);
    	if (empty($this->validationErrors[$property])) {
    		unset($this->validationErrors[$property]);
    	}
    }
    
    /**
     * Clean validation errors where possible
     * 
     * @return void
     * @author Fabrizio Branca <mail@fabrizio-banca.de>
     */
    public function clean() {
    	$allErrors = $this->validate();
    	foreach ($allErrors as $field => $errors) {
    		foreach ($errors as $key => $error) {
    			if (t3lib_div::isFirstPartOfStr($error, 'notAllowedInType_')) {
    				$this->__set($field, '');
    				$this->removeValidationError($field, $key);
    			}
    			switch ($error) {
    				case 'notAllowedInTranslation': {
    					$this->__set($field, '');
    					$this->removeValidationError($field, $key);
    				} break;
    			}
    		}
    	}
    }
    
    /**
     * Adds a tag to the pages cache
     * Requires the caching framework to be enabled
     * 
     * @return void
     */
    public function addToPagesCache() {
    	$uid = $this['uid'];
    	tx_pttools_assert::isValidUid($uid, array('message' => 'Adding to cache is only possible for saved objects.'));
    	$cacheIdentifier = $this->getClassName() . '_' . $uid;
    	tx_tcaobjects_cacheControl::addTag($cacheIdentifier);
    	if (($endtimeField = $this->getEnableColumn('endtime')) == true) {
    		$endtime = $this[$endtimeField];
    		if (!empty($endtime)) {
    			tx_tcaobjects_cacheControl::addTimeout($endtime);		
    		}
    	}
    }
    
    /**
     * Deletes a pages from cache with the corresponding tag
     * Requires the caching framework to be enabled
     * 
     * @return void
     */
    public function deletePageCache() {
    	$uid = $this['uid'];
    	tx_pttools_assert::isValidUid($uid, array('message' => 'Adding to cache is only possible for saved objects.'));
    	$cacheIdentifier = $this->getClassName() . '_' . $uid;
    	tx_tcaobjects_cacheControl::flushByTag($cacheIdentifier);
    }
    
    
    
    /**
     * Set validation error
     * 
     * @param string $property
     * @param array $propertyErrors
     * @return void
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-05-31 (<- the day after Lena Meyer-Landrut won the ESC 2010)
     */
    public function setValidationError($property, array $propertyErrors) {
    	if (!empty($propertyErrors)) {
        	if (is_array($this->validationErrors[$property])) {
        		$this->validationErrors[$property] = array();
        		foreach ($propertyErrors as $error) {
        			if (!in_array($error, $this->validationErrors[$property])) {
        				$this->validationErrors[$property][] = $property;
         			}
        		}
        	}	
        }
    }
    
    
    
    /**
     * Validate all properties
     * 
     * @return array validation errors array('<property>' => array(<rule>, <rule>))
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2010-04-01
     */
    public function validate() {
    	$errors = array();
    	$dataArray = $this->getDataArray();
    	foreach ($dataArray as $property => $value) {
        	$propertyErrors = $this->getValidationErrorsForProperty($property, $value);
        	$this->setValidationError($property, $propertyErrors);
        }
        $this->validationErrors = t3lib_div::array_merge_recursive_overrule($this->validationErrors, $this->objectValidation());
    	return $this->validationErrors;
    }
    
    
    
    /**
     * This methods can be overriden to detect global errors while validating.
     * This should be used for non field specific rules.
     * E.g.: An object is not valid when endtime is smaller than starttime.
     * 
     * @return array errors
     */
    protected function objectValidation() {
    	return array();
    }



    /**
     * Registers itself in the registry and return an identifier
     *
     * @param 	void
     * @return 	string	identifier where to finde this object in the registry
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2009-02-22
     */
    public function __toString() {
    	$registryIdentifier = uniqid('tcaobject_'.$this->getClassName().'_', true);
    	tx_pttools_registry::getInstance()->register($registryIdentifier, $this);
    	return $registryIdentifier;
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

    	$offsetExists = false;

    	if (key_exists($offset, $this->_properties)) {

    		$offsetExists = true;

    	} else {

	        $propertyParts = explode('_', $offset);

	        $special = end($propertyParts);
	        if (in_array($special, $this->_modifierList)) {
	            array_pop($propertyParts);
	        }

	        $property = implode('_', $propertyParts);
	        // TODO: call offsetExists recursively?
	        if (key_exists($property, $this->_properties)) {
	        	$offsetExists = true;
	        }
    	}
        return $offsetExists;
    }



    /**
     * Gets the value of the offset
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
        return new ArrayIterator($this->_values); // works because this class implements ArrayAccess :)
    }

}

/*
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_object.php'])	{
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_object.php']);
}
*/

?>