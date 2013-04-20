#include <string.h>
#include <iostream>
#include <dirent.h>
#include <sys/types.h>
#include <cstdlib>
#include <sys/ipc.h>
#include <unistd.h>
#include <errno.h>
#include <fcntl.h>
#include <sys/stat.h>
#include <sys/times.h>
#include <pthread.h>
#include <time.h>
#include <cstdio>
//#include <sys/wait.h>

#define NUM_THREADS 30
#define NUM_WRITERS 15

/*
 command line arguments:
 
 -p path -d destination_path -t time_start time_end
 
 ex:
 
 -p stealth-logs -d /test_path/temp_results.txt -t 20130120000000 20130124000000
 
 The time is formated as "year month day hour min sec", -1 can be used in place of each time to ignore them.  For all times past the start time use -1 in place of time_end, all times before the end time used -1 in place of time_start and to ignore all times use -1 in place of both.
 
 The results are outputted to to the file specified by the destination_path. The file if it exist will be overwritten, if not it is created, in the example they would be outputted to /test_path/temp_results.txt. Results are outputted as 1 logfile message per line with tab as a string delimiter to break the message into columns. Each line is formatted in the following:
 
 PATH \t MESSAGE \t time \t ERROR_FLAG \t WARNING_FLAG
 
 
 
 compile with -pthread on unix
 compile with -lpthread on linux, and uncomment #include <sys.wait.h>, run as root user
 
 
 */

typedef struct timestamp{
    char time[15];
    char zone[7];
}timestamp;

//message struct, holds parsed message data
typedef struct mes{
    char message[1000];
    char file_name[128];
    char path[256];
    char error;
    char warning;
    timestamp stamp;
} message;

// shared data among threads
typedef struct shared{
    long time_start;
    long time_end;
}shared;

// write thread argument
typedef struct write_data{
    int pos;
    message *list;
}write_data;

//parser threads argument
typedef struct thread_data{
    char file_name[256];
    char path[256];
}thread_data;

//thread data for parser threads
pthread_t threads[NUM_THREADS];
thread_data td[NUM_THREADS];
int thread_data_pos;

//thread data for writing threads
pthread_t writer_threads[NUM_WRITERS];
write_data wtd[NUM_WRITERS];
int writer_data_pos;
pthread_mutex_t writer_mutex;

//global variables
int common_path_len;
char output_file[256];
int total_number_of_files;
int num_of_processed_files;
FILE *flag_file;
char flag_file_path[256];

shared mem;
pthread_mutex_t mutex;
void *status;
char months[12][4];
int current_year;

//long total_messages;

// writes the messages passed to it to the output file
void *write_to_file(void *arg);
// parses ems log files
void *ems_thread(void *threadarg);
// parses message log files
void *message_thread(void *threadarg);
// general parsing thread
void *parser_thread(void *threadarg);
//parses backup log files
void *backup_thread(void *threadarg);
//recursively goes through given file path and spawns a thread for each log-file it encounters
int spwan_parsers(char *path);
//counts number of files in path, used to update a flag file which contains % of process completed
int count_of_files(char *path);

