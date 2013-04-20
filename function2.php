<?php
    ini_set("memory_limit","900M");
    include 'paths.php';
    global $program_files_path,$user_data_path;
    $file=array();
    $dr=array();
    function getresults()
    {
        global $dr,$file,$un,$kw,$program_files_path,$user_data_path;
        
        $files=array();
        $dir=array();
        //$pattern1 = '/\b(error|Error|ERROR)\b/';
        //$pattern2 = '/\b(debug|Debug|DEBUG)\b/';
        $kw = str_replace(' ',',',$kw);
        echo "<br>Keyword = ".$kw."<br>";
        $handle = fopen("$user_data_path/$un/final_results.txt", "r");
        if ($handle) {
            echo "<table border=\"1\"><tr>";
            echo "<th>Time</th>";
            echo "<th>Location</th>";
            echo "<th>Message</th></tr>";
            
            while (($buffer = fgets($handle, 4096)) !== false) {
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
            if (!feof($handle)) {
                echo "Error: unexpected fgets() fail\n";
            }
            fclose($handle);
            echo "</table>";
            $dr=$dir;
            $file=$files;
            
        }//end handle
    }//end getresults
    
    function getdir()
    {
        global $dr;
        $result = array_unique($dr);
        $result=array_values($result);
        sort($result);
        $count=count($result);
        
        for($i=0;$i<$count;$i++)
        {
            echo "<input type=\"checkbox\" name=\"".$result[$i]."\"value=\"1\">".$result[$i]."</br>";
        }
        
        echo "</br><center><input type=\"submit\" name=\"sub3\"></center>";
        
        
    }//end getdir
    
    function getfil()
    {
        global $file;
        $result = array_unique($file);
        $result=array_values($result);
        sort($result);
        $count=count($result);
        
        for($i=0;$i<$count;$i++)
        {
            echo "<input type=\"checkbox\" name=\"".$result[$i]."\"value=\"1\">".$result[$i]."</br>"; 
        }
        
        echo "</br><center><input type=\"submit\" name=\"sub3\"></center>";
        
        
    }//end getdir
    ?>