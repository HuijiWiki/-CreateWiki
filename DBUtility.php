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

      $db_name = "huiji";
      $sql = 'SELECT `domain_id` AS `exists` FROM HUIJI.DOMAIN WHERE DOMAIN.DOMAIN_PREFIX=\''.$name.'\'';
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
   */

   public static function insertGlobalDomainPrefix($domainprefix, $domainname, $domaintype, $domaindsp){
      //huiji_domain_all is the database to store the huiji_domain_all . 
      $db_name = 'huiji';
      $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd, $db_name);
      if($conn->connect_error)
      {
         die("Connection Failed");
      }
      $sql = "INSERT INTO domain (domain_prefix, domain_name, domain_type, domain_dsp, domain_status) VALUES ('{$domainprefix}', '{$domainname}', '{$domaintype}', '{$domaindsp}', 'TRUE')";
     
      if ($conn->query($sql) === TRUE) {
         $conn->close();
         return TRUE;
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
   
   /**drop a DB. TODO: Change this to "drop a bunch of tables".
    * 
    * @param type $name the domain prefix
    * @return Boolean. True if sucessful False if not. 
    */
   public static function dropDB($name){
      $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd);
      if($conn->connect_error)
      {
         die("Connection Failed");
      }
      // statement to execute

      $db_name = "huiji_".str_replace(".","_",$name);
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
?>
