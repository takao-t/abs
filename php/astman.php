<?php 
//
// Asterisk AMI Interface
// From voip-info.org
// https://www.voip-info.org/asterisk-manager-example-php/
// Modify by TT

namespace AbspFunctions;

class AstMan {

  var $socket;
  var $error;
  
  function AstMan()
  {
    $this->socket = FALSE;
    $this->error = "";
  } 

  function Login($host , $username, $password){
    
    $this->socket = @fsockopen("127.0.0.1","5038", $errno, $errstr, 1); 
    if (!$this->socket) {
      $this->error =  "Could not connect - $errstr ($errno)";
      return FALSE;
    }else{
      stream_set_timeout($this->socket, 1); 
  
      $wrets = $this->Query("Action: Login\r\nUserName: $username\r\nSecret: $password\r\nEvents: off\r\n\r\n"); 

     	if (strpos($wrets, "Message: Authentication accepted") != FALSE){
        return true;
      }else{
  		  $this->error = "Could not login - Authentication failed";
        fclose($this->socket); 
        $this->socket = FALSE;
  		  return FALSE;
   	  }
    }
  }
  
  function Logout(){
    $wrets = "";
    if ($this->socket){
      fputs($this->socket, "Action: Logoff\r\n\r\n"); 
      while (!feof($this->socket)) { 
        $wrets .= fread($this->socket, 8192); 
      } 
      fclose($this->socket); 
      $this->socket = "FALSE";
    }
  	return; 
  }
  
  function Query($query){
    $wrets = "";
    
    if ($this->socket === FALSE)
      return FALSE;
      
    fputs($this->socket, $query); 
    do
    {
      $line = fgets($this->socket, 4096);
      $wrets .= $line;
      $info = stream_get_meta_data($this->socket);
    }while ($line != "\r\n" && $info['timed_out'] == false );
    return $wrets;
  }
  
  function GetError(){
    return $this->error;
  }
  
  function GetDB($family, $key){
    $value = "";
  
    $wrets = $this->Query("Action: Command\r\nCommand: database get $family $key\r\n\r\n");
  
    if ($wrets){
      $value_start = strpos($wrets, "Value: ") + 7;
      $value_stop = strpos($wrets, "\n", $value_start);
    	if ($value_start > 8){
        $value = substr($wrets, $value_start, $value_stop - $value_start);
      }
  	}
        // Need trim(trailing white space)
        $value = trim($value);

   	return $value;
  }	
  
  function PutDB($family, $key, $value){
    $wrets = $this->Query("Action: Command\r\nCommand: database put $family $key $value\r\n\r\n");
  
  	if (strpos($wrets, "Updated database successfully") != FALSE){
  		return TRUE;
   	}
    $this->error =  "Could not updated database";
   	return FALSE;
  }	
  
  function DelDB($family, $key){
    $wrets = $this->Query("Action: Command\r\nCommand: database del $family $key\r\n\r\n");

  	if (strpos($wrets, "Database entry removed.") != FALSE){
  		return TRUE;
   	}
    $this->error =  "Database entry does not exist";
   	return FALSE;
  }	
  
  // BIG Modify.  
  function GetFamilyDB($family){
    $wrets = $this->Query("Action: Command\r\nCommand: database show $family\r\n\r\n");
    if ($wrets){
      $lines = explode("\n", $wrets);
      $i = 0;
      foreach($lines as $line){
        if(strpos($line, "Response: Success") !== false) continue;
        if(strpos($line, "Message: Command") !== false) continue;
        if(strpos($line, "results found") !== false) break;
          $value[$i] = trim(str_replace("Output: /$family/", '', $line));
          $i = $i + 1;
      }
   	  if(isset($value)) return $value;
          else return '';
  	}
    return FALSE;
  }	  

  function ExecCMD($param){
    $wrets = $this->Query("Action: Command\r\nCommand: $param\r\n\r\n");
    return $wrets;
  }	

}
?> 
