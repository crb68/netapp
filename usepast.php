<?php
session_start();
error_reporting(E_ALL);
set_time_limit(0);
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
<center><h1><big>Use Stored Data</big></h1></center>
</div>
</div>
<div class="bdy">

</br></br>
<?php
    include 'paths.php';
    $un=$_SESSION['un'];
    global $program_files_path,$user_data_path;
if(isset($_SESSION['log']))
{
    if(isset($_POST['reload_old'])){
        //go to searchpage
        if (!file_exists("$user_data_path/$un/temp_results.txt")) {
            exec("$program_files_path/parse_2 -p $user_data_path/$un -d  $user_data_path/$un -t -1 -1 > /dev/null 2>&1 &");
            ?>
            <meta http-equiv="REFRESH" content="0;url=search2.php">
            <?php
                }else{
        
        $_SESSION['reload'] = "1";
        ?>
        <meta http-equiv="REFRESH" content="0;url=search2.php">
        <?php
            }
        //header("Location: search2.php");
    }else if($_POST['no_reload']){
        $un=$_SESSION['un'];
        if(isset($un) && is_dir("$user_data_path/$un")){
            system("rm -r $user_data_path/$un");
            //header("Location: data.php");
            ?>
            <meta http-equiv="REFRESH" content="0;url=data.php">
            <?php
        }else{
            echo "ERROR no previous data found";
            ?>
            <meta http-equiv="REFRESH" content="3;url=login.php">
            <?php
        }
    }
    ?>

<form action="usepast.php" method="post">
<center><h2>Use stored user data?</h2></center></br>
<center>Yes:<input type="checkbox" name="reload_old" value ="1"></center>
<center>No:<input type="checkbox" name="no_reload" value ="1"></center></br>
<center><input type="submit" name="sub"value="Submit"></center></br>
</form>
<?php
    }
    ?>
</div>
</body>

</html>