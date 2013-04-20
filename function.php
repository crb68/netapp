<?php
error_reporting(E_ALL);
$login_result;
$conn_id;

    
//takes username and pw returns a 1 for valid, 0 for invalid  
function login($un, $upw){
    require ("dbinfo.php");
    
    $mysqli = new mysqli("localhost", $db_user, $db_pass, $db_name);
    
    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
    
    $driver = new mysqli_driver();
    $driver->report_mode = MYSQLI_REPORT_ALL;
    
    // check if valid user name
    try {
        
        /* create a prepared statement */
        $stmt = $mysqli->prepare("SELECT username FROM user WHERE username = ? ");
        
    } catch (mysqli_sql_exception $e) {
        
        echo $e->__toString();
    }
    if ($stmt) {
        /* bind parameters for markers */
        $stmt->bind_param('s', $un);
        
        /* execute query */
        $stmt->execute();
        
        /* bind result variables */
        $stmt->bind_result($user_id);
        
        /* fetch value */
        $stmt->fetch();
        
        if($user_id == NULL){
            $stmt->close();
            $mysqli->close();
            return 2;
        }
        
        /* close statement */
        $stmt->close();
    }

    
    //validate password
    try {
        
        /* create a prepared statement */
        $stmt = $mysqli->prepare("SELECT password FROM user WHERE username = ? ");
        
    } catch (mysqli_sql_exception $e) {
        
        echo $e->__toString();
    }
    if ($stmt) {
        /* bind parameters for markers */
        $stmt->bind_param('s', $un);
        
        /* execute query */
        $stmt->execute();
        
        /* bind result variables */
        $stmt->bind_result($user_pass);
        
        /* fetch value */
        $stmt->fetch();
        
        if($user_pass !== $upw){
            $stmt->close();
            $mysqli->close();
            return 3;
        }
        
        /* close statement */
        $stmt->close();
    }

    
    // validate user level
    try {
    
    /* create a prepared statement */
    $stmt = $mysqli->prepare("SELECT user_level FROM user WHERE username = ?");
        
    } catch (mysqli_sql_exception $e) {
        
        echo $e->__toString();
    }
    if ($stmt) {
        /* bind parameters for markers */
        $stmt->bind_param('s', $un);
        
        /* execute query */
        $stmt->execute();
        
        /* bind result variables */
        $stmt->bind_result($user_lvl);
        
        /* fetch value */
        $stmt->fetch();
        
        /* close statement */
        $stmt->close();
    }
    
    /* close connection */
    $mysqli->close();
    if($user_lvl == 1){
        return 1;
    }elseif($user_lvl == 2){
        return 5;
    }else{
        return 4;
    }
    
}//end login

    function deleteDir($dirPath) {
    if (! is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}
