<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Fabrizio Branca <fabrizio.branca@aoemedia.de>
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
 * Cache control
 *
 * @author	Fabrizio Branca <fabrizio.branca@aoemedia.de>
 * @package	TYPO3
 * @subpackage	tcaobjects
 */
class tx_tcaobjects_cacheControl {
	
	protected static $cache;
	
	/**
	 * Add tag
	 * 
	 * @param string $tag
	 * @return void
	 */
	public static function addTag($tag) {
		
		tx_pttools_assert::isNotEmptyString($tag);
		tx_pttools_assert::isInstanceOf($GLOBALS['TSFE'], 'tslib_fe', array('message' => 'No TSFE found'));
		
		static $addedTags = array();
		if (!in_array($tag, $addedTags)) {
			$addedTags[] = $tag;
			$GLOBALS['TSFE']->addCacheTags(array($tag));
		}

	}
	
	
	
	/**
	 * Set the time when the page cache should expire.
	 * This is needed because there may be records with starttime before the default cache lifetime ends
	 * 
	 * @param integer Timestamp of when the cache should expire.
	 * @return int The cache timeout for the current page.
	 */
	public static function addTimeout($expire) {
		
		tx_pttools_assert::isValidUid($expire, true, array('message' => 'Invalid expire timestamp'));
		tx_pttools_assert::isInstanceOf($GLOBALS['TSFE'], 'tslib_fe', array('message' => 'No TSFE found'));
		
		$defaultTimeout = $GLOBALS['TSFE']->get_cache_timeout();
		if (($GLOBALS['EXEC_TIME'] + $defaultTimeout) > $expire) {
			$GLOBALS['TSFE']->set_cache_timeout_default(intval($expire - $GLOBALS['EXEC_TIME']));
		}
		return $GLOBALS['TSFE']->get_cache_timeout();
	}
	
	
	
	/**
	* Flush page cache by tag.
	* 
	* @param	string		Tag to clear cache by
	*/
	public static function flushByTag($tag) {
		
		tx_pttools_assert::isNotEmptyString($tag);
		tx_pttools_assert::isEqual($GLOBALS['TYPO3_CONF_VARS']['SYS']['useCachingFramework'], true, array('message' => 'Caching framework is not activated'), false);
			
		self::getCache()->flushByTag($tag);
	}
	
	
	
	/**
	 * Initialize Page Cache framework
	 *
	 * @return	void
	 */	
	public static function getCache() {
		if (!isset(self::$cache)) {
			try {
				self::$cache = $GLOBALS['typo3CacheManager']->getCache('cache_pages');
			} catch(t3lib_cache_exception_NoSuchCache $e) {
				t3lib_cache::initPageCache();
				self::$cache = $GLOBALS['typo3CacheManager']->getCache('cache_pages');
			}
		}
		return self::$cache;
	}
	
	
	
	/**
	 * Clear cache cmd
	 * 
	 * @param string $clearCacheCmd
	 * @return void
	 * @since 2010-10-07
	 */
	public static function clearCacheCmd($clearCacheCmd) {
		$tce = t3lib_div::makeInstance('t3lib_tcemain'); /* @var $tce t3lib_TCEmain */
		$tce->clear_cacheCmd($clearCacheCmd);
	}
	
	
	
	/**
	 * Flush cache by pid
	 * 
	 * @param int pid
	 * @return void
	 * @since 2010-10-07
	 */
	public static function flushByPid($pid) {
		tx_pttools_assert::isValidUid($pid, false, array('message' => 'Invalid pid'));
		return self::clearCacheCmd($pid);
	}
	
	
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_cacheControl.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tcaobjects/res/class.tx_tcaobjects_cacheControl.php']);
}

?>