//main -----------------------------------------------------------------------------
int main(int argc, char* argv[]){
    
    double t1, t2;
    struct tms tb1, tb2;
    double ticspersec;
    ticspersec = (double) sysconf(_SC_CLK_TCK);
    t1 = (double) times(&tb1);
    
    time_t theTime = time(NULL);
    struct tm *aTime = localtime(&theTime);
    
    //total_messages = 0;
    
    current_year = aTime->tm_year + 1900;
    int waiting;
    char* path;
    char* time_start;
    char* time_end;
    char* output_path;
    
    char signal_file[256];
    pthread_t thread_ID;
    void *exit_status;
    //reads in commadline arguments
    int i;
    for(i = 1; i < argc-1; i++){
        if(strcmp("-p", argv[i]) == 0){
            path = argv[i+1];
            common_path_len = strlen(path);
        }
        if(strcmp("-d", argv[i]) == 0){
            output_path = argv[i+1];
            strcpy(output_file,output_path);
            strcat(output_file,"/temp_results.txt");
        }
        if(strcmp("-t", argv[i]) == 0){
            mem.time_start = strtol(argv[i+1],NULL,0);
            mem.time_end = strtol(argv[i+2],NULL,0);
        }
    }//end for
    
    //array with months, used to conert timestamps
    strcpy(months[0],"Jan");
    strcpy(months[1],"Feb");
    strcpy(months[2],"Mar");
    strcpy(months[3],"Apr");
    strcpy(months[4],"May");
    strcpy(months[5],"June");
    strcpy(months[6],"July");
    strcpy(months[7],"Aug");
    strcpy(months[8],"Sept");
    strcpy(months[9],"Oct");
    strcpy(months[10],"Nov");
    strcpy(months[11],"Dec");
    
    pthread_mutex_init(&mutex, NULL);
    pthread_mutex_init(&writer_mutex, NULL);
    
    //creates new file/erases old file
    FILE *f;
    if((f = fopen(output_file, "w")) == NULL){
        printf("File not created\n");
        return 0;
    }
    fclose(f);
    
    //creates flag file for percent complete
    strcpy(flag_file_path,output_path);
    strcat(flag_file_path,"/flag_file.txt");
    if((flag_file = fopen(flag_file_path, "w")) == NULL){
        printf("File not created\n");
        return 0;
    }
    fclose(flag_file);
    
    //spwan parser programs
    thread_data_pos = 0;
    writer_data_pos = 0;
    count_of_files(path);
    //printf("%d\n",total_number_of_files);
    spwan_parsers(path);
    
    //wait for all threads
    for(i = 0; i < thread_data_pos; i++){
        if(pthread_join(threads[i],&exit_status)){
            perror("pthread_join main");
        }
    }
    for(i = 0; i < writer_data_pos; i++){
        if(pthread_join(writer_threads[i],&exit_status)){
            perror("pthread_join main");
        }
    }
    
    if((flag_file = fopen(flag_file_path, "w")) == NULL){
        printf("File not created\n");
        return 0;
    }
    fprintf(flag_file,"%d", (int)(100*((double)num_of_processed_files/(double)total_number_of_files)));
    //printf("Percent Compelete %f\n", 100*((double)num_of_processed_files/(double)total_number_of_files));
    fclose(flag_file);
    pthread_mutex_destroy(&mutex);
    pthread_mutex_destroy(&writer_mutex);
    
    FILE *sigF;
    strcpy(signal_file,output_path);
    strcat(signal_file,"/cache.txt");
    if((sigF = fopen(signal_file, "w")) == NULL){
        printf("File not created\n");
        return 0;
    }
    fprintf(sigF,"%d", 1);
    fclose(sigF);
    
    printf("---ENDofParser---\n");
    t2 = (double) times(&tb2);
    printf("Time elapsed: %f\n",(t2 - t1) / ticspersec);
    printf("------------------\n");
    return 0;
}
//-----------------------------------------------------------------------------------------------------------
void *backup_thread(void *threadarg){
    thread_data *my_data;
    my_data = (thread_data *)threadarg;
    pthread_t tid;
    void *exit_status;
    char *file_name = my_data->file_name;
    char *path = my_data->path;
    char absolute[500];
    strcpy(absolute,path);
    strcat(absolute,"/");
    strcat(absolute,file_name);
    
    //read file in to main memory
    int retval;
    int file_size;
    FILE *f;
    if((f = fopen(absolute, "r")) == NULL){
        printf("File not opened err\n");
        return NULL;
    }
    fseek (f , 0 , SEEK_END);
    file_size = ftell(f);
    rewind(f);
    char *buffer = (char *)malloc(sizeof(char)*file_size);
    retval = fread (buffer,sizeof(char),file_size,f);
    if (retval != file_size) {
        perror("file parse read");
        pthread_exit(NULL);
    }
    fclose(f);
    
    int status;
    int i;
    int j;
    int k;
    
    int pos = 0;
    int list_size = 1000;
    message *list = (message*)malloc(sizeof(message)*list_size);
    
    char message[1500];
    char line[1500];
    
    char timestamp[15];
    char zone[7];
    char buff[5];
    
    long time;
    
    char error;
    char warning;
    
    //read through file, parse line by line
    int file_pos = 0;
    while(file_pos < file_size){
        i = 0;
        while(buffer[file_pos] != '\n' && i < 1499){
            line[i++] = buffer[file_pos++];
        }
        file_pos++;
        line[i] = '\n';
        line[i+1] = '\0';
        
        i = 0;
        if(line[0] = 'd'){
            //printf("%s %d\n",absolute,thread_data_pos);
            
            while(line[i] != '/'){
                i++;
            }
            snprintf(buff,5,"%d",current_year);
            //get year
            timestamp[0] = buff[0];
            timestamp[1] = buff[1];
            timestamp[2] = buff[2];
            timestamp[3] = buff[3];
            
            buff[0] = line[8];
            buff[1] = line[9];
            buff[2] = line[10];
            buff[3] = '\0';
            
            for(k = 0; k < 12 ; k++){
                if(strcmp(months[k],buff) == 0){
                    snprintf(buff,4,"%02d",k+1);
                    break;
                }
            }
            //month
            timestamp[4] = buff[0];
            timestamp[5] = buff[1];
            //day
            if(line[12] == ' '){
                timestamp[6] = '0';
            }else{
                timestamp[6] = line[12];
            }
            timestamp[7] = line[13];
            //hour
            timestamp[8] = line[15];
            timestamp[9] = line[16];
            //min
            timestamp[10] = line[18];
            timestamp[11] = line[19];
            //sec
            timestamp[12] = line[21];
            timestamp[13] = line[22];
            timestamp[14] = '\0';
            
            zone[0] = line[24];
            zone[1] = line[25];
            zone[2] = line[26];
            zone[3] = '\0';
            
        }else{
            for(k = 0; k < 14 ; k++){
                timestamp[k] = '0';
            }
            timestamp[k] = '\0';
            zone[0] = '\0';
        }
        time = strtol(timestamp,NULL,0);

            //check if in range specified by command line arguments
        if((mem.time_start == -1 && mem.time_end == -1) ||
           (mem.time_start != -1 && mem.time_end == -1 && (time - mem.time_start >= 0)) ||
           (mem.time_start == -1 && mem.time_end != -1 && (mem.time_end - time >= 0)) ||
           (mem.time_start != -1 && mem.time_end != -1 && (time - mem.time_start >= 0) && (mem.time_end - time >= 0))){
            
            j = 0;
            while(line[i] != '\n' && j < 998 && i < 1499){  // message length limit
                message[j] = line[i];
                i++;
                j++;
            }
            message[j] = '\0';
            
            error = '1';
            warning = '1';
            //flag messages
            if(strcasestr(message,"fail")){
                error = '0';
            }
            else if((strcasestr(message,"error") || strcasestr(message," err"))){
                error = '0';
            }
            else if(strcasestr(message," not ") || strcasestr(message,"doesn't")){
                error = '0';
            }
            if(error == '1'){
                if(strcasestr(message,"warning")){
                    warning = '0';
                }
                else if(strcasestr(message,"debug")){
                    warning = '0';
                }
            }
            //copy to message list, if not enough space double size
            if(pos == list_size){
                list_size = 2*list_size;
                list = (struct mes*)realloc(list, sizeof(message)*list_size);
            }
            strcpy(list[pos].message,message);
            strcpy(list[pos].path,path);
            strcpy(list[pos].file_name,file_name);
            strcpy(list[pos].stamp.time,timestamp);
            strcpy(list[pos].stamp.zone,zone);
            list[pos].error = error;
            list[pos].warning = warning;
            pos++;
        }
    }//end while
    free(buffer);
    //pass data to writer
    pthread_mutex_lock(&mutex);     //lock if max writers is met
    if(writer_data_pos == NUM_WRITERS){
        for(i = 0; i < NUM_WRITERS; i++){
            if(pthread_join(writer_threads[i],&exit_status)){
                perror("pthread_join parser thread");
            }
        }
        writer_data_pos = 0;
    }
    if(writer_data_pos < NUM_WRITERS){
        wtd[writer_data_pos].pos = pos;
        wtd[writer_data_pos].list = list;
        if(pthread_create(&writer_threads[writer_data_pos], NULL, write_to_file, &wtd[writer_data_pos])){
            perror("pthread create");
        }
        writer_data_pos++;
    }
    pthread_mutex_unlock(&mutex);      //unlock
    return NULL;
}
//-----------------------------------------------------------------------------------------------------------
void *sis_log_thread(void *threadarg){
    thread_data *my_data;
    my_data = (thread_data *)threadarg;
    pthread_t tid;
    void *exit_status;
    char *file_name = my_data->file_name;
    char *path = my_data->path;
    char absolute[500];
    strcpy(absolute,path);
    strcat(absolute,"/");
    strcat(absolute,file_name);

        //read file into main memory
    int retval;
    int file_size;
    FILE *f;
    if((f = fopen(absolute, "r")) == NULL){
        printf("File not opened err\n");
        return NULL;
    }
    fseek (f , 0 , SEEK_END);
    file_size = ftell(f);
    rewind(f);
    char *buffer = (char *)malloc(sizeof(char)*file_size);
    retval = fread (buffer,sizeof(char),file_size,f);
    if (retval != file_size) {
        perror("file parse read");
        pthread_exit(NULL);
    }
    fclose(f);
    
    int status;
    int i;
    int j;
    int k;
    
    int pos = 0;
    int list_size = 1000;
    message *list = (message*)malloc(sizeof(message)*list_size);
    
    char message[1500];
    char line[1500];
    
    char timestamp[15];
    char zone[7];
    char buff[5];
    
    long time;
    
    char error;
    char warning;
    
    //read data line by line
    int file_pos = 0;
    while(file_pos < file_size){
        i = 0;
        while(buffer[file_pos] != '\n' && i < 1499){
            line[i++] = buffer[file_pos++];
        }
        file_pos++;
        line[i] = '\n';
        line[i+1] = '\0';
        
        i = 0;
        if(strchr(line,'[')){

            
            while(line[i] != '['){
                i++;
            }
            snprintf(buff,5,"%d",current_year);
            //get year
            timestamp[0] = line[24];
            timestamp[1] = line[25];
            timestamp[2] = line[26];
            timestamp[3] = line[27];
            
            buff[0] = line[4];
            buff[1] = line[5];
            buff[2] = line[6];
            buff[3] = '\0';
            
            for(k = 0; k < 12 ; k++){
                if(strcmp(months[k],buff) == 0){
                    snprintf(buff,4,"%02d",k+1);
                    break;
                }
            }
            //month
            timestamp[4] = buff[0];
            timestamp[5] = buff[1];
            //day
            if(line[8] == ' '){
                timestamp[6] = '0';
            }else{
                timestamp[6] = line[8];
            }
            timestamp[7] = line[9];
            //hour
            timestamp[8] = line[11];
            timestamp[9] = line[12];
            //min
            timestamp[10] = line[14];
            timestamp[11] = line[15];
            //sec
            timestamp[12] = line[17];
            timestamp[13] = line[18];
            timestamp[14] = '\0';
            
            zone[0] = line[20];
            zone[1] = line[21];
            zone[2] = line[22];
            zone[3] = '\0';
            
        }else{
            for(k = 0; k < 14 ; k++){
                timestamp[k] = '0';
            }
            timestamp[k] = '\0';
            zone[0] = '\0';
        }
        time = strtol(timestamp,NULL,0);
        
        //check if in range specified by command line arguments
        if((mem.time_start == -1 && mem.time_end == -1) ||
           (mem.time_start != -1 && mem.time_end == -1 && (time - mem.time_start >= 0)) ||
           (mem.time_start == -1 && mem.time_end != -1 && (mem.time_end - time >= 0)) ||
           (mem.time_start != -1 && mem.time_end != -1 && (time - mem.time_start >= 0) && (mem.time_end - time >= 0))){
            
            j = 0;
            while(line[i] != '\n' && j < 998 && i < 1499){  // message length limit
                message[j] = line[i];
                i++;
                j++;
            }
            message[j] = '\0';
            
            //flag messages
            error = '1';
            warning = '1';
            
            if(strcasestr(message,"fail")){
                error = '0';
            }
            else if((strcasestr(message,"error") || strcasestr(message," err"))){
                error = '0';
            }
            else if(strcasestr(message," not ") || strcasestr(message,"doesn't")){
                error = '0';
            }
            if(error == '1'){
                if(strcasestr(message,"warning")){
                    warning = '0';
                }
                else if(strcasestr(message,"debug")){
                    warning = '0';
                }
            }
            //copy to message list, if not enough space double size
            if(pos == list_size){
                list_size = 2*list_size;
                list = (struct mes*)realloc(list, sizeof(message)*list_size);
            }
            strcpy(list[pos].message,message);
            strcpy(list[pos].path,path);
            strcpy(list[pos].file_name,file_name);
            strcpy(list[pos].stamp.time,timestamp);
            strcpy(list[pos].stamp.zone,zone);
            list[pos].error = error;
            list[pos].warning = warning;
            pos++;
        }
    }//end while
    free(buffer);
    //spwan writing thread pass data
    pthread_mutex_lock(&mutex);     //lock if max writers is met
    if(writer_data_pos == NUM_WRITERS){
        for(i = 0; i < NUM_WRITERS; i++){
            if(pthread_join(writer_threads[i],&exit_status)){
                perror("pthread_join parser thread");
            }
        }
        writer_data_pos = 0;
    }
    if(writer_data_pos < NUM_WRITERS){
        wtd[writer_data_pos].pos = pos;
        wtd[writer_data_pos].list = list;
        if(pthread_create(&writer_threads[writer_data_pos], NULL, write_to_file, &wtd[writer_data_pos])){
            perror("pthread create");
        }
        writer_data_pos++;
    }
    pthread_mutex_unlock(&mutex);      //unlock
    return NULL;
}
//-----------------------------------------------------------------------------------------------------------
void *message_thread(void *threadarg){
    thread_data *my_data;
    my_data = (thread_data *)threadarg;
    pthread_t tid;
    void *exit_status;
    char *file_name = my_data->file_name;
    char *path = my_data->path;
    char absolute[500];
    strcpy(absolute,path);
    strcat(absolute,"/");
    strcat(absolute,file_name);
    

        //read data into main memory
    int retval;
    int file_size;
    FILE *f;
    if((f = fopen(absolute, "r")) == NULL){
        printf("File not opened err\n");
        return NULL;
    }
    fseek (f , 0 , SEEK_END);
    file_size = ftell(f);
    rewind(f);
    char *buffer = (char *)malloc(sizeof(char)*file_size);
    retval = fread (buffer,sizeof(char),file_size,f);
    if (retval != file_size) {
        perror("file parse read");
        pthread_exit(NULL);
    }
    fclose(f);
    
    int status;
    int i;
    int j;
    int k;
    
    int pos = 0;
    int list_size = 1000;
    message *list = (message*)malloc(sizeof(message)*list_size);
    
    char message[1500];
    char line[1500];
    
    char timestamp[15];
    char zone[7];
    char buff[5];
    
    long time;
    
    char error;
    char warning;
    
    // read data line by line
    int file_pos = 0;
    while(file_pos < file_size){
        i = 0;
        while(buffer[file_pos] != '\n' && i < 1499){
            line[i++] = buffer[file_pos++];
        }
        file_pos++;
        line[i] = '\n';
        line[i+1] = '\0';
        
        i = 0;
        if(strchr(line,'[')){
            //printf("%s %d\n",absolute,thread_data_pos);
            
            while(line[i] != '['){
                i++;
            }
            snprintf(buff,5,"%d",current_year);
            //get year
            timestamp[0] = buff[0];
            timestamp[1] = buff[1];
            timestamp[2] = buff[2];
            timestamp[3] = buff[3];
            
            buff[0] = line[4];
            buff[1] = line[5];
            buff[2] = line[6];
            buff[3] = '\0';
            
            for(k = 0; k < 12 ; k++){
                if(strcmp(months[k],buff) == 0){
                    snprintf(buff,4,"%02d",k+1);
                    break;
                }
            }
            //month
            timestamp[4] = buff[0];
            timestamp[5] = buff[1];
            //day
            if(line[8] == ' '){
                timestamp[6] = '0';
            }else{
                timestamp[6] = line[8];
            }
            timestamp[7] = line[9];
            //hour
            timestamp[8] = line[11];
            timestamp[9] = line[12];
            //min
            timestamp[10] = line[14];
            timestamp[11] = line[15];
            //sec
            timestamp[12] = line[17];
            timestamp[13] = line[18];
            timestamp[14] = '\0';
            
            zone[0] = line[20];
            zone[1] = line[21];
            zone[2] = line[22];
            zone[3] = '\0';
            
        }else{
            for(k = 0; k < 14 ; k++){
                timestamp[k] = '0';
            }
            timestamp[k] = '\0';
            zone[0] = '\0';
        }
        time = strtol(timestamp,NULL,0);
        
        //check if in range specified by command line arguments
        if((mem.time_start == -1 && mem.time_end == -1) ||
           (mem.time_start != -1 && mem.time_end == -1 && (time - mem.time_start >= 0)) ||
           (mem.time_start == -1 && mem.time_end != -1 && (mem.time_end - time >= 0)) ||
           (mem.time_start != -1 && mem.time_end != -1 && (time - mem.time_start >= 0) && (mem.time_end - time >= 0))){
            
            j = 0;
            while(line[i] != '\n' && j < 998 && i < 1499){  // message length limit
                message[j] = line[i];
                i++;
                j++;
            }
            message[j] = '\0';
            //flag messages
            error = '1';
            warning = '1';
            
            if(strcasestr(message,"fail")){
                error = '0';
            }
            else if((strcasestr(message,"error") || strcasestr(message," err"))){
                error = '0';
            }
            else if(strcasestr(message," not ") || strcasestr(message,"doesn't")){
                error = '0';
            }
            if(error == '1'){
                if(strcasestr(message,"warning")){
                    warning = '0';
                }
                else if(strcasestr(message,"debug")){
                    warning = '0';
                }
            }
            //copy to message list, if not enough space double size
            if(pos == list_size){
                list_size = 2*list_size;
                list = (struct mes*)realloc(list, sizeof(message)*list_size);
            }
            strcpy(list[pos].message,message);
            strcpy(list[pos].path,path);
            strcpy(list[pos].file_name,file_name);
            strcpy(list[pos].stamp.time,timestamp);
            strcpy(list[pos].stamp.zone,zone);
            list[pos].error = error;
            list[pos].warning = warning;
            pos++;
        }
    }//end while
    free(buffer);
    
    pthread_mutex_lock(&mutex);     //lock
    if(writer_data_pos == NUM_WRITERS){
        for(i = 0; i < NUM_WRITERS; i++){
            if(pthread_join(writer_threads[i],&exit_status)){
                perror("pthread_join parser thread");
            }
        }
        writer_data_pos = 0;
    }
    if(writer_data_pos < NUM_WRITERS){
        wtd[writer_data_pos].pos = pos;
        wtd[writer_data_pos].list = list;
        if(pthread_create(&writer_threads[writer_data_pos], NULL, write_to_file, &wtd[writer_data_pos])){
            perror("pthread create");
        }
        writer_data_pos++;
    }
    pthread_mutex_unlock(&mutex);      //unlock
    return NULL;
}
//-----------------------------------------------------------------------------------------------------------
void *ems_thread(void *threadarg){
    thread_data *my_data;
    my_data = (thread_data *)threadarg;
    pthread_t tid;
    void *exit_status;
    char *file_name = my_data->file_name;
    char *path = my_data->path;
    char absolute[500];
    strcpy(absolute,path);
    strcat(absolute,"/");
    strcat(absolute,file_name);
    
    //printf("%s %d\n", mem->keywords[0], mem->keywords_size);
    
    //printf("%s %d\n",absolute,thread_data_pos);
    
    
    int retval;
    int file_size;
    FILE *f;
    if((f = fopen(absolute, "r")) == NULL){
        printf("File not opened err\n");
        return NULL;
    }
    fseek (f , 0 , SEEK_END);
    file_size = ftell(f);
    rewind(f);
    char *buffer = (char *)malloc(sizeof(char)*file_size);
    retval = fread (buffer,sizeof(char),file_size,f);
    if (retval != file_size) {
        perror("file ems read");
        pthread_exit(NULL);
    }
    fclose(f);
    
    int status;
    int i;
    int j;
    int k;
    
    int pos = 0;
    int list_size = 1000;
    message *list = (message*)malloc(sizeof(message)*list_size);
    
    char message[1500];
    char line[1500];
    
    char timestamp[15];
    char zone[7];
    char buff[4];
    
    long time;
    
    char error;
    char warning;
    
    //printf("%s", buffer);
    
    
    int file_pos = 0;
    while(file_pos < file_size){
        i = 0;
        while(i < 1499){
            if(buffer[file_pos] == '<' && buffer[file_pos+1] == '/' && buffer[file_pos+2] == 'L' && buffer[file_pos+3] == 'R' && buffer[file_pos+4] == '>'){
                //printf("\n\n\n\n break \n\n\n\n");
                break;
            }
            //printf("%c",buffer[file_pos]);
            if(buffer[file_pos] == '\n'){
                line[i++] = ' ';
                file_pos++;
            }else if(buffer[file_pos] == ' ' && buffer[file_pos-1] == ' '){
                file_pos++;
            }else{
                line[i++] = buffer[file_pos++];
            }
        }
        
        //printf("\n");
        file_pos += 6;
        line[i] = '\n';
        line[i+1] = '\0';

        //printf("line: %s", line);
        
        //year
        timestamp[0] = line[12];
        timestamp[1] = line[13];
        timestamp[2] = line[14];
        timestamp[3] = line[15];
    
            
        buff[0] = line[9];
        buff[1] = line[10];
        buff[2] = line[11];
        buff[3] = '\0';
            
            for(k = 0; k < 12 ; k++){
                if(strcmp(months[k],buff) == 0){
                    snprintf(buff,4,"%02d",k+1);
                    break;
                }
            }
            //month
            timestamp[4] = buff[0];
            timestamp[5] = buff[1];
            //day
            timestamp[6] = line[7];
            timestamp[7] = line[8];
            //hour
            timestamp[8] = line[17];
            timestamp[9] = line[18];
            //min
            timestamp[10] = line[20];
            timestamp[11] = line[21];
            //sec
            timestamp[12] = line[23];
            timestamp[13] = line[24];
            timestamp[14] = '\0';
        
            //no zone
            zone[0] = '\0';
        //printf("time : %s\n", timestamp);

        time = strtol(timestamp,NULL,0);
        //printf("time : %ld\n", time);
        
        if((mem.time_start == -1 && mem.time_end == -1) ||
           (mem.time_start != -1 && mem.time_end == -1 && (time - mem.time_start >= 0)) ||
           (mem.time_start == -1 && mem.time_end != -1 && (mem.time_end - time >= 0)) ||
           (mem.time_start != -1 && mem.time_end != -1 && (time - mem.time_start >= 0) && (mem.time_end - time >= 0))){
            
            j = 0;
            i = 0;
            //printf("%s \n", line);
            
            while(1){
                if(line[i] == '>'){
                    break;
                }
                i++;
            }
            i++;
            while(j < 998 && i < 1499){  // message length limit
                if(line[i] == '/' && line[i+1] == '>'){
                    break;
                }else if(line[i] == '<'){
                	i++;
                }else{
                	message[j] = line[i];
                	i++;
                	j++;
                }
            }
            //message[j++] = '/';
            //message[j++] = '>';
            message[j] = '\0';
            //printf("message %s\n", message);
            error = '1';
            warning = '1';
            
            if(strcasestr(message,"fail")){
                error = '0';
            }
            else if((strcasestr(message,"error") || strcasestr(message," err"))){
                error = '0';
            }
            else if(strcasestr(message," not ") || strcasestr(message,"doesn't")){
                error = '0';
            }
            if(error == '1'){
                if(strcasestr(message,"warning")){
                    warning = '0';
                }
                else if(strcasestr(message,"debug")){
                    warning = '0';
                }
            }
            //copy to message list, if not enough space double size
            if(pos == list_size){
                list_size = 2*list_size;
                list = (struct mes*)realloc(list, sizeof(message)*list_size);
            }
            strcpy(list[pos].message,message);
            strcpy(list[pos].path,path);
            strcpy(list[pos].file_name,file_name);
            strcpy(list[pos].stamp.time,timestamp);
            strcpy(list[pos].stamp.zone,zone);
            list[pos].error = error;
            list[pos].warning = warning;
            pos++;
        }
    }//end while
    free(buffer);
    
    pthread_mutex_lock(&mutex);     //lock
    if(writer_data_pos == NUM_WRITERS){
        for(i = 0; i < NUM_WRITERS; i++){
            if(pthread_join(writer_threads[i],&exit_status)){
                perror("pthread_join parser thread");
            }
        }
        writer_data_pos = 0;
    }
    if(writer_data_pos < NUM_WRITERS){
        wtd[writer_data_pos].pos = pos;
        wtd[writer_data_pos].list = list;
        if(pthread_create(&writer_threads[writer_data_pos], NULL, write_to_file, &wtd[writer_data_pos])){
            perror("pthread create");
        }
        writer_data_pos++;
    }
    pthread_mutex_unlock(&mutex);      //unlock
    return NULL;

    
    
}
//-----------------------------------------------------------------------------------------------------------
void *parser_thread(void *threadarg){
    thread_data *my_data;
    my_data = (thread_data *)threadarg;
    pthread_t tid;
    void *exit_status;
    char *file_name = my_data->file_name;
    char *path = my_data->path;
    char absolute[500];
    strcpy(absolute,path);
    strcat(absolute,"/");
    strcat(absolute,file_name);
    
    // read file into main memory
    int retval;
    int file_size;
    FILE *f;
    if((f = fopen(absolute, "r")) == NULL){
        printf("File not opened err\n");
        return NULL;
    }
    fseek (f , 0 , SEEK_END);
    file_size = ftell(f);
    rewind(f);
    char *buffer = (char *)malloc(sizeof(char)*file_size);
    retval = fread (buffer,sizeof(char),file_size,f);
    if (retval != file_size) {
        perror("file parse read");
        pthread_exit(NULL);
    }
    fclose(f);
    
    int status;
    int i;
    int j;
    int k;
    
    int pos = 0;
    int list_size = 1000;
    message *list = (message*)malloc(sizeof(message)*list_size);
    
    char message[1500];
    char line[1500];

    char timestamp[15];
    char zone[7];
    char buff[4];
    
    long time;
    
    char error;
    char warning;

   // read data line by line
    int file_pos = 0;
    while(file_pos < file_size){
        i = 0;
        while(buffer[file_pos] != '\n' && i < 1499){
            line[i++] = buffer[file_pos++];
        }
        file_pos++;
        line[i] = '\n';
        line[i+1] = '\0';

        i = 0;
        if(strchr(line,'[')){
            //printf("%s %d\n",absolute,thread_data_pos);
        
        while(line[i] != '['){
            i++;
        }
        //get year
        timestamp[0] = line[i-21];
        timestamp[1] = line[i-20];
        timestamp[2] = line[i-19];
        timestamp[3] = line[i-18];
        
        buff[0] = line[i-28];
        buff[1] = line[i-27];
        buff[2] = line[i-26];
        buff[3] = '\0';
        
        for(k = 0; k < 12 ; k++){
            if(strcmp(months[k],buff) == 0){
                snprintf(buff,4,"%02d",k+1);
                break;
            }
        }
        //month
        timestamp[4] = buff[0];
        timestamp[5] = buff[1];
        //day
        timestamp[6] = line[i-24];
        timestamp[7] = line[i-23];
        //hour
        timestamp[8] = line[i-16];
        timestamp[9] = line[i-15];
        //min
        timestamp[10] = line[i-13];
        timestamp[11] = line[i-12];
        //sec
        timestamp[12] = line[i-10];
        timestamp[13] = line[i-9];
        timestamp[14] = '\0';
        
        zone[0] = line[i-7];
        zone[1] = line[i-6];
        zone[2] = line[i-5];
        zone[3] = line[i-4];
        zone[4] = line[i-3];
        zone[5] = line[i-2];
        zone[6] = '\0';
        
        }else{
            for(k = 0; k < 14 ; k++){
                timestamp[k] = '0';
            }
            timestamp[k] = '\0';
            zone[0] = '\0';
        }
        time = strtol(timestamp,NULL,0);
        
        //check if in range
        if((mem.time_start == -1 && mem.time_end == -1) ||
           (mem.time_start != -1 && mem.time_end == -1 && (time - mem.time_start >= 0)) ||
           (mem.time_start == -1 && mem.time_end != -1 && (mem.time_end - time >= 0)) ||
           (mem.time_start != -1 && mem.time_end != -1 && (time - mem.time_start >= 0) && (mem.time_end - time >= 0))){
            
            j = 0;
            while(line[i] != '\n' && j < 998 && i < 1499){  // message length limit
                message[j] = line[i];
                i++;
                j++;
            }
            message[j] = '\0';
            
            error = '1';
            warning = '1';
            
            if(strcasestr(message,"fail")){
                error = '0';
            }
            else if((strcasestr(message,"error") || strcasestr(message," err"))){
                error = '0';
            }
            else if(strcasestr(message," not ") || strcasestr(message,"doesn't")){
                error = '0';
            }
            if(error == '1'){
                if(strcasestr(message,"warning")){
                    warning = '0';
                }
                else if(strcasestr(message,"debug")){
                    warning = '0';
                }
            }
            //copy to message list, if not enough space double size
            if(pos == list_size){
                list_size = 2*list_size;
                list = (struct mes*)realloc(list, sizeof(message)*list_size);
            }
            strcpy(list[pos].message,message);
            strcpy(list[pos].path,path);
            strcpy(list[pos].file_name,file_name);
            strcpy(list[pos].stamp.time,timestamp);
            strcpy(list[pos].stamp.zone,zone);
            list[pos].error = error;
            list[pos].warning = warning;
            pos++;
        }
    }//end while
    free(buffer);

    pthread_mutex_lock(&mutex);     //lock if max writer threads is met
    if(writer_data_pos == NUM_WRITERS){
        for(i = 0; i < NUM_WRITERS; i++){
            if(pthread_join(writer_threads[i],&exit_status)){
                perror("pthread_join parser thread");
            }
        }
        writer_data_pos = 0;
    }
    if(writer_data_pos < NUM_WRITERS){
        wtd[writer_data_pos].pos = pos;
        wtd[writer_data_pos].list = list;
        if(pthread_create(&writer_threads[writer_data_pos], NULL, write_to_file, &wtd[writer_data_pos])){
            perror("pthread create");
        }
        writer_data_pos++;
    }
    pthread_mutex_unlock(&mutex);      //unlock
    return NULL;
}

