<?php

// TODO: switch to tx_pttools_iPageable interface?
class tx_tcaobjects_objectCollection extends tx_pttools_objectCollection implements tx_tcaobjects_iPageable {

	/**
	 * @var tx_pttools_objectAccessor
	 * TODO: make Accessors non-static and make them available by this property (including getter -> lazy loading!)
	 */
	protected $accessor;

	/**
	 * @var string class name of the tcaobject
	 */
	protected $tcaObjectName = '';

	/**
	 * @var string table name of the tcaobject
	 */
	protected $table = '';
	
	/**
	 * @var string unique property (will be checked on add)
	 */
	protected $uniqueProperty = null;
	
	/**
	 * @var bool if set the object's uid i will automatically used as the collections id if no other is set
	 */
	protected $useUidAsCollectionId = false;
	
	/**
	 * @var string sorting field used by genericFieldSorter (for internal use only!)
	 */
	protected static $_sortingField;
	
	/**
	 * @var string sorting direction used by genericFieldSorter (for internal use only!)
	 */
	protected static $_sortingDirection;



	/**
	 * Constructor
	 *
	 * @param 	string	(optional) "load_".$load will be called on construct if set
	 * @param 	array 	(optional) parameters for the "load_".$load method
	 * @throws	tx_pttools_exception	if "load_".$load does not exist
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-08
	 */
	public function __construct($load = '', array $params = array()) {

		// set restricted class name
		if (is_null($this->restrictedClassName)) {
			$this->restrictedClassName = $this->getClassName();
		}

		// load items
		if (!empty($load)) {
			tx_pttools_assert::isNotEmptyString($load, array('message' => 'No valid load parameter'));
			$methodName = 'load_'.$load;
			if (method_exists($this, $methodName)) {
				$this->$methodName($params);
			} else {
				throw new tx_pttools_exception('Trying to load "'.$load.'", but method "'.$methodName.'" does not exist!');
			}
		}
	}



	/**
	 * Wrapper for loadItems, that can be called from the constructor
	 *
	 * @param 	array 	params, can contain keys "where", "limit" and "order"
	 * @return 	void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-08
	 */
	public function load_items(array $params) {
		$this->loadItems($params['where'], $params['limit'], $params['order']);
	}



	/**
	 * Load all versions of a record
	 *
	 * @param 	array	params, key "
	 *
	 */
	public function load_versions(array $params) {
		tx_pttools_assert::isValidUid($params['uid'], false, array('message' => 'No valid uid given!'));

		$where = '(t3ver_oid = ' . $params['uid'] . ' OR uid = ' . $params['uid'] . ')';
		$limit = '';
		$order = 't3ver_id DESC';
		$ignoreEnableFields = false;

		$dataArr = tx_tcaobjects_objectAccessor::selectCollection($this->getTable(), $where, $limit, $order, $ignoreEnableFields);
		$this->setDataArray($dataArr);
	}



	/**
	 * Load items
	 *
	 * @param 	string	(optional) where
	 * @param 	string	(optional) limit
	 * @param 	string	(optional) order
	 * @param	bool	(optional) ignore enable fields, default is false
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-01
	 */
	public function loadItems($where = '', $limit = '', $order = '', $ignoreEnableFields = false) {
		$dataArr = tx_tcaobjects_objectAccessor::selectCollection($this->getTable(), $where, $limit, $order, $ignoreEnableFields);
		$this->setDataArray($dataArr);
	}



	/**
	 * Get table name
	 *
	 * @return 	string	table name
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-01
	 */
	protected function getTable() {
		if ($this->table == '') {
			$this->table = tx_tcaobjects_div::getTableNameForClassName($this->getClassName());
		}
		return $this->table;
	}



	/**
	 * Get the class name of the items in this array
	 *
	 * @return 	string	class name
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-05
	 */
	protected function getClassName() {
		if (empty($this->tcaObjectName)) {
			// assuming that "fooCollection" contains "foo" objects
			$this->tcaObjectName = str_replace('Collection', '', get_class($this)); 
		}
		return $this->tcaObjectName;
	}



	/**
	 * Set data array
	 *
	 * @param 	array	data array
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-01
	 */
	protected function setDataArray(array $dataArr) {
		$tcaObjectName = $this->getClassName();
		foreach ($dataArr as $row) {
			$object = new $tcaObjectName('', $row);
			$this->addItem($object);
		}
	}



