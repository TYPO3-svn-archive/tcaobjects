<?php 

/**
 * Database status provider
 * 
 * @author Fabrizio Branca
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
		
		$tables = $this->getTableData();
		$message = $this->renderMessage($tables);
		if ($this->allUtf8($tables)) {
			$severity = tx_reports_reports_status_Status::OK;
			$value = 'OK';
		} else {
			$severity = tx_reports_reports_status_Status::WARNING;
			$value = 'WARNING';
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
			$query = 'SHOW CREATE TABLE ' . $table;
			$res = $GLOBALS['TYPO3_DB']->admin_query($query);
			$create = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
			$createStatement = $create[1];
			$matches = array();
			preg_match('/DEFAULT CHARSET=(\w+)/', $createStatement, $matches);
			$data['charset'] = $matches[1];
		}
		return $tables;
	}
	
	/**
	 * Check if all tables are in utf8
	 * 
	 * @param array $tables
	 * @return bool
	 */
	protected function allUtf8(array $tables) {
		foreach ($tables as $table => $data) {
			if ($data['charset'] != 'utf8') {
				return false;
			}		
		}
		return true;
	}
	
	/**
	 * Render message
	 * 
	 * @param array $tables
	 * @return string html
	 */
	protected function renderMessage(array $tables) {
		$nonUtf8Tables = array();
		$output = '<table>';
		$output .= '<tr><th>Table</th><th>Collation</th><th>Charset</th><th>Engine</th></tr>';
		foreach ($tables as $table => $data) {
			$output .= sprintf('<tr><td>%s</td><td>%s</td><td style="background-color: %s;">%s</td><td>%s</td></tr>', 
				$data['Name'],
				$data['Collation'],
				$data['charset'] == 'utf8' ? '#CDEACA' : '#FBB19B',
				$data['charset'],
				$data['Engine']
			);
			if ($data['charset'] != 'utf8') {
				$nonUtf8Tables[] = $table;
			}
		}
		$output .= '</table>';
		
		if (count($nonUtf8Tables)) {
			$output .= '<h4>Convert statements for non utf8 tables:</h4>';
			$output .= '<pre>';
			foreach ($nonUtf8Tables as $table) {
				$output .= sprintf('ALTER TABLE %s CONVERT TO CHARACTER SET utf8;'.chr(10), $table);
				
			}
			$output .= '</pre>';
		}
		return $output;
	}
	
}

?>