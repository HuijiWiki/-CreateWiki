<?php


require_once('Confidential.php');
class DBUtility
{
   /** 
   *Version 0.0.1 check in the data base whether the domain user wants to take
   *$name : domain name 
   *$language, $type: ignored atm
   */
   public static function domainExists($name, $language=null, $type=null){
      $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd);
      if($conn->connect_error)
      {
         die("Connection Failed");
      }
      // statement to execute
      $name = mysqli_real_escape_string($conn, $name);
      $db_name = "huiji";
      $sql = 'SELECT `domain_id` AS `exists` FROM huiji.domain WHERE domain.domain_prefix=\''.$name.'\'';
      # echo  $sql;
      // execute the statement
      $query = $conn->query($sql);
      if ($query === false) 
      {
         die('Query Error');
         $conn->close();
      }
      // extract the value
      $dbExists = (bool) ($query->num_rows > 0);
      $conn->close();
      #echo "value :".$dbExists;
      return $dbExists;
   }


  

   /**
   *This function inserts the newly created domain prefix into the global domain_prefix database table  
   *
   *Database : huiji_domain_all
   *table : domains {domain_prefix : VARCHAR, domain_name : VARCHAR}
   *
   *$domainprefix : the new domain prefix
   *$domainname : the new domain name 
   *
   * @return mixed. FALSE if failed to insert, id if successfully inserted.
   *
   */

   public static function insertGlobalDomainPrefix($domainprefix, $domainname, $domaintype, $domaindsp){
      //huiji_domain_all is the database to store the huiji_domain_all . 
      $db_name = 'huiji';
      $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd, $db_name);
      if($conn->connect_error)
      {
         die("Connection Failed");
      }
      $domainprefix = mysqli_real_escape_string($conn, $domainprefix);
      $domainname = mysqli_real_escape_string($conn, $domainname);
      $domaintype = mysqli_real_escape_string($conn, $domaintype);
      $domaindsp = mysqli_real_escape_string($conn, $domaindsp);
      $sql = "INSERT INTO domain (domain_prefix, domain_name, domain_type, domain_dsp, domain_status) VALUES ('{$domainprefix}', '{$domainname}', '{$domaintype}', '{$domaindsp}', 'TRUE')";
     
      if ($conn->query($sql) === TRUE) {
         $last_id = $conn->insert_id;
         $conn->close();
         return $last_id;
      } else {
         echo "Error: " . $sql . "<br>" . $conn->error;
         $conn->close();
         return FALSE;
      }
      $conn->close();
   }

   /**
   *This function inserts Interwiki Prefix into global interwiki table  
   *
   *Database : huiji.interwiki
   *table : domains {domain_prefix : BLOB, domain_name : BLOB}
   *
   *$domainprefix : the new domain prefix
   *$domainid : the new domain name 
   *
   * @return mixed. FALSE if failed to insert, true if successfully inserted.
   *
   */

   public static function insertInterwikiPrefix($domainprefix, $domainid){
      //huiji.interwiki is the database to store the global interwiki links. 
      $db_name = 'huiji';
      $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd, $db_name);
      if($conn->connect_error)
      {
         die("Connection Failed");
      }
      $url = 'http://'.$domainprefix.'.huiji.wiki/wiki/$1';
      $api = 'http://'.$domainprefix.'.huiji.wiki/api.php';

      $domainprefix = mysqli_real_escape_string($conn, $domainprefix);
      $domainid = mysqli_real_escape_string($conn, $domainid);
      $sql = "INSERT INTO interwiki (iw_prefix, iw_url, iw_api, iw_wikiid, iw_local, iw_trans) VALUES ('{$domainprefix}', '{$url}', '{$api}', '{$domainid}', '1', '1')";
     
      if ($conn->query($sql) === TRUE) {
         $conn->close();
         return true;
      } else {
         echo "Error: " . $sql . "<br>" . $conn->error;
         $conn->close();
         return FALSE;
      }
      $conn->close();
   }

   public static function checkDomainExists($domainprefix){
      $db_name = 'huiji';

      $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd, $db_name);
      if($conn->connect_error)
      {
         die("Connection Failed");
      }
      $conn->close();
   }
   
   /**drop a DB. 
    * 
    * @param type $name the domain prefix
    * @return Boolean. True if sucessful False if not. 
    */
   public static function dropDB($name){
      if (Confidential::IS_PRODUCTION){
         return self::dropTablesWithPrefix($name);
      } else{
         $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd);
         if($conn->connect_error)
         {
            die("Connection Failed");
         }
         // statement to execute

         $db_name = "huiji_".str_replace(".","_",$name);
         $db_name = mysqli_real_escape_string($conn, $db_name);
         $sql = "DROP DATABASE IF EXISTS ".$db_name;
         if ($conn->query($sql) === TRUE) {
            $conn->close();
            return TRUE;
         } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
            $conn->close();
            return FALSE;
         }         
      }

      
      }
   /**drop a table with given prefix.
    *
    * @param type $prefix the domain prefix
    * @return Boolean. True if sucessful False if not. 
    */
   
   public static function dropTablesWithPrefix($prefix){

    $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd);
      if($conn->connect_error)
      {
         die("Connection Failed");
      }
      $conn->query("SET group_concat_max_len = 10000");
      $db_name = "huiji_sites";
      $prefix = mysqli_real_escape_string($conn, $prefix);
      $sql = "SELECT CONCAT( 'DROP TABLE ', GROUP_CONCAT(table_name) , ';' ) \n"
    . " AS statement FROM information_schema.tables \n"
    . " WHERE table_schema = '".$db_name."' AND table_name LIKE '".$prefix."%'";
      $result = $conn->query($sql);
      $row = $result->fetch_row();
      $result->close();
      mysqli_select_db($conn, $db_name);
      if( $conn->query($row[0])){
             $conn->close();
	     self::dropRelatedTables($prefix);
             return true;
      }
      self::dropRelatedTables($prefix);
      echo "Error:" . $conn->error;
      $conn->close();
      return false;

   }
  /**
   * Drop entries in interwiki/domain/user_site_follow tables
   *
   */
  public static function dropRelatedTables($prefix){
	$db_name = "huiji";
        $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd, $db_name);
            if($conn->connect_error)
            {
               die("Connection Failed");
            }
        $prefix = mysqli_real_escape_string($conn, $prefix);
        $arr = array (array(
                    'tbname' =>'interwiki',
                    'param' => 'iw_prefix',
                    'condition' => $prefix
                ),
                array(
                    'tbname' =>'domain',
                    'param' => 'domain_prefix',
                    'condition' => $prefix
                ),
                array(
                    'tbname' =>'user_site_follow',
                    'param' => 'f_wiki_domain',
                    'condition' => $prefix
                ));
        foreach ($arr as $key => $value) {
            
            $sql = "DELETE from ".$value['tbname']." WHERE '".$value['param']."' = '".$value['condition']. "'";
            $res = $conn->query($sql);
            if( $res ){
                $conn->close();
                return true;
            }else{
                echo 'error:'.$conn->error;
                $conn->close();
                return false;
            }
            
        }

  }

}
?>
