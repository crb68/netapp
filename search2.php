<?php
    session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 'on');
    set_time_limit(0);
    include 'function2.php';
    include 'paths.php';
    global $program_files_path,$user_data_path;
    $flag=0;
    $no_wait_flag = 0;
    $limited_results_flag = 0;
    if(isset($_SESSION['log']))
    {
        $un=$_SESSION['un'];
        
        if(isset($_POST['sub3'])){
            //read results and output only ones with files
            $flag = 1;
            $no_wait_flag = 1;
            $limited_results_flag = 1;
        }
        if(isset($_SESSION['reload']) && $_SESSION['reload'] == "1"){
            $flag = 4;
            $_SESSION['reload'] = "0";
        }
        if(isset($_POST['subsrch']))
        {
            if(isset($_POST['resort']) && $_POST['resort'] == "1"){
                
                $str = "-k".$_POST['sortby1']." ";
                if($_POST['sortby2'] != ""){
                    $str .= "-k".$_POST['sortby2']." ";
                }
                if($_POST['sortby3'] != ""){
                    $str .= "-k".$_POST['sortby3']." ";
                }
                if(isset($_POST['reverse']) && $_POST['reverse'] == "1"){
                    $str .= "-r ";
                }
                system("sort -t'\t' $str $user_data_path/$un/unsorted_results.txt > $user_data_path/$un/final_results.txt");
                $flag=1;
                $no_wait_flag = 1;
            }else{
                if(isset($_POST['time1']) && $_POST['time1'] !== ''){
                    $time1 = $_POST['time1'];
                    $st = $time1{6}.$time1{7}.$time1{8}.$time1{9}.$time1{3}.$time1{4}.$time1{0}.$time1{1}.$time1{11}.$time1{12}.$time1{14}.$time1{15}.$time1{17}.$time1{18};
                }else{
                    $st = -1;
                }
                
                if(!isset($_POST['time2']) || $_POST['time2'] !== ''){
                    $time2 = $_POST['time2'];
                    $et = $time2{6}.$time2{7}.$time2{8}.$time2{9}.$time2{3}.$time2{4}.$time2{0}.$time2{1}.$time2{11}.$time2{12}.$time2{14}.$time2{15}.$time2{17}.$time2{18};
                }else{
                    $et = -1;
                }
                
                $kw=$_POST['kw'];
                
                if($kw != '')
                {
                    $kw = trim($kw);
                    $kw = preg_replace('!\s+!', ' ', $kw);
                    $kw = '"'.$kw.'"';
                    $kw = str_replace(' ','" "',$kw);
                    
                    $sortstr = $_POST['sortby1']." ".$_POST['sortby2']." ".$_POST['sortby3'];
                    if(isset($_POST['reverse']) && $_POST['reverse'] == "1"){
                        $sortstr .= "-r ";
                    }
                    if(isset($_POST['and']) && $_POST['and'] == "1"){
                        $sortstr .= "-and ";
                    }
                    
                    exec("$program_files_path/reparse_2.pl -t $st $et -s $sortstr -k $kw -path $user_data_path/$un -file $user_data_path/$un/temp_results.txt > /dev/null 2>/dev/null &");
                    $flag=1;
                }
                else
                {
                    $sortstr = $_POST['sortby1']." ".$_POST['sortby2']." ".$_POST['sortby3'];
                    if(isset($_POST['reverse']) && $_POST['reverse'] == "1"){
                        $sortstr .= "-r ";
                    }
                    
                    exec("$program_files_path/reparse_2.pl -t $st $et -s $sortstr -path $user_data_path/$un -file $user_data_path/$un/temp_results.txt > /dev/null 2>/dev/null &");
                    $flag=1;
                }
            }
            
        }
        ?>

<html>
<head>

<script type="text/javascript">

var int=self.setInterval(function(){UrlExists()},2000);

function UrlExists()
{
    
    document.getElementById("subsrch").disabled = true;
    
    var http = new XMLHttpRequest();
    <?php $cache_str = "$user_data_path/$un/cache.txt"?>
    http.open('GET', "<?php echo $cache_str;?>", false);
    http.send();
    //document.write(http.status);
    if(http.status!=404)
    {
        document.getElementById("subsrch").disabled = false;
        int=window.clearInterval(int);
    }
    
    //<?php unlink("$user_data_path/$un/cache.txt");?>
}
</script>

<link rel="stylesheet" type="text/css" href="main.css" />
<link href='http://fonts.googleapis.com/css?family=Skranji' rel='stylesheet' type='text/css'>
</head>

<body onload="UrlExists()">
<div class="bdy2">
<div class="bigtitle">
<center><h1>Search Results </h1></center>
</div>
<div class="bigtcol">
<ul>
<li class='active'><a href='logout.php'>Logout</a></li>
</ul></br></br>
<form action="search2.php" method="post">


Start Time:&nbsp;<br>
<link href="http://netdna.bootstrapcdn.com/twitter-bootstrap/2.2.2/css/bootstrap-combined.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" media="screen"
href="http://tarruda.github.com/bootstrap-datetimepicker/assets/css/bootstrap-datetimepicker.min.css">


<div id="datetimepicker" class="input-append date">
<input type="text" name = "time1"></input>
<span class="add-on">
<i data-time-icon="icon-time" data-date-icon="icon-calendar"></i>
</span>
</div>
<script type="text/javascript"
src="http://cdnjs.cloudflare.com/ajax/libs/jquery/1.8.3/jquery.min.js">
</script>
<script type="text/javascript"
src="http://netdna.bootstrapcdn.com/twitter-bootstrap/2.2.2/js/bootstrap.min.js">
</script>
<script type="text/javascript"
src="http://tarruda.github.com/bootstrap-datetimepicker/assets/js/bootstrap-datetimepicker.min.js">
</script>
<script type="text/javascript"
src="http://tarruda.github.com/bootstrap-datetimepicker/assets/js/bootstrap-datetimepicker.pt-BR.js">
</script>
<script type="text/javascript">
$('#datetimepicker').datetimepicker({
                                    format: 'dd/MM/yyyy hh:mm:ss',language: 'en'});
</script>

End Time:&nbsp;&nbsp;<br>
<div id="datetimepicker2" class="input-append date">
<input type="text" name = "time2"></input>
<span class="add-on">
<i data-time-icon="icon-time" data-date-icon="icon-calendar"></i>
</span>
</div>
<script type="text/javascript"
src="http://cdnjs.cloudflare.com/ajax/libs/jquery/1.8.3/jquery.min.js">
</script>
<script type="text/javascript"
src="http://netdna.bootstrapcdn.com/twitter-bootstrap/2.2.2/js/bootstrap.min.js">
</script>
<script type="text/javascript"
src="http://tarruda.github.com/bootstrap-datetimepicker/assets/js/bootstrap-datetimepicker.min.js">
</script>
<script type="text/javascript"
src="http://tarruda.github.com/bootstrap-datetimepicker/assets/js/bootstrap-datetimepicker.pt-BR.js">
</script>
<script type="text/javascript">
$('#datetimepicker2').datetimepicker({
                                     format: 'dd/MM/yyyy hh:mm:ss',language: 'en'});
</script>
KeyWords: <input type="text" name="kw"></br>And Keywords: <input type = "checkbox" name = "and" value = "1"><br>
Sort by:
<select name="sortby1">
<option value= "3">Time</option>
<option value= "5">Error/Fail</option>
<option value= "6">Warn/Debug</option>
<option value= "1">Path</option>
<option value= "2">Message</option>
<option value= "7 -n">Frequency</option>
</select>
<select name="sortby2">
<option value= ""></option>
<option value= "3">Time</option>
<option value= "5">Error/Fail</option>
<option value= "6">Warn/Debug</option>
<option value= "1">Path</option>
<option value= "2">Message</option>
<option value= "7 -n">Frequency</option>
</select>
<select name="sortby3">
<option value= ""></option>
<option value= "3">Time</option>
<option value= "5">Error/Fail</option>
<option value= "6">Warn/Debug</option>
<option value= "1">Path</option>
<option value= "2">Message</option>
<option value= "7 -n">Frequency</option>
</select>
Reverse Sort: <input type="checkbox" name = "reverse" value = "1"><br>
Resort Previous Results: <input type="checkbox" name = "resort" value = "1"><br>
Search: <input type="submit" name="subsrch" id="subsrch">
</br>
<hr>
</form>
</div>
<div class="dir2" id="d2">
<center>Directories</center></br>
</div>

<div class="fil2" id="f2">
<center>Files</center></br>
</div>

<div class="bigtable">
<div class="bigtable2">

<?php
    if($flag == 4){
        //do nothing just load page
    }else if($flag==1)
    {
        ?>
<!-- Progress information -->
<div id="information" style="width"></div>
<?php
    if(!$no_wait_flag){
        //-------------------------
        $start = microtime(true);
        // Loop through process
        $content = @file_get_contents("$user_data_path/$un/reparse_finished_flag.txt");
        while($content == False){
            
            // Javascript for updating information
            $elapsed_time = round((microtime(true) - $start));
            $elapsed = "Searching";
            if($elapsed_time % 3 == 0){
                $elapsed .= ".";
            }else if($elapsed_time % 3 == 1){
                $elapsed .= "..";
            }else{
                $elapsed .= "...";
            }
            $elapsed .= "<br> Elapsed Time: ".$elapsed_time." seconds";
            echo '<script language="javascript">
            document.getElementById("information").innerHTML="'.$elapsed.'";
            </script>';
            
            // This is for the buffer achieve the minimum size in order to flush data
            echo str_repeat(' ',1024*64);
            
            // Send output to browser immediately
            flush();
            
            // Sleep one second so we can see the delay
            sleep(1);
            $content = @file_get_contents("$user_data_path/$un/reparse_finished_flag.txt");
        }
        
        // Tell user that the process is completed
        $elapsed_time = (microtime(true) - $start);
        echo '<script language="javascript">document.getElementById("information").innerHTML="Search completed"</script>';
        echo "Total Elapsed Time: $elapsed_time seconds";
        // removes reparse_finished_flag.txt, if left messes up next run
        exec("rm $user_data_path/$un/reparse_finished_flag.txt");
        
        //--------------------------
    }
    if($limited_results_flag){
        $keys = array_keys($_POST);
        //-------------------------------------------------
        global $dr,$file;
        $files=array();
        $dir=array();
        $handle = fopen("$user_data_path/$un/final_results.txt", "r");
        if ($handle) {
            
            echo "<table border=\"1\"><tr>";
            echo "<th>Time</th>";
            echo "<th>Location</th>";
            echo "<th>Message</th></tr>";
            
            while (($buffer = fgets($handle, 4096)) !== false) {
                
                $match = 0;
                $string = $buffer;
                $str=explode("\t",$string);
                
                array_push($files,$str[0]);
                $pos = strrpos($str[0],"/");
                $str3 = substr($str[0],0,$pos);
                array_push($dir,$str3);
                
                $time=$str[2];
                $location=$str[0];
                $message=$str[1];
                $freq = $str[6];
                
                for($i = 0; $i < count($keys)-1; $i++){
                    $keys[$i] = str_replace('_','.',$keys[$i]);
                    $str_pos = strpos($location, $keys[$i]);
                    if($str_pos !== False){
                        $match = 1;
                        break;
                    }
                }
                
                if($match){
                    if($str[4]==1)
                    {
                        if($str[5]==1)
                        {
                            echo "<tr><td>".$time."</td>";
                            echo "<td>".$location."<br><br>[Message Frequency: ".$freq."]</td>";
                            echo "<td>".$message."</td></tr>";
                            
                        }
                        else
                        {
                            echo "<tr><td><font color=\"blue\">".$time."</font></td>";
                            echo "<td><font color=\"blue\">".$location."<br><br>[Message Frequency: ".$freq."]</font></td>";
                            echo "<td><font color=\"blue\">".$message."</font></td></tr>";
                        }
                    }
                    else
                    {
                        echo "<tr><td><font color=\"red\">".$time."</font></td>";
                        echo "<td><font color=\"red\">".$location."<br><br>[Message Frequency: ".$freq."]</font></td>";
                        echo "<td><font color=\"red\">".$message."</font></td></tr>";
                        
                    }
                    
                }
            }
            
            
        }else{
            echo "final results open fail";
        }
        
        if (!feof($handle)) {
            echo "Error: unexpected fgets() fail\n";
        }
        
        fclose($handle);
        echo "</table>";
        $dr=$dir;
        $file=$files;
        
        //-------------------------------------------------
    }else{
        getresults();
    }
    $flag=2;
    }else{
        echo "Processing Files<br>";
        ?>
<!-- Progress bar holder -->
<div id="progress" style="width:500px;border:1px solid #ccc;"></div>
<!-- Progress information -->
<div id="information" style="width"></div>
<?php
    $start = microtime(true);
    $content = @file_get_contents("$user_data_path/$un/flag_file.txt");
    while($content == False){
        $content = @file_get_contents("$user_data_path/$un/flag_file.txt");
        //wait till file appears
    };
    //------------------------------------------------
    $percent = 0;
    // Loop through process
    while($percent < 100){
        // Calculate the percentation
        $percent = (file_get_contents("$user_data_path/$un/flag_file.txt"))."%";
        if(isset($percent) && $percent != ''){
            // Javascript for updating the progress bar and information
            $elapsed_time = round((microtime(true) - $start));
            $percent_and_elapsed = $percent." Elapsed Time: ".$elapsed_time." seconds";
            echo '<script language="javascript">
            document.getElementById("progress").innerHTML="<div style=\"width:'.$percent.';background-color:#6495ed;\">&nbsp;</div>";
            document.getElementById("information").innerHTML="'.$percent_and_elapsed.'";
            </script>';
            
            // This is for the buffer achieve the minimum size in order to flush data
            echo str_repeat(' ',1024*64);
            
            // Send output to browser immediately
            flush();
            
            // Sleep one second so we can see the delay
            sleep(1);
        }
    }
    
    // Tell user that the process is completed
    $elapsed_time = round((microtime(true) - $start));
    echo '<script language="javascript">document.getElementById("information").innerHTML="Process completed"</script>';
    echo "Total Elapsed Time: $elapsed_time seconds";
    // removes flag file, if left messes up next run
    exec("rm $user_data_path/$un/flag_file.txt");
    //---------------------------------------------------------
    
    }// end flag
    ?>
</div>
</div>
</div>


<div class="dir" id="d1">
<center>Directories</center></br>
<form action="search2.php" method="post">
<?php
    if($flag==2)
    {
        getdir();
        $flag=3;
    }
    ?>
</form>
</div>
<div class="fil" id="f1">
<center>Files</center></br>
<form action="search2.php" method="post">
<?php
    if($flag==3)
    {
        getfil();
    }
    ?>
</form>
</div>

<script type="text/javascript">
document.getElementById("d2").style.display = 'none';
document.getElementById("f2").style.display = 'none';
document.getElementById("d1").style.display = 'inline';
document.getElementById("f1").style.display = 'inline';
</script>

</body>

</html>

<?php
    
    }//end if
    else
    {
        header("Location: login.php"); /* Redirect browser */
    }
    ?>