<?php
echo '<!DOCTYPE html">
<html lang="zh-cn" dir="ltr">
<head>
    <title>灰机准备起飞中</title>
</head>
<body>
<!-- Progress bar holder -->
<div id="progress" style="width:500px;border:1px solid #ccc;"></div>
<!-- Progress information -->
<div id="information" style="width"></div>';

require_once('CreateWiki.php');
require_once('ErrorMessage.php');
require_once('Invitation.php');
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
header('Content-type: text/html; charset=utf-8');

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
		echo '<script type="text/javascript">window.location="http://home.huiji.wiki/wiki/%E7%89%B9%E6%AE%8A:%E7%94%A8%E6%88%B7%E7%99%BB%E5%BD%95";</script>';
	} else {
		echo '<script type="text/javascript">window.location="http://test.huiji.wiki/wiki/%E7%89%B9%E6%AE%8A:%E7%94%A8%E6%88%B7%E7%99%BB%E5%BD%95";</script>';
	}
   
}
elseif($ret == 0){
    Invitation::expireInvitation($invcode);
    echo '<script type="text/javascript">window.location="http://'.$domainprefix.'.huiji.wiki";</script>';
    // header('Location: http://'.$domainprefix.'.huiji.wiki');
}
else{
    
    echo $ret;
}

echo '</body>
</html>';
?>