//-----------------------------------------------------------------------------------------------------------
//spawns parser processes for each file contained in folder
int spwan_parsers(char *path){
    DIR *d;
    int i;
    char *appendedPath;
    struct dirent *entry;
    if((d = opendir(path)) == NULL){
        perror("Cannot open dir\n");
        return 1;
    }
    while((entry = readdir(d)) != NULL){
        if(strcmp(entry->d_name,".") && strcmp(entry->d_name,"..") && strcmp(entry->d_name, ".DS_Store")){ //ignores itself and parent
            if (entry->d_type & DT_DIR) {
                appendedPath = new char[256];
                strcpy(appendedPath,path);
                strcat(appendedPath, "/");
                strcat(appendedPath, entry->d_name);
                spwan_parsers(appendedPath);
                delete [] appendedPath;
            }else{
                if(thread_data_pos == NUM_THREADS){
                        for(i = 0; i < NUM_THREADS; i++){
                            if(pthread_join(threads[i],&status)){
                                perror("pthread_join spawn parser");
                            }
                        }
                        thread_data_pos = 0;
                    //update completed percent
                    if((flag_file = fopen(flag_file_path, "w")) == NULL){
                        printf("File not created\n");
                        return 0;
                    }
                    fprintf(flag_file,"%d", (int)(100*((double)num_of_processed_files/(double)total_number_of_files)));
                    fclose(flag_file);
                }
                if(thread_data_pos < NUM_THREADS){
                    num_of_processed_files++;
                    strcpy(td[thread_data_pos].file_name,entry->d_name);
                    strcpy(td[thread_data_pos].path,path);
                    if(strstr(entry->d_name,"EMS-LOG-FILE")){
                        if(pthread_create(&threads[thread_data_pos], NULL, ems_thread, &td[thread_data_pos])){
                            perror("pthread create");
                        }
                        thread_data_pos++;
                    }else if(strstr(entry->d_name,"MESSAGE")){
                        if(pthread_create(&threads[thread_data_pos], NULL, message_thread, &td[thread_data_pos])){
                            perror("pthread create");
                        }
                        thread_data_pos++;
                    }else if(strstr(entry->d_name,"backup")){
                        if(pthread_create(&threads[thread_data_pos], NULL, backup_thread, &td[thread_data_pos])){
                            perror("pthread create");
                        }
                        thread_data_pos++;
                    }else if(strstr(entry->d_name,"sis-log")){
                        if(pthread_create(&threads[thread_data_pos], NULL, sis_log_thread, &td[thread_data_pos])){
                            perror("pthread create");
                        }
                        thread_data_pos++;
                    }else if(strcmp(entry->d_name,"shelf-log-iom")){
                        if(pthread_create(&threads[thread_data_pos], NULL, parser_thread, &td[thread_data_pos])){
                            perror("pthread create");
                        }
                        thread_data_pos++;
                    }else{
                    }
                }
            }//end else
        }//end if
    }//end while
    closedir(d);
    return 0;
    
}
//-----------writer-----------------------------------------------------------------------------------------------
void *write_to_file(void *arg){
    pthread_mutex_lock(&writer_mutex);     //lock
    write_data *my_data;
    my_data = (write_data *)arg;
    int pos = my_data->pos;
    message *list = my_data->list;
    char buff[256];
    int i;
    FILE *fp;
    fp = fopen(output_file, "a");
    //write list to file
    for(i = 0; i < pos; i++){
        memmove(list[i].path,list[i].path+common_path_len, 256 - common_path_len);
        fprintf(fp,"%s/%s\t%s\t%s\t%s\t%c\t%c\n",list[i].path,list[i].file_name,list[i].message,list[i].stamp.time,list[i].stamp.zone,list[i].error,list[i].warning);
    }
    fclose(fp);
    free(list);
    pthread_mutex_unlock(&writer_mutex);     //lock
    return NULL;
    
}
//-----------count_of_files-------------------------------------------------------------------------------------------------
int count_of_files(char *path){
    DIR *d;
    char *appendedPath;
    struct dirent *entry;
    if((d = opendir(path)) == NULL){
        perror("Cannot open dir\n");
        return 1;
    }
    while((entry = readdir(d)) != NULL){
        if(strcmp(entry->d_name,".") && strcmp(entry->d_name,"..") && strcmp(entry->d_name, ".DS_Store")){ //ignores itself and parent
            if (entry->d_type & DT_DIR) {
                appendedPath = new char[256];
                strcpy(appendedPath,path);
                strcat(appendedPath, "/");
                strcat(appendedPath, entry->d_name);
                count_of_files(appendedPath);
                delete [] appendedPath;
            }else{
                    total_number_of_files++;
            }//end else
        }//end if
    }//end while
    closedir(d);
    return 0;
    
}
