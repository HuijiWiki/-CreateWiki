<?php
require_once('DBUtility.php');
require_once('ErrorMessage.php');
class CreateWiki{
    
    public $domainprefix ;
    public $wikiname;
    public $domaintype;
    public $domaindsp; 
    private $founderid;
    private $foundername;
    private $manifestName;


    private $steps = array(
        1 => '点火准备',
        2 => '请检查氧气阀压力',
        3 => '请确认带好毛巾',
        4 => '请系好安全带',
        5 => '主发动机点火中……',
        6 => '请不要恐慌',
        7 => '倒计时开始……',
        8 => '灰机已经起飞！重复一遍，灰机已经起飞！',
    );

    private $id;
    /** Constructor
     *
     * 
     * @param type $prefix domain prefix
     * @param type $name domain name
     * @param type $type wiki type
     * @param type $dsp wiki description
     */
    public function __construct($prefix, $name, $type, $dsp , $manifestName){
        $this->domainprefix = $prefix;
        $this->wikiname = $name;
        $this->domaintype = $type;
        $this->domaindsp = $dsp;
        $this->manifestName = $manifestName;
    }
    
    /** Create a complete working sub wiki
     * 
     * @return Int; if not sucessful return the error code else 0;
     */
    public function create(){
        //----------------------------------------
        // Total processes

        $ruleRet = $this->checkRule($this->wikiname, $this->domainprefix);
        if($ruleRet != 0){
            //the input from user is not valid, need to ask him to do it again
            return $ruleRet;
        }
        $i = 1;
        $this->showProgress($i);
        $sessionRet = $this->checkUserSession();
        if($sessionRet == false){
            //user is not logged in. redirect him to the log_in page
           
            return ErrorMessage::ERROR_NOT_LOG_IN;
        }
        
        $i = 2;
        $this->showProgress($i);
        $dirRet = $this->createWikiDir($this->domainprefix);
        if($dirRet != 0){
            //revoke directory creation
            $this->removeWikiDir($this->domainprefix);
            return $dirRet;
        }
        $i = 3;
        $this->showProgress($i);
        $installRet = $this->newWikiInstall($this->domainprefix, $this->wikiname, $this->domaintype, $this->domaindsp);
        if($installRet!=0){
            //revoke directory creation
            //revoke install
            $this->removeWikiDir($this->domainprefix);
            $this->removeWikiInstall($this->domainprefix, $this->wikiname);
            return $installRet;
        }
        $i = 4;
        $this->showProgress($i);
        $updateRet = $this->updateLocalSettings($this->domainprefix, $this->wikiname);
        if($updateRet!=0){
            //revoke all
             $this->removeWikiDir($this->domainprefix);
             $this->removeWikiInstall($this->domainprefix, $this->wikiname);
            return $updateRet;
        }
        $i = 5;
        $this->showProgress($i);
        $this->promote($this->domainprefix, $sessionRet);
        // stop the process for 2 seconds, give system more time to update wiki.
        sleep(2);
        $i = 6;
        $this->showProgress($i);

        if($this->manifestName === "empty"){

        }
        else if($this->manifestName === "internal"){
           // $manifestChoice = "Manifest:灰机基础包";
            $this->migrateInitialManifest($this->domainprefix);
        }
        else if($manifest === "external"){
            $fromDomain = $_POST["fromDomain"]; //get the wikia site to get the nav bar informaiton
            $toDomain = $this->domainprefix.".huiji.wiki";
            $this->migrateWikia($fromDomain, $toDomain);
        }
        $i = 7;
        $this->showProgress($i);
        $this->enableES();
        $i = 8;
        $this->showProgress($i);
        // header('Location: http://'.$domainprefix.'.huiji.wiki');
    
        
        // $this->migrateInitialTemplate($this->domainprefix, $this->template);

        //redirect to the newly created wiki
        return 0; 
    }
    
    public function checkUserPreviliage(){
        
    }
    
