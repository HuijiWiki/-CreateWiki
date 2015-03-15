<?php


/**
 * Description of InvitationDB
 *
 * @author minyitang
 */

require_once('Confidential.php');
require_once('ErrorMessage.php');
class InvitationDB {
    const INV_DB = "invitationDB";
    const INV_TB = "invitation_code_table";
    //put your code here
    
    /** insert a invitation and its usage status into the database.
     * 
     * @param String $invcode the invitation code 
     * @param Int $status the usage status of the invitation code
     * @return boolean true if successfu, false elsewise.
     */
    public static function insertIntoInvDB($invcode, $status){
        $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd, self::INV_DB);
      if($conn->connect_error)
      {
         die("Connection Failed");
      }
      $sql = "INSERT IGNORE INTO ".self::INV_TB." (invitation_code, status) VALUES ('{$invcode}', '{$status}')";
      if ($conn->query($sql) === TRUE) {
         
         return TRUE;
         } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
            return FALSE;
         }
      $conn->close();
    }
    
    /** query the database to check the invitation code status
     * 
     * @param string $invcode
     * @return int|boolean 0 if invitation code is valid, error code otherwise, false if db connection failed
     */
    public static function queryInvDB($invcode){
      $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd, self::INV_DB);
      if($conn->connect_error)
      {
         die("Connection Failed");
      }
      $sql = "SELECT t.status from ".self::INV_TB. " as t Where `invitation_code`='{$invcode}'";
      $query = $conn->query($sql);
      if ($query === false) 
      {
         echo $conn->error;
         $conn->close();
         return false;
        
      }

      // extract the value
      $conn->close();
      $row = $query->fetch_object();
      if($row == null){
          
          return ErrorMessage::INV_NOT_FOUND;
      }
      $status = (int) $row->status;
      if($status == 0){
        
          return ErrorMessage::INV_USED;
      }
     
      return 0;
     
    }
    
    public static function updateInv($inv, $status){
      $conn = mysqli_connect(Confidential::$servername,Confidential::$username,Confidential::$pwd, self::INV_DB);
      if($conn->connect_error)
      {
         die("Connection Failed");
      }
      $sql = "UPDATE ".self::INV_TB." SET `status`='{$status}' WHERE `invitation_code`='{$inv}'";
      $query = $conn->query($sql);
       $conn->close();
      if ($query === false) 
      {
         echo $conn->error;
        
         return false;
        
      }
      return true;
    }
}
