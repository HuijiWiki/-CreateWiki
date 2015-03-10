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

      $db_name = "huiji_".str_replace(".","_",$name);
      $sql = 'SELECT COUNT(*) AS `exists` FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMATA.SCHEMA_NAME=\''.$db_name.'\'';
      # echo  $sql;
      // execute the statement
      $query = $conn->query($sql);
      if ($query === false) 
      {
         die('Query Error');
         $con->close();
      }

      // extract the value
      $row = $query->fetch_object();
      $dbExists = (bool) $row->exists;
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

   public static function insertGlobalDomainPrefix($domainprefix, $domainname){
      //huiji_domain_all is the database to store the huiji_domain_all . 
      $db_name = 'huiji_domain_all';
      $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd, $db_name);
      if($conn->connect_error)
      {
         die("Connection Failed");
      }
      $sql = "INSERT INTO domains (domain_prefix, domain_name, status) VALUES ('{$domainprefix}', '{$domainname}', 'TRUE')";
     
      if ($conn->query($sql) === TRUE) {
         
         return TRUE;
         } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
            return FALSE;
         }
      $conn->close();
   }

   public static function checkDomainExists($domainprefix){
      $db_name = 'huiji_domain_all';

      $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd, $db_name);
      if($conn->connect_error)
      {
         die("Connection Failed");
      }
      $conn->close();
   }
   
   /**drop a DB
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
         
         return TRUE;
         } else {
           echo "Error: " . $sql . "<br>" . $conn->error;
           return FALSE;
         }
      $conn->close();
   }
}
?>
