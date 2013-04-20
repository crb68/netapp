<?php
class FTP {
public $login_result;
public $conn_id;
public $fserver;
public $fuser;
public $fpassword;
public $list=array();
//connect to ftp server and retrieve folders for data retrival 
 public function __construct($fserver,$fuser,$fpassword){
 $this->fserver=$fserver;
$this->fuser=$fuser;
$this->fpassword=$fpassword;
$ftp_port = '21';  
// set up basic connection
 $this->conn_id=$conn_id = ftp_connect($fserver,$ftp_port)or die("Couldn't connect to $fserver"); 


// login with username and password
$this->login_result=$login_result = ftp_login($conn_id, $fuser, $fpassword);
 


ftp_pasv ($this->conn_id, true) ;


// get contents of the current directory
//$contents = ftp_nlist($conn_id, ".");

return $this->conn_id;
}

function listFiles($cid)
{
$contents = ftp_nlist($this->conn_id, ".");
return $contents;
}

function dirchange($dir)
{

if (ftp_chdir($this->conn_id, $dir)) 
{
    $msg = ftp_pwd($this->conn_id);
    return $msg;
} 
else 
{ 
    $msg = 1;
    return $msg;
}

}
}//end ftp class
?>