    public function checkRule($name, $domain, $venue=null, $language=null, $type=null){
        $status = 0;
        $reg = "/[A-Za-z0-9][A-Za-z0-9-]*/i";


    if( strlen( $domain ) === 0 || empty($domain) || empty($name) ) {
        // empty field
        $status = ErrorMessage::ERROR_DOMAIN_IS_EMPTY;
    }
    elseif ( strlen( $domain ) < 3 ) {
        // too short
        $status = ErrorMessage::ERROR_DOMAIN_TOO_SHORT;
    }
    elseif ( strlen( $domain ) > 30 ) {
        // too long
        $status = ErrorMessage::ERROR_DOMAIN_TOO_LONG;
    }
    elseif( preg_match($reg, $domain) !== 1 && Confidential::IS_PRODUCTION){
        $status = ErrorMessage::ERROR_DOMAIN_INVALID_CHAR;
    }
    elseif ( strpos ($domain, '.') !== false && Confidential::IS_PRODUCTION ) {
        //no dot allowed in production server
        $status = ErrorMessage::ERROR_DOMAIN_BAD_NAME;
    }
        
    else {
            if( DBUtility::domainExists( $domain) ) {
              $status = ErrorMessage::ERROR_DOMAIN_NAME_TAKEN;
            }
    }
    return $status;
    }
    
    
    /**
     * 
     * @param type $domainprefix
     * @return Int, return error code if not successful, 0 if successful
     */
    public function createWikiDir($domainprefix, $srcDir=null){
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $name = $domainprefix;
    
        $structure = "/var/www/virtual/".$name;
        

            // To create the nested structure, the $recursive parameter 
            // to mkdir() must be specified.
        $oldmask = umask(0);
        if (!mkdir($structure, 0777,true)) {
            return ErrorMessage::ERROR_FAIL_FOLDER;
        }
        if(!mkdir($structure."/uploads",0777,true)) {
            return ErrorMessage::ERROR_FAIL_CREATE_UPLOAD;
        }
        if(!mkdir($structure."/cache",0777,true)) {
            return ErrorMessage::ERROR_FAIL_CREATE_CACHE;
        }
        //the source link from the linked folder
        // if($srcDir == null){
        //  $srcDir = "/var/www/src/extensions/SocialProfile";   
        // }
        
        // self::xcopy($srcDir."/avatars", $structure."/uploads/avatars");
        // self::xcopy($srcDir."/awards", $structure."/uploads/awards");    

        umask($oldmask);
        // use shared avatars and awards from the main site.
        exec('ln -s /var/www/html/uploads/avatars '.$structure."/uploads/avatars");
        exec('ln -s /var/www/html/uploads/awards '.$structure."/uploads/awards");
        exec('ln -s /var/www/src/* '.$structure);
        return 0;
    }
    
    /** remove a created wiki directory. 
     * 
     * @param type $domainprefix, domain prefix entered by users
     * @return 0 if successful, errorcode if not. 
      */
    public function removeWikiDir($domainprefix){
        $structure = "/var/www/virtual/".$domainprefix;
        $cmd = "rm -r ".$structure;
        exec($cmd, $output, $return_var);
        if($return_var){
            return ErrorMessage::ERROR_REMOVE_DIR;
        }
        return 0;
   
    }
    
    
    
    
    
    /**install a new wiki by running the install.php script. 
     * 
     * @param type $domainprefix
     * @param type $wikiname
     * @return int error code if fails, 0 if successful
     */
    
   public function newWikiInstall( $domainprefix, $wikiname, $domaintype, $domaindsp){
       //create wll the script params
        $domainDir = str_replace(".","_",$domainprefix);
        $name = "huiji_".$domainDir;
        $structure = '/var/www/virtual/'.$domainprefix;
        $install_cmd = "php ".$structure."/maintenance/install.php --dbuser=".Confidential::$username." --dbpass=".Confidential::$pwd;
        $name_admin = " ".$wikiname." ".$wikiname."_admin";
        $confpath = " --confpath=".$structure;
        $pass = " --pass=123123 ";
        $install_db = " --installdbuser=".Confidential::$username." --installdbpass=".Confidential::$pwd;
        if (Confidential::IS_PRODUCTION){
            $db_info= " --dbserver=".Confidential::$servername." --dbname=huiji_sites --dbprefix=".$domainDir;
        } else {
            $db_info= " --dbserver=localhost --dbname=".$name;
        }
        $script_path = " --scriptpath=";
        $lang = " --lang=zh-cn";
        $install_cmd = $install_cmd.$name_admin.$confpath.$pass.$install_db.$db_info.$script_path.$lang;

        if(!exec($install_cmd)){
            return ErrorMessage::ERROR_FAIL_EXE_INSTALL_CMD;
        }
	$this->id = DBUtility::insertGlobalDomainPrefix($domainprefix, $wikiname, $domaintype, $domaindsp, $this->founderid, $this->foundername);
        DBUtility::insertInterwikiPrefix($domainprefix, $this->id);
        return 0;
    }
    
