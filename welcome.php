<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */



require_once('CreateWiki.php');
require_once('ErrorMessage.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');


$domainprefix = $_POST["domainprefix"];
$domainprefix = strtolower ( $domainprefix ); //domain name should be case in sensitive here.
$wikiname = $_POST["wikiname"];
$dsp = $_POST["description"];
$type = $_POST["type"];


$wiki = new CreateWiki($domainprefix, $wikiname, $type, $dsp);
$ret = $wiki->create();
echo $ret;

?>