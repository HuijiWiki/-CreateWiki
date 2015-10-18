<?php

/**
 * Description of Invitatio
 *
 * @author minyitang
 */
require_once('InvitationDB.php');
require_once('ErrorMessage.php');
class Invitation {
    //put your code here
    const INVITATION_USED               = 1;
    const INVITATION_NOT_FOUND          = 2;
    
  
    
    /** Check the usage status of a given invitation code. 
     * 
     * @param String $inv The invitation code user provided
     * @return int. 0 if invitation code is valid and has not expired. Error code otherwise
     */
    public static function checkInvitation($inv){
      $ret = InvitationDB::queryInvDB($inv);
      if (!Confidential::IS_PRODUCTION )
        return 0;
      return $ret; 
    }
    
    /** set an invitation code to expire.
     * 
     * @param type $inv
     * @return boolean, true if successful, false elsewiese
     */
    public static function expireInvitation($inv){
       $status = false;
       $ret = InvitationDB::updateInv($inv, $status);
       return $ret;
    }
    
    public static function generateInvCode($num){
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789$*_!";
        
        for($i = 0; $i<$num; $i++ ){
            $code = substr( str_shuffle( $chars ), 0, 10 );
            $status = true;
            InvitationDB::insertIntoInvDB($code, $status);
        }
        
       
    }
    
    public static function getInvList($num){
        $arr = InvitationDB::getInv($num);
        $out = '<ul class="list-group">';
        foreach ($arr as $li){
            $out .= '<li class="list-group-item">'.$li.'</li>';
        }
        $out .= '</ul>';
        return $out;
    }
}
?>
