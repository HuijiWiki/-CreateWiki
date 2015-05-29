<?php
require_once('DBUtility.php');
require_once('ErrorMessage.php');
class CreateWiki{
    
    public $domainprefix ;
    public $wikiname;
    public $domaintype;
    public $domaindsp; 

    private $id;
    /** Constructor
     *
     * 
     * @param type $prefix domain prefix
     * @param type $name domain name
     * @param type $type wiki type
     * @param type $dsp wiki description
     */
    public function __construct($prefix, $name, $type, $dsp){
        $this->domainprefix = $prefix;
        $this->wikiname = $name;
        $this->domaintype = $type;
        $this->domaindsp = $dsp;
    }
    
    /** Create a complete working sub wiki
     * 
     * @return Int; if not sucessful return the error code else 0;
     */
    public function create(){
        //----------------------------------------
        // Total processes
        $total = 10;
        $i = 1;
        $this->showProgress($total, $i);

        $ruleRet = $this->checkRule($this->wikiname, $this->domainprefix);
        if($ruleRet != 0){
            //the input from user is not valid, need to ask him to do it again
            return $ruleRet;
        }
        $i = 2;
        $this->showProgress($total, $i);
        $sessionRet = $this->checkUserSession();
        if($sessionRet == false){
            //user is not logged in. redirect him to the log_in page
           
            return ErrorMessage::ERROR_NOT_LOG_IN;
        }
        
        $i = 3;
        $this->showProgress($total, $i);
        $dirRet = $this->createWikiDir($this->domainprefix);
        if($dirRet != 0){
            //revoke directory creation
            $this->removeWikiDir($this->domainprefix);
            return $dirRet;
        }
        $i = 4;
        $this->showProgress($total, $i);
        $installRet = $this->newWikiInstall($this->domainprefix, $this->wikiname, $this->domaintype, $this->domaindsp);
        if($installRet!=0){
            //revoke directory creation
            //revoke install
            $this->removeWikiDir($this->domainprefix);
            $this->removeWikiInstall($this->domainprefix, $this->wikiname);
            return $installRet;
        }
        $updateRet = $this->updateLocalSettings($this->domainprefix, $this->wikiname);
        if($updateRet!=0){
            //revoke all
             $this->removeWikiDir($this->domainprefix);
             $this->removeWikiInstall($this->domainprefix, $this->wikiname);
            return $updateRet;
        }
        $i = 5;
        $this->showProgress($total, $i);
        $this->promote($this->domainprefix, $sessionRet);
        $i = 6;
        $this->showProgress($total, $i);
            
        //redirect to the newly created wiki
        return 0; 
    }
    
    public function checkUserPreviliage(){
        
    }
    
    public function checkRule($name, $domain, $venue=null, $language=null, $type=null){
        $status = 0;
    if( strlen( $domain ) === 0 ) {
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
        $this->id = DBUtility::insertGlobalDomainPrefix($domainprefix, $wikiname, $domaintype, $domaindsp);
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
            //Don't ever drop a db in production server, just mark it as unavailable.
        } else {
            DBUtility::dropDB('huiji_'.str_replace('.', '_', $domainprefix));
        }
        
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
                $api_end = 'http://home.huiji.wiki/api.php?action=query&format=xml&meta=userinfo';
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
                return $id[1];
                
            }
            else{
                return false;

            }
        }
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
        $command = "php /var/www/virtual/".$domainprefix."/maintenance/update.php  --conf=/var/www/virtual/".$domainprefix."/LocalSettings.php --quick --doshared";
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
    public function showProgress($total, $current){
        $percent = intval($current/$total * 100)."%";
        echo '<script language="javascript">document.getElementById("progress").innerHTML="<div style=\"width:'.$percent.';background-color:#ddd;\">&nbsp;</div>";document.getElementById("information").innerHTML="'.$current.' row(s) processed.";</script>';
        echo str_repeat(' ',1024*64);
        flush();
        if ( $current === $total ){
            echo '<script language="javascript">document.getElementById("information").innerHTML="Process completed"</script>';
        }
    }

}   
?>
