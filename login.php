<?php
session_start();
error_reporting(E_ALL);
include 'paths.php';
global $program_files_path,$user_data_path;
set_time_limit(0);
include 'function.php';
$pass=0;
if(isset($_POST['reg']))
{
header("Location: newUser.php"); /* Redirect browser */
}
if(isset($_POST['sub']))
{
$un=$_POST['uname'];
$upw=$_POST['pw'];
    
  $pass = login($un,$upw);

    if($pass==1)
    {
        $_SESSION['log']=1;
        $_SESSION['un']=$un;
        
        if(isset($un) && is_dir("$user_data_path/$un")){
            header("Location: usepast.php");
        }else{
            header("Location: data.php"); /* Redirect browser */
        }
    }
    if($pass == 5){
        $_SESSION['log']=1;
        $_SESSION['un']=$un;
        $_SESSION['admin'] = 1;
        header("Location: admin.php");
    }
}


?>
<html>


<head>
<link rel="stylesheet" type="text/css" href="main.css" />
<link href='http://fonts.googleapis.com/css?family=Skranji' rel='stylesheet' type='text/css'>
</head>
<body>
<div class="head">
<div class="logo">
<img src = "Netapp_logo.svg.png" width="100%" height="100%">
</div>
<div class="htext">
<center><h1><big>LOGIN</big></h1></center>
</div>
</div>
<div class="bdy">
<?php
    if($pass == 4){
        echo "<center><br>Account not approved by admin</center>";
    }
    if($pass == 2){
        echo "<center><br>Invalid User Name</center>";
    }
    if($pass == 3){
        echo "<center><br>Invalid Password</center>";
    }
    ?>

<div class="lg">
<form action="login.php" method="post">


</br></br>
<center>UserName:<input type="text" name="uname"></center></br>
<center>PassCode:<input type="password" name="pw"></center></br>
<center><input type="submit" name="sub" value="Submit">&nbsp;&nbsp;
<input type="submit" name="reg" value="Create Account"></center></br>

</form>
</div>
</div>

</body>

</html>