	/**
	 * Returns an array with the value of the property of all items
	 *
	 * @param 	string	property name, use "|" to access properties of subitems
	 * @return 	array	array with all property values
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-09-24
	 */
	public function extractProperty($propertyName) {

		$propertyParts = t3lib_div::trimExplode('|', $propertyName);

		$returnArray = array();
		foreach ($this->itemsArr as $key => $item) {
			$returnArray[$key] = tx_tcaobjects_div::extractProperty($item, $propertyName);
		}
		return $returnArray;
	}

	
	
	/**
	 * Call "storeSelf" on all items
	 * 
	 * @return void
	 * @return void
	 * @author Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since 2009-08-09
	 */
	public function storeAll() {
		/* @var $element tx_tcaobjects_object */
		foreach ($this 	as $element) {
			$element->storeSelf();
		}
	}
	
	

    /**
     * Registers itself in the registry and return an identifier
     *
     * @param 	void
     * @return 	string	identifier where to finde this object in the registry
     * @author	Fabrizio Branca <fabrizio@scrbl.net>
     * @since	2009-02-22
     */
    public function __toString() {
    	$registryIdentifier = uniqid('tcaobjectCollection_'.get_class($this).'_', true);
    	tx_pttools_registry::getInstance()->register($registryIdentifier, $this);
    	return $registryIdentifier;
    }
    
    
    
    /**
	 * Overwrite addItem method to check for unique fields;
	 * If the property "uniqueProperty" is set this method checks if there are existing items having the same value
	 * in this property
	 * 
     * @param	tx_tcaobjects_object	item to add
     * @param	mixed	(optional) array key
     * @return	void
     */
    public function addItem(tx_tcaobjects_object $itemObj, $id=0) {
    	
    	if (!is_null($this->uniqueProperty)) {
    		
    		if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Checking for unique property "%s"', $this->uniqueProperty), 'tcaobjects');
    		
			$property = tx_tcaobjects_div::extractProperty($itemObj, $this->uniqueProperty);
			$existingProperties = $this->extractProperty($this->uniqueProperty);
    		
    		if (TYPO3_DLOG) t3lib_div::devLog('Unique check', 'tcaobjects', 1, array('property' => $property, 'existingProperties' => $existingProperties));
    		
    		if (in_array($property, $existingProperties)) {
    			throw new tx_pttools_exception(sprintf('Property "%s" already in exists on collection!', $this->uniqueProperty));
    		}
    	}
    	
    	if (($id === 0) && $this->useUidAsCollectionId) {
    		$id = $itemObj->get_uid();
    	}
    	
    	return parent::addItem($itemObj, $id);
    }
    
    
    
    /**
     * Prepend one or more elements to the beginning of the collection
     * Multiple elements (like in array_unshift) are not supported!
     *
     * @param   mixed   element to prepend
     * @param   bool    (optional) if true key won't be modified, else numerical keys will be renumbered, default if false
     * @return  int     Returns the new number of elements in the collection
     * @author  Fabrizio Branca <mail@fabrizio-branca.de>
     * @since   2008-06-07
     */
    public function unshift($element, $doNotModifyKeys = false, $useKey = NULL) {
        if ((is_null($useKey)) && $this->useUidAsCollectionId) {
    		$useKey = $element->get_uid();
    	}
    	return parent::unshift($element, $doNotModifyKeys, $useKey);
    }
    
    
    
    /**
     * Returns the first item in the collection where the given value matches the value of the given property
     * 
     * @param string property name
     * @param string value
     * @return false|tx_tcaobjects_object
     */
    public function getItemByProperty($propertyName, $propertyValue) {
    	if (($key = $this->getItemKeyByProperty($propertyName, $propertyValue)) !== false) {
    		return $this->itemsArr[$key];
    	} else {
    		return false;
    	}
    }
    
    
    
    /**
     * Returns the key of the first item in the collection where the given value matches the value of the given property
     * 
     * @param string property name
     * @param string value
     * @return false|string|int key of the first found item
     */
    public function getItemKeyByProperty($propertyName, $propertyValue) {
    	/* @var $item tx_tcaobjects_object */
    	foreach ($this as $key => $item) {
    		if ($item[$propertyName] == $propertyValue) {
    			return $key;
    		} 
    	}
    	return false;
    }
    
    
    
