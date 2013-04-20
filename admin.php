<?php
session_start();
include 'paths.php';
global $program_files_path,$user_data_path;
$un=$_SESSION['un'];
set_time_limit(0);

if(isset($_SESSION['log']) && isset($_SESSION['admin']))
{
    
    require ("dbinfo.php");
    
    $mysqli = new mysqli("localhost", $db_user, $db_pass, $db_name);
    
    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
    if(isset($_POST['search'])){
        try {
            /* create a prepared statement */
            $stmt = $mysqli->prepare("SELECT username,email,fname,lname,user_level FROM user where username like ? or email like ? or fname like ? or lname like ? order by username");
            
        } catch (mysqli_sql_exception $e) {
            
            echo $e->__toString();
        }
        $param = '%'.$_POST['param'].'%';
        
        /* bind parameters for markers */
        $stmt->bind_param('ssss',$param,$param,$param,$param);

    }else{
        try {
            
            /* create a prepared statement */
            $stmt = $mysqli->prepare("SELECT username,email,fname,lname,user_level FROM user order by username");
            
        } catch (mysqli_sql_exception $e) {
            
            echo $e->__toString();
        }
    }
    if ($stmt) {
        /* execute query */
        $stmt->execute();

        /* bind result variables */
        $stmt->bind_result($user,$email,$fname,$lname,$user_lvl);
        
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
<center><h1><big>Admin</big></h1></center>
</div>
</div>
<div class="bdy">
<ul>
     <li class='active'><a href='logout.php'>Logout</a></li>
     </ul>
<br>
<br>
<center>
<form action="admin.php" method="post">
Search:<input type="text" name="param">
<input type="submit" name="search" value="submit">
</form>
<hr>
<table border="1">
<tr>
<th>User Name</th>
<th>Email</th>
<th>First Name</th>
<th>Last Name</th>
<th>User Level</th>
<th>EDIT</th>
</tr>
<form action="admin_edit.php" method="post">
<?php
    /* fetch value */
    while($stmt->fetch()){
        echo "<tr><td>$user</td><td>$email</td><td>$fname</td><td>$lname</td><td>$user_lvl</td><td><button name=\"edit\" value=\"".$user."\" type=\"submit\">edit</button></td></tr>";
    }
    ?>
</form>
</table>
</center>
</body>
</html>

<?php
        /* close statement */
        $stmt->close();
    }
	$mysqli->close();
}//end if
else
{
header("Location: login.php"); /* Redirect browser */
}
?>