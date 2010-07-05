<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Fabrizio Branca <typo3@fabrizio-branca.de>
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
 * Database status provider
 * 
 * @author Fabrizio Branca <typo3@fabrizio-branca.de>
 * @since 2010-04-21
 */
class tx_tcaobjects_reports_DatabaseStatus implements tx_reports_StatusProvider {

	/**
	 * Returns the status for this report
	 *
	 * @return	array	List of statuses
	 * @see typo3/sysext/reports/interfaces/tx_reports_StatusProvider::getStatus()
	 */
	public function getStatus() {
		
		$databaseCharset = $this->getDatabaseCharset($this->getDatabaseCreateStatement(TYPO3_db));
		
		$tables = $this->getTableData();
		$notUtf8Tables = $this->extractNonUtf8Tables($tables);
		
		$message = '';
		
		if (count($notUtf8Tables) == 0 && $databaseCharset == 'utf8') {
			$severity = tx_reports_reports_status_Status::OK;
			$value = 'Database, all tables and all fields are utf8 encoded';
		} else {
			$severity = tx_reports_reports_status_Status::WARNING;
			$value = 'Database, tables or fields are not utf8 encoded';
				
			$message .= $this->renderMessage($tables, $databaseCharset);
			$message .= $this->renderAlterStatements($notUtf8Tables, $databaseCharset);
		}
		
		$statuses = array(
			'charsets' => t3lib_div::makeInstance('tx_reports_reports_status_Status',
				'Character Sets',
				$value,
				$message,
				$severity
			)
		);

		return $statuses;
	}
	
	/**
	 * Get table data
	 * 
	 * @return array
	 */
	protected function getTableData() {
		$tables = $GLOBALS['TYPO3_DB']->admin_get_tables();
		foreach ($tables as $table => &$data) {
			$createStatement = $this->getTableCreateStatement($table);
			$data['charset'] = $this->getTableCharset($createStatement);
			$data['field_charset'] = $this->getFieldCharsets($createStatement);
		}
		return $tables;
	}
	
	/**
	 * Get database create statement
	 * 
	 * @param string (optional) database name, be default the typo3 db will be used
	 * @return string
	 */
	protected function getDatabaseCreateStatement($database) {
		$query = 'SHOW CREATE DATABASE ' . $database;
		$res = $GLOBALS['TYPO3_DB']->admin_query($query);
		$create = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		return $create[1];
	}
	
	/**
	 * Get table create statement
	 * 
	 * @param string table name
	 * @return string table create statement
	 */
	protected function getTableCreateStatement($tableName) {
		$query = 'SHOW CREATE TABLE ' . $tableName;
		$res = $GLOBALS['TYPO3_DB']->admin_query($query);
		$create = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		return $create[1];
	}
	
	/**
	 * Get database charset
	 * 
	 * @param string database create statement
	 * @return string charset
	 */
	protected function getDatabaseCharset($databaseCreateStatement) {
		$matches = array();
		preg_match('/`(\w+)`.*character set (\w+)/i', $databaseCreateStatement, $matches);
		return $matches[2];
	}
	
	/**
	 * Get table charset
	 * 
	 * @param string table create statement
	 * @return string table charset
	 */
	protected function getTableCharset($tableCreateStatement) {
		$matches = array();
		preg_match('/DEFAULT CHARSET=(\w+)/', $tableCreateStatement, $matches);
		return $matches[1];
	}
	
	/**
	 * Get field charsets
	 * 
	 * @param string table create statement
	 * @return array field charsets
	 */
	protected function getFieldCharsets($tableCreateStatement) {
		$fieldCharSets = array();
		$fieldMatches = array();
		$data['field_charset'] = array();
		preg_match_all('/`(\w+)`.*character set (\w+)/', $tableCreateStatement, $fieldMatches);
		foreach ($fieldMatches[0] as $key => $fieldMatch) {
			$fieldCharSets[$fieldMatches[1][$key]] = $fieldMatches[2][$key]; 
		}
		return $fieldCharSets;
	}
	
	/**
	 * Check if all tables are in utf8
	 * 
	 * @param array $tables
	 * @return array non utf8 tables or tables containing non utf8 fields
	 */
	protected function extractNonUtf8Tables(array $tables) {
		$nonUtf8Tables = array();
		foreach ($tables as $table => $data) {
			if ($data['charset'] != 'utf8') {
				$nonUtf8Tables[] = $table;
			}		
			foreach ($data['field_charset'] as $field => $charset) {
				if ($charset != 'utf8') {
					if (!in_array($table, $nonUtf8Tables)) {
						$nonUtf8Tables[] = $table;
					}
				}	
			}
		}
		return $nonUtf8Tables;
	}
	
	/**
	 * Render message
	 * 
	 * @param array $tables
	 * @return string HTML output
	 */
	protected function renderMessage(array $tables, $databaseCharset) {
		$output = '<table>';
			
			// output database charset
		$output .= '<tr><th>Database</th><th>Charset</th></tr>';
		$output .= sprintf('<tr><td style="border-top: 1px solid black; font-weight: bold;">%s</td><td style="border-top: 1px solid black; background-color: %s; font-weight: bold;">%s</td></tr>', 
			TYPO3_db,
			$databaseCharset == 'utf8' ? '#CDEACA' : '#FBB19B',
			$databaseCharset
		);	
		
			// output table/field charsets		
		$output .= '<tr><th>Table</th><th>Charset</th></tr>';
		foreach ($tables as $table => $data) {
			$output .= sprintf('<tr><td style="border-top: 1px solid black; font-weight: bold;">%s</td><td style="border-top: 1px solid black; background-color: %s; font-weight: bold;">%s</td></tr>', 
				$data['Name'],
				$data['charset'] == 'utf8' ? '#CDEACA' : '#FBB19B',
				$data['charset']
			);		
			
			foreach ($data['field_charset'] as $field => $charset) {
				$output .= sprintf('<tr><td style="padding-left: 30px;">%s</td><td style="background-color: %s;">%s</td></tr>', 
					$field,
					$charset == 'utf8' ? '#CDEACA' : '#FBB19B',
					$charset
				);
			}	
		}
		$output .= '</table>';
		return $output;
	}
	
	/**
	 * Render alter statements for database and tables
	 * 
	 * @param array $nonUtf8Tables
	 * @param string $databaseCharset
	 * @return string HTML output containing render statements
	 */
	protected function renderAlterStatements(array $nonUtf8Tables, $databaseCharset) {
		$output = '<h4>Convert statements for non utf8 database and tables (and tables containing non utf8 fields):</h4>';
		$output .= '<pre>';
		
		if ($databaseCharset != 'utf8') {
			$output .= sprintf("ALTER DATABASE `%s` CHARACTER SET `utf8` COLLATE `utf8_general_ci`;".chr(10).chr(10), TYPO3_db);
		}
		foreach ($nonUtf8Tables as $table) {
			$output .= sprintf("ALTER TABLE `%s` CONVERT TO CHARACTER SET `utf8` COLLATE `utf8_general_ci`;".chr(10), $table);
		}
		$output .= '</pre>';
		return $output;
	}
	
}

?>