    /**
     * Returns the index for a given id
     * 
     * @param string id
     * @return int index
     */
    public function getIndexForId($id) {
    	if (!$this->hasItem($id)) {
    		throw new tx_pttools_exception(sprintf('Item with id "%s" does not exist.', $id));
    	}
    	return array_search($id, array_keys($this->itemsArr));
    }


    
    /**
     * Get item id from collection by Index
     *
     * @param   integer     index (position in array) of Collection Item
     * @return  mixed       item that has been requested
     * @remarks index starts with 0 for first element
     * @throws  tx_pttools_exceptionInternal if idx is invalid
     * @author  Fabrizio Branca <mail@fabrizio-branca.de>
     * @since   2009-12-14
     */
    public function getItemIdByIndex($idx) {

        // check parameters
        $idx = intval($idx);
        if (($idx < 0) || ($idx >= $this->count())) {
        	return false;
            // throw new tx_pttools_exceptionInternal('Invalid index');
        }
        $itemArr = array_keys($this->itemsArr);

        return $itemArr[$idx];

    }
    
    
    
    /**
     * Get next item if exists
     * 
     * @param mixed $id
     * @return false|mixed id of the next item
     */
    public function getNextItemId($id) {
    	$currentIndex = $this->getIndexForId($id);
    	return ($currentIndex >= count($this)) ? false :$this->getItemIdByIndex($currentIndex+1);
    }
    
    
    
    /**
     * Get previous item if exists
     * 
     * @param mixed $id
     * @return false|mixed id of the previous item
     */
    public function getPreviousItemId($id) {
    	$currentIndex = $this->getIndexForId($id);
    	return ($currentIndex <= 0) ? false : $this->getItemIdByIndex($currentIndex-1);
    }
    
    
    
    /**
     * Get next item if exists
     * 
     * @param mixed $id
     * @return false|tx_tcaobjects_object
     */
    public function getNextItem($id) {
    	$currentIndex = $this->getIndexForId($id);
    	return ($currentIndex >= count($this)) ? false : $this->getItemByIndex($currentIndex+1);
    }
    
    
    
    /**
     * Get previous item if exists
     * 
     * @param mixed $id
     * @return false|tx_tcaobjects_object
     */
    public function getPreviousItem($id) {
    	$currentIndex = $this->getIndexForId($id);
    	return ($currentIndex <= 0) ? false : $this->getItemByIndex($currentIndex-1);
    }
    
    
    
    /**
     * Filter: Returns only items which's specified property equals a given value
     * 
     * @param string property name
     * @param string value
     * @return tx_tcaobjects_objectCollection
     */
    public function where_propertyEquals($propertyName, $propertyValue) {
    	$collection = new tx_tcaobjects_objectCollection();
    	foreach ($this as $key => $item) {
    		if ($item[$propertyName] == $propertyValue) {
    			$collection->addItem($item, $key);
    		} 
    	}
    	return $collection;
    }



	/***************************************************************************
	 * Methods for the "tx_tcaobjects_iPageable" interface
	 **************************************************************************/
    
    /**
	 * Get total item count
	 * 
	 * @param string where clause
	 * @return int total item count
     */
	public function getTotalItemCount($where = '') {
		return tx_tcaobjects_objectAccessor::selectCollectionCount($this->getTable(), $where);
	}

	

	/**
	 * Get items
	 * 
	 * @param string where clause
	 * @param string limit clause
	 * @param string order by clause
	 * @return tx_tcaobjects_objectCollection self
	 */
	public function getItems($where = '', $limit = '', $order = ''){
		$this->loadItems($where, $limit, $order);
		return $this;
	}



	/***************************************************************************
	 * Overriding methods for the "ArrayAccess" interface
	 **************************************************************************/

