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
 * Inclusion of external resources (pt_tools classes won't be autoloaded...)
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; 
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; 
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_objectCollection.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php';



/**
 * Singleton registry
 * 
 * $Id: $
 * 
 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
 * @since	2008-05-27
 * @see 	http://www.patternsforphp.com/wiki/Registry
 */
final class tx_tcaobjects_registry extends tx_pttools_objectCollection implements tx_pttools_iSingleton, ArrayAccess {
	
	
	/**
	 * @var 	tx_tcaobjects_registry	Singleton unique instance
	 */
	private static $uniqueInstance = NULL;
	
	
	/***************************************************************************
	 * Methods for the "tx_pttools_iSingleton" interface
	 **************************************************************************/
	
    /**
     * Returns a unique instance of the Singleton object. Use this method instead of the private/protected class constructor.
     * 
     * @param   void
     * @return  tx_tcaobjects_registry      unique instance of the Singleton object
     * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since   2008-05-27
     */
    public static function getInstance() {
        
        if (self::$uniqueInstance === NULL) {
            self::$uniqueInstance = new tx_tcaobjects_registry();
        }
        
        return self::$uniqueInstance;
        
    }
    
    
    /**
     * Final method to prevent object cloning (using 'clone'), in order to use only the unique instance of the Singleton object.
     * 
     * @param   void
     * @return  void
     * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since   2008-05-27
     */
    public final function __clone() {
        
        trigger_error('Clone is not allowed for '.get_class($this).' (Singleton)', E_USER_ERROR);
        
    }
    
    
    
    
    /***************************************************************************
     * Methods for the "ArrayAccess" interface
     **************************************************************************/
	
    /**
     * Checks if an offset is in the array
     *
     * @param 	mixed	offset
     * @return 	bool
     */
	public function offsetExists($offset) {
        return $this->hasItem($offset);
    }

    
    
    /**
     * Returns the value for a given offset
     *
     * @param 	mixed	offset
     * @return 	mixed	element of the collection
     */
    public function offsetGet($offset) {
    	return $this->getItemById($offset);
    }

    
    
    /**
     * Adds an element to the collection
     *
     * @param 	mixed	offset
     * @param 	mixed	value
     */
    public function offsetSet($offset, $value) {
    	$this->addItem($value, $offset);
    }

    
    
    /**
     * Deletes an element from the collection
     *
     * @param 	mixed	offset
     */
    public function offsetUnset($offset) {
    	$this->deleteItem($offset);
    }
    
    
    
    /***************************************************************************
	 * Methods for the registry pattern
	 **************************************************************************/
    
    
    /**
     * Registers an object to the registry
     *
     * @param 	mixed	label, use namespaces here to avoid conflicts
     * @param 	mixed 	object
     * @param	bool	(optional) overwrite existing object, default is false
     * @return 	void
     * @throws	tx_pttools_exception	if the given label already exists and overwrite if false
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-05-27
     */
    public function register($label, $object, $overwrite = false) {
    	
        if (!$this->hasItem($label) || $overwrite == true) {
            $this->addItem($object, $label);
        } else {
        	throw new tx_pttools_exception('There is already an element stored with the label "'.$label.'"!');
        }
        
    }
    
    
    
    /**
     * Unregisters a label
     *
     * @param 	mixed 	label
     * @throws	tx_pttools_exception 	if the label does not exists (uncaught exception from "deleteItem")
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-05-27
     */
    public function unregister($label) {
       	$this->deleteItem($label);
    }
 
    
    
    /**
     * Gets the object for a given label
     *
     * @param 	mixed	label
     * @return 	mixed	object
     * @throws	tx_pttools_exception 	if the label does not exists (uncaught exception from "getItemById")
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-05-27
     */
    public function get($label) {
    	return $this->getItemById($label);
    }
 
    
    
    /**
     * Checks if the label exists
     *
     * @param 	mixed	label
     * @return 	bool
     * @author	Fabrizio Branca <mail@fabrizio-branca.de>
     * @since	2008-05-27
     */
    public function has($label) {
        return $this->hasItem($label);
    }
    
    
    /***************************************************************************
	 * Magic methods wrappers for registry pattern methods
	 * 
	 * $reg = tx_tcaobjects_registry::getInstance();
	 * $reg->myObject = new SomeObject();
	 * if (isset($reg->myObject)) {
	 * 		// there is a myObject value
	 * } else {
	 * 		// there is not a myObject value
	 * }
	 * $obj = $reg->myObject;
	 * unset($reg->myObject);
	 **************************************************************************/
    
    /**
     * @see 	tx_tcaobjects_registry::register
     */
    public function __set($label, $object) {
    	$this->register($label, $object);
    }
    
    
    
    /**
     * @see 	tx_tcaobjects_registry::unregister
     */
    public function __unset($label) {
        $this->unregister($label);
    }
    
    
    
    /**
     * @see 	tx_tcaobjects_registry::get
     */
    public function __get($label) {
        return $this->get($label);
    }
    
    
    
    /**
     * @see 	tx_tcaobjects_registry::has
     */
    public function __isset($label) {
    	return $this->has($label);
    }
    
    
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_registry.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_registry.php']);
}

?>