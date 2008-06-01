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
	 * Load items
	 *
	 * @param 	string	(optional) where
	 * @param 	string	(optional) limit
	 * @param 	string	(optional) order
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-01
	 */
	public function loadItems($where = '', $limit = '', $order = '') {
		$dataArr = tx_tcaobjects_objectAccessor::selectCollection($this->getTable(), $where, $limit, $order);
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
			$tcaObjectName = str_replace('Collection', '', get_class($this)); // assuming that "fooCollection" contains "foo" objects
			$tmp = new $tcaObjectName();
			$this->table = $tmp->getTable();
		}
		return $this->table;
	}

	
	
	/**
	 * Set data array
	 *
	 * @param 	array	data array
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	2008-06-01
	 */
	protected function setDataArray(array $dataArr) {
		$tcaObjectName = str_replace('Collection', '', get_class($this)); // assuming that "fooCollection" contains "foo" objects
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