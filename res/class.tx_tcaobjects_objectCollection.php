<?php

require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_objectCollection.php';
require_once t3lib_extMgm::extPath('tcaobjects').'res/class.tx_tcaobjects_objectAccessor.php';
require_once t3lib_extMgm::extPath('tcaobjects').'res/class.tx_tcaobjects_iPageable.php';


class tx_tcaobjects_objectCollection extends tx_pttools_objectCollection implements ArrayAccess, tx_tcaobjects_iPageable {

    /**
     * @var tx_pttools_objectAccessor
     * TODO: make Accessors non-static and make them available by this property (including getter -> lazy loading!)
     */
    protected $accessor;

    protected $tcaObjectName = '';

    protected $table = '';


    public function loadItems($where = '', $limit = '', $order = '') {
        $dataArr = tx_tcaobjects_objectAccessor::selectCollection($this->getTable(), $where, $limit, $order);
		$this->setDataArray($dataArr);
    }
    
    protected function getTable() {
    	if ($this->table == '') {
			// get table name
	    	$tcaObjectName = str_replace('Collection', '', get_class($this)); // assuming that "fooCollection" contains "foo" objects
	        $tmp = new $tcaObjectName();
	    	$this->table = $tmp->getTable();
    	}
    	return $this->table;
    }
    
    protected function setDataArray($dataArr) {
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
    
    /***************************************************************************
     * Methods for the "ArrayAccess" interface
     **************************************************************************/
    public function offsetExists($offset) {
        return array_key_exists($this->itemsArr, $offset);
    }

    public function offsetGet($offset) {
        return $this->itemsArr[$offset];
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->itemsArr[] = $value;
        } else {
            $this->itemsArr[$offset] = $value;
        }
    }

    public function offsetUnset($offset) {
        unset($this->itemsArr[$offset]);
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_objectCollection.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_objectCollection.php']);
}

?>