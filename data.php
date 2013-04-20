<?php
session_start();
include 'ftp.php';
include 'paths.php';
global $program_files_path,$user_data_path;
$un=$_SESSION['un']; 
set_time_limit(0);
$flag=0;
$cflag=0;
$msgflag=0;
$output;
$logString;
$emess;
$conn_id;
$thelist;
$dir;     //if signed in
if(isset($_SESSION['log']))
{
      //ready to parse data
     if(isset($_POST['sub4']))
     {
         if(is_dir("$user_data_path/$un")){
            exec("$program_files_path/parse_2 -p $user_data_path/$un -d  $user_data_path/$un -t -1 -1 > /dev/null 2>&1 &");
            header("Location: search2.php"); /* Redirect browser */
         }else{
             $no_data_flag = 1;
             $flag = 0;
         }
     }
      
      if(isset($_POST['sub3']))
      {
      $flag=0;
      }
      
       if(isset($_POST['download']))
       {
       $flag=3;
       
       }
   
   //change directory
    if(isset($_POST['cdir']))
    {
    $dir=$_POST['cd'];
    $_SESSION['dir']=$dir;
      $fs=$_SESSION['fs'];
      $fu = $_SESSION['fu'];
      $pw = $_SESSION['pw'];
    $conn_id=new FTP($fs,$fu,$pw);
    $msg=$conn_id->dirchange($dir);
        if($msg!=1)
        {
        $thelist=$conn_id->listFiles($conn_id);
        }
         else
         {
         $msgflag=1;
         }
         $flag=2;
    }
    //connect to ftp server
  if(isset($_POST['sub']))
  {
     $fs=$_POST['ftps'];
     $fu=$_POST['fun'];
     $pw=$_POST['fpw'];
      //checking ftp connection fields are not empty
      if(strlen($fs) > 1 and strlen($fu) > 1 and strlen($pw) > 1)
      {  
        
         $_SESSION['fs']=$fs;
         $_SESSION['fu']=$fu;
         $_SESSION['pw']=$pw;
    $logString="ftp ftp://".$fu.":".$pw."@".$fs." 2>&1";
    $conn_id=new FTP($fs,$fu,$pw);
    $thelist=$conn_id->listFiles($conn_id); 
          $_SESSION['cid']=$conn_id;
    
            $msgflag=1;
            $flag=1;
         }
         else
         {
         $cflag=1;
         }

  }

 ?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="main.css" />
<link href='http://fonts.googleapis.com/css?family=Skranji' rel='stylesheet' type='text/css'>

<script type="text/javascript">
//disable download button on bodyload
function dis()
{
document.getElementById("two").disabled = true;
}
//enable or disable download button depending on checkboxes clicked
function check()
{
var j=0;
var elem = document.getElementById('frm').elements;
        for(var i = 0; i < elem.length; i++)
        {
       
        var id = elem[i].id;
       
           if(elem[i].checked)
           {
           
           j++;
           
           }
        }
           if(j>0)
           {
           
           document.getElementById("two").disabled = false;
           }
           else
           {
        
           document.getElementById("two").disabled = true;
           }
}
                      
</script>
</head>
<body onload="dis()">
<div class="head">
<div class="logo">
<img src = "Netapp_logo.svg.png" width="100%" height="100%">
</div>
<div class="htext">
<center><h1><big>Data Retrival</big></h1></center>
</div>
</div>
<div class="bdy">
<ul>
     <li class='active'><a href='logout.php'>Logout</a></li>
     </ul>

<?php
    //ftp connection form
if ($flag==0)
{
    if(isset($no_data_flag)){
        echo "<center>No Files downloaded</center>";
    }
?>
<div class="lg2">
<form action="data.php" method="post">
</br><center>FTP Server:<input type="text" name="ftps">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;</center></br>
<center>FTP UserName:<input type="text" name="fun"></center></br>
<center>FTP PassCode:<input type="password" name="fpw"></center></br>
<center><input type="submit" name="sub" value="Connect"></center></br>
</form>
<?php
    //checking for filled parameters ftp
   if($cflag==1)
   {
   echo "<font color=\"red\">Check FTP Parameters!</font>";
   }
?>
</div>
<?php
}
   //download screen
if ($flag==1)
{
         $fs = $_SESSION['fs'];
         $fu = $_SESSION['fu'];
         $pw = $_SESSION['pw'];
echo "<div class=\"lg3\">";
echo "<center>Select Directories or Change Directory</center></br></br>";
$count=count($thelist);
echo "<form method=\"post\" action=\"data.php\" id=\"frm\">";
for($i=0;$i<$count;$i++)
{
//$temp= substr($thelist[$i], 1);
if(is_dir('ftp://'.$fu.":".$pw."@".$fs."/".$thelist[$i]))
{
echo "<input type=\"checkbox\" name=\"".$thelist[$i]."\" value=\"".$thelist[$i]."\" id=\"$i\" onclick=\"check()\">".$thelist[$i]."</br>";
}
else
{
echo $thelist[$i]."</br>";
}

}
echo "<center>Change Dir:<input type=\"text\" name=\"cd\"></center></br>";
echo "<center><input type=\"submit\" name=\"download\" value=\"Download\" id=\"two\">&nbsp;&nbsp;";
echo "<input type=\"submit\" name=\"cdir\" value=\"Change Dir\"></center></br>";

echo "</div>";
}
//after directory change
if($flag==2)
{
         $fs = $_SESSION['fs'];
         $fu = $_SESSION['fu'];
         $pw = $_SESSION['pw'];
echo "<div class=\"lg3\">";
echo "<center>Select Directories or Change Directory</center></br></br>";
$count=count($thelist);
echo "<form method=\"post\" action=\"data.php\" id=\"frm\">";
for($i=0;$i<$count;$i++)
{
//$temp= substr($thelist[$i], 1);
   if(is_dir('ftp://'.$fu.":".$pw."@".$fs.$dir."/".$thelist[$i]))
   {
   echo "<input type=\"checkbox\" name=\"".$thelist[$i]."\" value=\"".$thelist[$i]."\" id=\"$i\" onclick=\"check()\">".$thelist[$i]."</br>";
   }
   else
   {
   echo $thelist[$i]."</br>";
   }
}
echo "<center>Change Dir:<input type=\"text\" name=\"cd\"></center></br>";
echo "<center><input type=\"submit\" name=\"download\" value=\"Download\" id=\"two\">&nbsp;&nbsp;";
echo "<input type=\"submit\" name=\"cdir\" value=\"Change Dir\"></center></br>";

echo "</div>";
}

if($flag==3)
{
      $fs = $_SESSION['fs'];
      $fu = $_SESSION['fu'];
      $pw = $_SESSION['pw'];
      $un = $_SESSION['un'];
      $dir= $_SESSION['dir'];
         
       
echo "<div class=\"lg3\">";
$count=count($_POST);

$i=0;
reset($_POST);//cycle through post array to obtain checkbox names for download
while($i < $count-2)
{
if($i==0)
{
$temp=key($_POST);
$string = "/usr/local/bin/wget -r --ftp-user=".$fu." --ftp-password=".$pw." -P ".$user_data_path."/".$un." ftp://".$fs.$dir."/".$temp." 2>&1";
//echo $string."</br>";
$t2=shell_exec($string);
//$t2=shell_exec('/usr/local/bin/wget');
//echo $t2;
}
else
{
$temp=next($_POST);
$string = "/usr/local/bin/wget -r --ftp-user=".$fu." --ftp-password=".$pw." -P ".$user_data_path."/".$un." ftp://".$fs.$dir."/".$temp;
shell_exec($string);
}
$i++;
}



?>
<form action="data.php" method="post">
<center><input type="submit" name="sub3" value="Get More Data">&nbsp;&nbsp;&nbsp;<input type="submit" name="sub4" value="Continue"></center>
</form>
<?php
// $display = file('/Applications/MAMP/php_error.log');
 //$count = count($display);
 //echo '<pre>';
 //for($i=$count - 3; $i<$count; $i++){
 //echo $display[$i];
 //}
 //echo '</pre>';

echo "</div>";
}

?>

</div>
</body>
</html>

<?php
		
}//end if
else
{
header("Location: login.php"); /* Redirect browser */
}
?>