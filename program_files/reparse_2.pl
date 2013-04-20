#!/usr/bin/perl -w

#command line arguments:

#-t time_start time_end -k keywords list(space delimited) -and(optional) -path destination_path -file source -s sort_str -r(reverse sort)

#ex:

#-t 20130120000000 20130124000000 -k word -path /store_results_here_path -file /test_path/temp_results.txt -s 3 5 6

#The time is formated as "year month day hour min sec", -1 can be used in place of each time to ignore them.  For all times past the start time use -1 in place of time_end, all times before the end time used -1 in place of time_start and to ignore all times use -1 in place of both. Keywords are entered in one after another, if the keyword flag is not used it only searches for time. The and flag AND’s keywords together. Path flags specifies where where the output of the reparse.pl will be placed. The file flag specifies the location of the source file(output file from parse.cpp). The s flag is used to specify the sorting options:

#sort flags:

#1- file

#2- message

#3- time

#4- timezone

#5- error

#6 -debug

#7- frequency

#The -r flag is used to reverse the sort. Reparse.pl outputs a file named final_results.txt in the following format.

#file \t message \t time \t timezone \t error_flag \t debug_flag \t message_frequency

use Getopt::Long;

local $now = time;

@times = ();
@keywords = ();
$and = '';
@sortby = ();
$reverse_sort = '';
$source_file = ''; #location of temp results from parse.cpp
$results_path = ''; #location of where reparce results will be stored

GetOptions('t=s{2}' => \@times, 'k=s{0,30}' => \@keywords, 'and' => \$and, 'r' => \$reverse_sort, 's=s{1,7}' => \@sortby, 'file=s' => \$source_file,'path=s' => \$results_path);


$time_start = $times[0];
$time_end = $times[1];
$num_of_keywords = @keywords;
#prints out message hash
sub print_mes_hash{
    foreach $message(keys %message_hash){
        my $year = substr($message_hash{$message}{time},0,4);
        my $month = substr($message_hash{$message}{time},4,2);
        my $day = substr($message_hash{$message}{time},6,2);
        my $hr = substr($message_hash{$message}{time},8,2);
        my $min = substr($message_hash{$message}{time},10,2);
        my $sec = substr($message_hash{$message}{time},12,2);
        print OUT "$message_hash{$message}{file}\t$message\t$day/$month/$year $hr:$min:$sec\t$message_hash{$message}{zone}\t$message_hash{$message}{error}\t$message_hash{$message}{warning}\t$message_hash{$message}{count}\n";
    }
}
#adds message to message_hash
sub addtolist{
    if(defined $message_hash{$message}){
        $message_hash{$message}{'count'}++;
    }else{
        $message_hash{$message} = {
            'file' => $file_name,
            'time' => $time,
            'zone'=> $zone,
            'error'=> $error,
            'warning' => $warning,
            'count' => 1,
        };
    }#end else
}

open FILE , $source_file or die $!;
open OUT ,'>',"$results_path/unsorted_results.txt";
$prev_file = 'no file';
while(<FILE>){
    $line =$_;
    chomp $line;
    ($file_name, $message, $time, $zone,$error,$warning) = split("\t",$line);
    if(! defined $time || $time eq ''){
        $time = 0;
    }
    if($file_name ne '' && $file_name ne '/'){
        if($prev_file ne $file_name){
            #print old message hash to file;
            print_mes_hash();
            %message_hash = ();
        }
        #check timestamp
        if($time =~ m/^\d{14}$/){
        if(($time_start == -1 && $time_end == -1) || ($time_start != -1 && $time_end != - 1 && $time >= $time_start && $time <= $time_end) ||
            ($time_start != -1 && $time_end == -1 && $time >= $time_start) ||
            ($time_start == -1 && $time_end != -1 && $time <= $time_end)){
                #add keyword search
                
                if($num_of_keywords > 0){
                    $match = 0;
                    $keyword_count = 0;
                    #check if keyword is in message
                    foreach $keyword (@keywords){
                        if($message =~ m/$keyword/i){
                            $highlight = "<font color=\"green\">".$keyword."</font>";
                            $message =~ s/$keyword/$highlight/gi;
                            
                            if($and){
                                $keyword_count++;
                            }else{
                                $match = 1;
                            }
                        }#end if
                    }
                    if($match == 1){
                        addtolist();
                    }
                    
                    if($and && $keyword_count == $num_of_keywords){
                        addtolist();
                    }
                }else{
                    #no time specified
                    addtolist();
                }
            }#end if
        }
    }#end if
    $prev_file = $file_name;
}

close(FILE);
close(OUT);

$str = "";
foreach $element(@sortby){
    $str .= "-k".$element." ";
}
if($reverse_sort){
    $str .= "-r ";
}
#sort file by sorting string
system("sort -t'\t' $str $results_path/unsorted_results.txt > $results_path/final_results.txt");

open OUT ,'>',"$results_path/reparse_finished_flag.txt";
print OUT "1";
close(OUT);

$now = time - $now;
printf("\n\nTotal running time: %d\n\n", $now);