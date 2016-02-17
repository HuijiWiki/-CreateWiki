<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if (php_sapi_name() === 'cli'){
	require_once('DBUtility.php');

	$ret = DBUtility::dropTablesWithPrefix('dota2');
	echo $ret;
}

?>
