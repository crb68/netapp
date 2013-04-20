<?php
session_start();
include 'function.php';
$un=$_SESSION['un']; 
   
session_unset();
session_destroy();
//unlink("cache.txt");

header("Location: login.php"); /* Redirect browser */


?>