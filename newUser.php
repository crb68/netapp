
<html>
<?php
    if(isset($_POST['submit'])){
        $insert = 0;
        $id_taken = 0;
        $email_taken = 0;
        $invalid_email = 0;
        $invalid_un = 0;
        $invalid_pw = 0;
        
        $uname = $_POST['uname'];
        $pw = $_POST['pw'];
        $email = $_POST['email'];
        $fname = $_POST['fname'];
        $lname = $_POST['lname'];
        $usr_lvl = 0;
        
        
        if(!isset($uname) || $uname == ''){
            $invalid_un = 1;
        }
        if(!isset($pw) || $pw == ''){
            $invalid_pw = 1;
        }
        if(!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            $invalid_email = 1;
        }
        
        if(!$invalid_email && !$invalid_pw && !$invalid_un){
            require ("dbinfo.php");
            $mysqli = new mysqli("localhost", $db_user, $db_pass, $db_name);
            
            /* check connection */
            if (mysqli_connect_errno()) {
                printf("Connect failed: %s\n", mysqli_connect_error());
                exit();
            }
            
            $driver = new mysqli_driver();
            $driver->report_mode = MYSQLI_REPORT_ALL;
            
            
            //check if user name is already taken
            try {
                
                /* create a prepared statement */
                $stmt = $mysqli->prepare("SELECT count(username) FROM user WHERE username = ? ");
                
            } catch (mysqli_sql_exception $e) {
                
                echo $e->__toString();
            }
            if ($stmt) {
                /* bind parameters for markers */
                $stmt->bind_param('s', $uname);
                
                /* execute query */
                $stmt->execute();
                
                /* bind result variables */
                $stmt->bind_result($id_taken);
                
                /* fetch value */
                $stmt->fetch();
                
                /* close statement */
                $stmt->close();
            }
            
            
            if($id_taken == 0){
                //check if email has been used before
                try {
                    /* create a prepared statement */
                    $stmt = $mysqli->prepare("SELECT count(email) FROM user WHERE email = ? ");
                    
                } catch (mysqli_sql_exception $e) {
                    
                    echo $e->__toString();
                }
                if ($stmt) {
                    /* bind parameters for markers */
                    $stmt->bind_param('s', $email);
                    
                    /* execute query */
                    $stmt->execute();
                    
                    /* bind result variables */
                    $stmt->bind_result($email_taken);
                    
                    /* fetch value */
                    $stmt->fetch();

                    /* close statement */
                    $stmt->close();
                }
                if($email_taken == 0){
                    try {
                        /* create a prepared statement */
                        $stmt = $mysqli->prepare("INSERT INTO user VALUES (?, ?, ?, ?, ?,?)");
                        
                    } catch (mysqli_sql_exception $e) {
                        
                        echo $e->__toString();
                    }
                    if ($stmt) {
                        /* bind parameters for markers */
                        $stmt->bind_param('ssssss', $uname,$email,$fname,$lname,$pw,$usr_lvl);
                        
                        try {
                            /* execute query */
                            $stmt->execute();
                        }
                        catch (mysqli_sql_exception $e) {
                            echo $e->__toString();
                        }
                        $insert = 1;
                        /* close statement */
                        $stmt->close();
                    }
                    
                }
            }
            
            /* close connection */
            $mysqli->close();
        }
    }
    ?>
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
<center><h1><big>Create Account</big></h1></center>
</div>
</div>
<div class="bdy">

</br></br>
<?php
    if($insert == 1){
        echo "<center>Account creation succesful. All accounts must be approved by the Admin before use.<br>Redirecting...</center>";
        ?>
<meta http-equiv="REFRESH" content="3;url=login.php">
<?php
    }else{
        if($invalid_un){
            echo "<center>User Name is Invalid</center>";
        }elseif($invalid_pw){
            echo "<center>Password is Invalid</center>";
        }elseif($invalid_email){
            echo "<center>E-mail is not valid</center>";
        }elseif($id_taken == 1){
             echo "<center>User name is taken</center>";
        }elseif($email_taken == 1){
            echo "<center>E-mail is has already been used</center>";
        }
        
        ?>
<form action="newUser.php" method="post">
<center>UserName*:<input type="text" name="uname" maxlength="32"></center></br>
<center>PassCode*:&nbsp;&nbsp;<input type="password" name="pw" maxlength="32"></center></br>
<center>Email*:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" name="email" maxlength="35"></center></br>
<center>First Name:&nbsp;&nbsp;<input type="text" name="fname" maxlength="32"></center></br>
<center>Last Name:&nbsp;&nbsp;&nbsp;<input type="text" name="lname" maxlength="32"></center></br>
<center><input type="submit" name="submit"value="Submit"></center></br>
</form>
<?php
    }
    ?>
</div>

</body>

</html>