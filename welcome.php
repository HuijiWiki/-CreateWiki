<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */



require_once('CreateWiki.php');
require_once('ErrorMessage.php');
require_once('Invitation.php');
// error_reporting(E_ALL);
// ini_set('display_errors', '1');


$domainprefix = $_POST["domainprefix"];
$domainprefix = strtolower ( $domainprefix ); //domain name should be case in sensitive here.
$wikiname = $_POST["wikiname"];
$dsp = $_POST["description"];
$type = $_POST["type"];

$invcode = $_POST["inv"];

$invCheck = Invitation::checkInvitation($invcode);
if($invCheck == ErrorMessage::INV_NOT_FOUND){
    die("The invitation code is not valid") ;
}
if($invCheck == ErrorMessage::INV_USED){
    die("The invitation code has expired");
}
$wiki = new CreateWiki($domainprefix, $wikiname, $type, $dsp);
$ret = $wiki->create();
if($ret == ErrorMessage::ERROR_NOT_LOG_IN){
	if (Confidential::IS_PRODUCTION){
		header( 'Location: http://home.huiji.wiki/wiki/%E7%89%B9%E6%AE%8A:%E7%94%A8%E6%88%B7%E7%99%BB%E5%BD%95' ) ;
	} else {
		header( 'Location: http://test.huiji.wiki/wiki/%E7%89%B9%E6%AE%8A:%E7%94%A8%E6%88%B7%E7%99%BB%E5%BD%95' ) ;
	}
   
}
elseif($ret == 0){
   Invitation::expireInvitation($invcode);
   header('Location: http://'.$domainprefix.'.huiji.wiki');
}
else{
    
    echo $ret;
}

?>