    /**
     * Returns the value for a given offset
     * and enables "filters" and "sorting".
     *
     * filter_
     * *******
     * You can ask a collection for a "filter_<nameOfTheFilter>" key via the ArrayAccess Interface
     * e.g. $myCollection['filter_elementsGreaterThanTen']
     * This method will check for a method called "check_<nameOfTheFilter>" and will pass every
     * element to this method. If the method returns true this element will be put into a new
     * constructed collection of the same type and after checking all original colelction's items the
     * new item will be returned.
     * A hint for choosing a name: Describe what items will be left in the newly returned collection
     * and not which items will be filtered out. In our example the new collection will contain only
     * items "greater than ten" (whatever this may mean, this decides the check_elementsGreaterThanTen
     * method) and will not contain only those elements that are not "greater than ten".
     *
     * You may chain those filters:
     * e.g.
     * $myCollection['filter_elementsGreaterThanTen']['filter_elementsAccesibleToCurrentUser']
     *
     * sort_
     * *****
     * Sorting works simular as filtering. Simply ask a collection for a "sort_<nameOfSorting>" key
     * via the ArrayAccess Interface
     * e.g. $userCollection['sort_byLastName']
     * This method will check if a callback function called "compare_<nameOfSorting>" exists in the
     * inheriting collection class. This callback (should be a static method) will get two objects
     * as parameters and should decide which one is the greater one. The comparison function must return
     * an integer less than, equal to, or greater than zero if the first argument is considered to be
     * respectively less than, equal to, or greater than the second. (have a look at PHP's usort documentation)
     * 
     * You can also use the generic field sorter
     * Syntax_
     * sort_field_<fieldName>[_asc|_desc]
     * 
     * count
     * *****
     * Returns the total count of items in this collection
     * 
     * last
     * ****
     * Returns the last element of the collection
     * 
     * #<index>
     * ********
     * Access element by index
     * 
     * <id>
     * ****
     * Access element by id
     * 
     * @param 	mixed	offset
     * @return 	mixed	element of the collection or a collection or a single value (count)
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-05-29
     */
    public function offsetGet($offset) {
    	if (substr($offset, 0, 7) == 'filter_') {
			$methodName = 'check_' .substr($offset, 7);
			if (method_exists($this, $methodName)) {
				$className = get_class($this);
				$newCollection = new $className(); /* @var tx_tcaobjects_objectCollection */
				foreach ($this as $object) { /* @var $object tx_tcaobjects_object */
					if ($this->$methodName($object) === true) {
						$newCollection->addItem($object);
					}
				}
				return $newCollection;
			} else {
				throw new tx_pttools_exception(sprintf('No method "%s" found (for filter use)', $methodName));
			}
    	} elseif (substr($offset, 0, 5) == 'sort_') {
    		$methodName = 'compare_' .substr($offset, 5);
    		if (method_exists($this, $methodName)) {
				uasort($this->itemsArr, array(get_class($this), $methodName));
    		} elseif (substr($offset, 5, 6) == 'field_') {
    			$field = substr($offset, 11);
    			if (substr($field, -4) == '_asc') {
    				$field = substr($field, 0, -4);
    				$direction = 'asc'; 
    			} elseif (substr($field, -5) == '_desc') {
    				$field = substr($field, 0, -5);
    				$direction = 'desc';
    			} else {
    				$direction = 'asc';
    			}
    			// not nice, but the only way to pass parameters to a callback function
    			self::$_sortingField = $field;
    			self::$_sortingDirection = $direction;
    			uasort($this->itemsArr, array(get_class($this), 'genericFieldSorter'));
    		} else {
				throw new tx_pttools_exception(sprintf('No method "%s" found (for sort use)', $methodName));
			}
    		return $this;
    	} elseif ($offset == 'count') {
    		return count($this);
    	} elseif (substr($offset, 0, 4) == 'idx_') {
    		return $this->getItemByIndex(substr($offset, 4));
    	} elseif ($offset == 'first') {
    		return $this->getItemByIndex(0);
    	} elseif ($offset == 'last') {
    		return $this->getItemByIndex(count($this)-1);
    	} else {
	        return $this->getItemById($offset);
    	}
    }
    
    /**
     * Generic field sorter used as callback function for usort in the sort_ "magic method"
     * 
     * @param tx_tcaobjects_object $a
     * @param tx_tcaobjects_object $b
     * @return int -1, 0, 1
     * @author Fabrizio Branca <mail@fabrizio-branca.de>
     * @since 2009-12-23 (<- the day before christmas)
     */
    protected function genericFieldSorter(tx_tcaobjects_object $a, tx_tcaobjects_object $b) {
    	$res = ($a[self::$_sortingField] > $b[self::$_sortingField]) ? +1 : -1;
    	if (self::$_sortingDirection == 'desc') {
    		$res *= -1;
    	}
    	return $res;
    }
    
    /**
     * Add the elements of a collection to this one
     *  
     * @param tx_tcaobjects_objectCollection $collection
     * @param unknown_type $preserveIDs
     */
    public function addCollection(tx_pttools_collection $collection, $preserveIDs=false) {
    	foreach($collection as $key => $item) {
    		$this->addItem($item, $preserveIDs ? $key : 0);
    	}
    }


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_objectCollection.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_objectCollection.php']);
}

?>