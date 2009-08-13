<?php

// TODO: switch to tx_pttools_iPageable interface?
class tx_tcaobjects_objectCollection extends tx_pttools_objectCollection implements tx_tcaobjects_iPageable {

	/**
	 * @var tx_pttools_objectAccessor
	 * TODO: make Accessors non-static and make them available by this property (including getter -> lazy loading!)
	 */
	protected $accessor;

	protected $tcaObjectName = '';

	protected $table = '';
	
	/**
	 * @var string unique property (will be checked on add)
	 */
	protected $uniqueProperty = null;



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
			// get table name
			$tcaObjectName = $this->getClassName();
			$tmp = new $tcaObjectName();
			$this->table = $tmp->getTable();
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
		return str_replace('Collection', '', get_class($this)); // assuming that "fooCollection" contains "foo" objects
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
			$this->addItem(new $tcaObjectName('', $row));
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
     */
    public function addItem($itemObj, $id=0) {
    	
    	if (!is_null($this->uniqueProperty)) {
    		
    		if (TYPO3_DLOG) t3lib_div::devLog(sprintf('Checking for unique property "%s"', $this->uniqueProperty), 'tcaobjects');
    		
			$property = tx_tcaobjects_div::extractProperty($itemObj, $this->uniqueProperty);
			$existingProperties = $this->extractProperty($this->uniqueProperty);
    		
    		if (TYPO3_DLOG) t3lib_div::devLog('Unique check', 'tcaobjects', 1, array('property' => $property, 'existingProperties' => $existingProperties));
    		
    		if (in_array($property, $existingProperties)) {
    			throw new tx_pttools_exception(sprintf('Property "%s" already in exists on collection!', $this->uniqueProperty));
    		}
    	}
    	
    	return parent::addItem($itemObj, $id);
    }
    
    
    /**
     * Returns the first item in the collection where the given value matches the value of the given property
     * 
     * @param string property name
     * @param string value
     * @return false|tx_tcaobjects_object
     */
    public function getItemByProperty($propertyName, $propertyValue) {
    	/* @var $item tx_tcaobjects_object */
    	foreach ($this as $item) {
    		if ($item[$propertyName] == $propertyValue) {
    			return $item;
    		} 
    	}
    	return false;
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
     * FILTERS
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
     * SORTING
     * *******
     * Sorting works simular as filtering. Simply ask a collection for a "sort_<nameOfSorting>" key
     * via the ArrayAccess Interface
     * e.g. $userCollection['sort_byLastName']
     * This method will check if a callback function called "compare_<nameOfSorting>" exists in the
     * inheriting collection class. This callback (should be a static method) will get two objects
     * as parameters and should decide which one is the greater one. The comparison function must return
     * an integer less than, equal to, or greater than zero if the first argument is considered to be
     * respectively less than, equal to, or greater than the second. (have a look at PHP's usort documentation)
     *
     * @param 	mixed	offset
     * @return 	mixed	element of the collection or a collection
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
				usort($this->itemsArr, array(get_class($this), $methodName));
			} else {
				throw new tx_pttools_exception(sprintf('No method "%s" found (for sort use)', $methodName));
			}
    		return $this;
    	} elseif ($offset == 'count') {
    		return count($this->itemsArr);
    	} else {
	        return $this->getItemById($offset);
    	}
    }


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_objectCollection.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_objectCollection.php']);
}

?>