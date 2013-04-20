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
        if(isset($_POST['delete'])){
            try {
                /* create a prepared statement */
                $stmt = $mysqli->prepare("delete from user where username = ?");
                
            } catch (mysqli_sql_exception $e) {
                
                echo $e->__toString();
            }
            $old_un = $_POST['old'];
            
            try{
                $stmt->bind_param('s',$old_un);
            }catch (mysqli_sql_exception $e) {
                echo $e->__toString();
            }
            if ($stmt) {
                /* execute query */
                try{
                    $stmt->execute();
                }catch (mysqli_sql_exception $e) {
                    
                }
            }
            if(is_dir("$user_data_path/$old_un")){
                system("rm -r $user_data_path/$old_un");
            }
            header("Location: admin.php");
        }
        if(isset($_POST['save'])){
            $new_un = $_POST['new_user'];
            $new_em = $_POST['new_email'];
            $new_f = $_POST['new_fname'];
            $new_l = $_POST['new_lname'];
            $new_lvl = $_POST['new_lvl'];
            $new_pw = $_POST['new_pw'];
            
            $old_un = $_POST['old'];
            
            if($new_lvl <= 2 && $new_lvl >= 0){
                try {
                    /* create a prepared statement */
                    if(!isset($new_pw) || $new_pw != ''){
                        $stmt = $mysqli->prepare("UPDATE user set username = ?,email = ?, fname = ?,lname = ?,password = ?,user_level = ? where username = ?");
                    }else{
                        $stmt = $mysqli->prepare("UPDATE user set username = ?,email = ?, fname = ?,lname = ?,user_level = ? where username = ?");
                    }
                    
                } catch (mysqli_sql_exception $e) {
                    
                    echo $e->__toString();
                }
                
                try{
                    if(!isset($new_pw) || $new_pw != ''){
                        $stmt->bind_param('sssssis',$new_un,$new_em,$new_f,$new_l,$new_pw,$new_lvl,$old_un);
                    }else{
                        $stmt->bind_param('ssssis',$new_un,$new_em,$new_f,$new_l,$new_lvl,$old_un);
                    }
                }catch (mysqli_sql_exception $e) {
                    echo $e->__toString();
                }
                if ($stmt) {
                    /* execute query */
                    try{
                        $stmt->execute();
                    }catch (mysqli_sql_exception $e) {
                        
                    }
                }
            }
            header("Location: admin.php");
        }
        
        $user_to_edit = $_POST['edit'];
        
        try {
            /* create a prepared statement */
            $stmt = $mysqli->prepare("SELECT email,fname,lname,user_level FROM user where username = ?");
            
        } catch (mysqli_sql_exception $e) {
            
            echo $e->__toString();
        }
        /* bind parameters for markers */
        $stmt->bind_param('s',$user_to_edit);
        if ($stmt) {
            /* execute query */
            $stmt->execute();
            
            /* bind result variables */
            $stmt->bind_result($email,$fname,$lname,$user_lvl);
            
            $stmt->fetch()
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
<center><h1><big>Admin Edit User</big></h1></center>
</div>
</div>
<div class="bdy">
<ul>
<li class='active'><a href='logout.php'>Logout</a></li>
</ul>
<br>
<br>
<center>

<hr>
<table border="1">
<tr>
<th>User Name</th>
<th>Email</th>
<th>First Name</th>
<th>Last Name</th>
<th>Password</th>
<th>User Level</th>
</tr>
<form action="admin_edit.php" method="post">
<input type = "hidden" name = "old" value = "<?php echo $user_to_edit;?>">
<?php
    echo "<tr><td><input type=\"text\" name=\"new_user\" value = \"".$user_to_edit."\"></td><td><input type=\"text\" name=\"new_email\" value = \"".$email."\"></td><td><input type=\"text\" name=\"new_fname\" value = \"".$fname."\"></td><td><input type=\"text\" name=\"new_lname\" value = \"".$lname."\"></td><td><input type=\"password\" name=\"new_pw\" ></td><td><input type=\"text\" name=\"new_lvl\" value = \"".$user_lvl."\" min=\"0\" max=\"2\"></td></tr>";
    ?>
</table>
<input type="submit" name="delete" value="Remove User">
<input type="submit" name="save" value="Save Changes">
</form>
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