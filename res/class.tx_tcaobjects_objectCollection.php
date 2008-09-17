<?php

class tx_tcaobjects_objectCollection extends tx_pttools_objectCollection implements tx_tcaobjects_iPageable {

	/**
	 * @var tx_pttools_objectAccessor
	 * TODO: make Accessors non-static and make them available by this property (including getter -> lazy loading!)
	 */
	protected $accessor;

	protected $tcaObjectName = '';

	protected $table = '';
	
	
	
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



	/***************************************************************************
	 * Methods for the "tx_tcaobjects_iPageable" interface
	 **************************************************************************/
	public function getTotalItemCount($where = '') {
		return tx_tcaobjects_objectAccessor::selectCollectionCount($this->getTable(), $where);
	}


	public function getItems($where = '', $limit = '', $order = ''){
		$this->loadItems($where, $limit, $order);
		return $this;
	}


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_objectCollection.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_objectCollection.php']);
}

?>