    /** revoke the process of install a wiki 
     * 
     * @param type $domainprefix
     * @param type $wikiname
     */
    public function removeWikiInstall($domainprefix, $wikiname){
        if (Confidential::IS_PRODUCTION){
            DBUtility::dropDB($domainprefix);
        } else {
            DBUtility::dropDB($domainprefix);
        }
        
    }
   
    public function enableES(){
	$localPreix = strtolower($this->domainprefix);
	$localConf = '/var/www/virtual/'.$localPreix.'/LocalSettings.php';
	$createESIndex = "php /var/www/src/extensions/CirrusSearch/maintenance/updateSearchIndexConfig.php --conf=".$localConf;
	exec($createESIndex);
	$bootstrapES_noLinks = "php /var/www/src/extensions/CirrusSearch/maintenance/forceSearchIndex.php --skipLinks --indexOnSkip --conf=".$localConf.' &';
	exec($bootstrapES_noLinks);
	$bootstrapES_links = "php /var/www/src/extensions/CirrusSearch/maintenance/forceSearchIndex.php --skipParse --conf=".$localConf.' &'; 
	exec($bootstrapES_links);
    }
    
    /**Check the current user session
     * 
     * @return boolean?int False if no user found, userid if found user session
     */
    public function checkUserSession(){
        $session_cookie = 'huiji_session';
        if(!isset($_COOKIE[$session_cookie]))
        {
            return false;
        }
        else
        {
            $ch = curl_init();
            if(Confidential::IS_PRODUCTION){
                $api_end = 'http://www.huiji.wiki/api.php?action=query&format=xml&meta=userinfo';
            }else{
                $api_end = 'http://test.huiji.wiki/api.php?action=query&format=xml&meta=userinfo';
            }
            curl_setopt($ch, CURLOPT_URL, $api_end);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_COOKIE, $session_cookie . '=' . $_COOKIE[$session_cookie]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $ret = curl_exec ($ch);

            curl_close ($ch);

            if(preg_match('/id="(\d+)"/',$ret,$id) && $id[1]){
                #pop a simple window for user to wait 
                #   return $id;
                $this->founderid = $id[1];
                preg_match('/name="(.*?)"/u',$ret,$name);
                $this->foundername = $name[1];
                return $id[1];
                
            }
            else{
                return false;

            }
        }
    }


/** 
    * When Create Wiki, copy the initial templates into the newly created wiki site
    *
    * @param $domainprefix : the domain prefix of the inital template
    * @param $iniTemplateName: the choice from user about which template user wants to install.
    *
    * @return true if the curl call is sucessful, false otherwise.
    **/
    public function migrateWikia($fromDomain, $toDomain){
        $params = array('fromDomain'=>$fromDomain, 'targetDomain'=>$toDomain);
        $ch = curl_init();
        $param_url = http_build_query($params);
        curl_setopt($ch, CURLOPT_URL, 'http://www.huiji.wiki:3000/service/mm?'.$param_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        $ret = curl_exec($ch);
        curl_close($ch);
        return $ret;
    }


    /** 
    * When Create Wiki, copy the initial templates into the newly created wiki site
    *
    * @param $domainprefix : the domain prefix of the inital template
    * @param $iniTemplateName: the choice from user about which template user wants to install.
    *
    * @return true if the curl call is sucessful, false otherwise.
    **/


    public function migrateInitialManifest($domainprefix){ 
        $targetDomain = $domainprefix.".huiji.wiki";
        $fromDomain = "templatemanager.huiji.wiki";
        $manifestName = "Manifest:灰机基础包";
        $params = array('fromDomain' => $fromDomain, 'targetDomain' => $targetDomain,'skeletonName' => $manifestName);
        $ch = curl_init();
        $param_url = http_build_query($params);
        if (Confidential::IS_PRODUCTION){
            curl_setopt($ch, CURLOPT_URL, 'http://www.huiji.wiki:3000/service/smp?'.$param_url);
        } else {
            curl_setopt($ch, CURLOPT_URL, 'http://test.huiji.wiki:3000/service/smp?'.$param_url);
        }
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        $ret = curl_exec($ch);
        curl_close($ch);

        return $ret;
    }






        /** Replace the current LocalSettings.php after it is generated
         * 
         * @param string $srcDir source directroy to copy template from
         * @param string $targetDir target directory to copy template to
         */

    public function copyTemplateLocalSetting($srcDir=null, $targetDir=null){
        if($srcDir == null){
            $srcDir = '/var/www/src/LocalSettings.php.example';
        }
        if($targetDir == null){
            $targetDir = './LocalSettings.php';
        }
        copy($srcDir,$targetDir);
    }



    /**update the localsetting.s.php
         * 
         * @param type $domainprefix
         * @param type $wikiname
         * @param string $fileName
         * @return int 0 if suceessfu.
         */
    public function updateLocalSettings($domainprefix, $wikiname,$fileName=null ){
        #$domainDir = str_replace(".","_",$domainprefix);
        $fileName = '/var/www/virtual/'.$domainprefix.'/LocalSettings.php';
        $templateName = '/var/www/src/LocalSettings.php.example';
        self::copyTemplateLocalSetting($templateName,$fileName);

        $file_contents = file_get_contents($fileName);
        $file_contents = str_replace("%wikiname%",$wikiname,$file_contents);
        $file_contents = str_replace("%domainprefix%",$domainprefix,$file_contents);
        $file_contents = str_replace("%wikiid%",$this->id,$file_contents);
        file_put_contents($fileName,$file_contents);
                
        self::updateDatabase($domainprefix);
        return 0; 
    }
    

    /**
    * Run the update.php in /maintenance.php to create and register necessary dbs for extensions 
    * $domainprefix : the domain prefix for the new wiki
    */
    public function updateDatabase($domainprefix){
        $command = "php /var/www/virtual/".$domainprefix."/maintenance/update.php  --conf=/var/www/virtual/".$domainprefix."/LocalSettings.php --quick";
        exec($command);
        exec($command);
        exec($command);
    }
    
    /**
    * promote a user to admin stage of the wiki
    * $domainprefix : the domain prefix for the new wiki
    * $userid : the user id of the user in glabal table. 
    */

    public function promote($domainprefix, $userid){
        $command = "php /var/www/virtual/".$domainprefix."/maintenance/createAndPromoteFromId.php --conf=/var/www/virtual/".$domainprefix."/LocalSettings.php --force --bureaucrat --sysop ".$userid;
        exec($command);
    }

    /** 
    * recursively copy all files from one folder to another.
    */
    public function xcopy($source, $dest, $permissions = 0777)
    {
        // Check for symlinks
        if (is_link($source)) {
            return symlink(readlink($source), $dest);
        }

        // Simple copy for a file
        if (is_file($source)) {
            return copy($source, $dest);
        }

        // Make destination directory
        if (!is_dir($dest)) {
            mkdir($dest, $permissions);
        }

        // Loop through the folder
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Deep copy directories
            self::xcopy("$source/$entry", "$dest/$entry", $permissions);
        }

        // Clean up
        $dir->close();
        return true;
    }




    /**
    * Make use of javascript to show the progress percentage.
    * @param $total: int
    * @param $current: int
    *
    */
    public function showProgress($current){
        $percent = intval($current/count($this->steps) * 100)."%";
        echo '<script type="text/javascript">document.getElementById("progress").innerHTML="<div class=\"progress-bar\" style=\"width:'.$percent.';\">&nbsp;</div>";
                $("#information").prepend("<h3 id=\"step_'.$current.'\">'.$this->steps[$current].'</h3>");
                $("#step_'.$current.'").textillate("{loop: false, initialDelay: 0, in:{effect: \'flip\', delayScale: 1, delay: 150, reverse: true}}");
            </script>';
        echo str_repeat(' ',1024*64);
        flush();
    }

}   
?>
