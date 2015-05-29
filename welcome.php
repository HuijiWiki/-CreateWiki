<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html lang="en">
<head>
    <title>Progress Bar</title>
</head>
<body>
<!-- Progress bar holder -->
<div id="progress" style="width:500px;border:1px solid #ccc;"></div>
<!-- Progress information -->
<div id="information" style="width"></div>
<?php


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
	// Tell user that the process is completed
	echo '<script language="javascript">document.getElementById("information").innerHTML="Process completed"</script>';
    Invitation::expireInvitation($invcode);
    header('Location: http://'.$domainprefix.'.huiji.wiki');
}
else{
    
    echo $ret;
}

?>
</body>
</html>
