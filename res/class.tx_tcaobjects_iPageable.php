<?php

interface tx_tcaobjects_iPageable {
	
	public function getTotalItemCount($where = '');
	
	public function getItems($where = '', $limit = '', $order = '');
	
}

?>