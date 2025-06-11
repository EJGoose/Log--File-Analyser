<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>log file analyser</title>

    <style> 
        /* background styling*/
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: #f9f9f9;
            font-family: Verdana, Arial, Helvetica, sans-serif; 
        }

        section {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color:rgb(255, 255, 255);
            width: 60%;
            padding: 20px;
        }

        /*form styles*/
        form {
            max-width: 450px;
            display: flex;
            flex-direction: column;
        }

        select {
            text-align: center;
            padding: 10px;
        }

        form label {
            margin: 10px;
        }

        form #submit-btn{
            margin-top: 15px;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #fff;
            background-color: rgb(65, 110, 225);
            color: white;
        }

        #submit-btn:hover{
            background-color: rgb(51, 84, 168);
        }



        /* table values */
        .valid {
            color: green;
        }

        .invalid {
            color: red;
        }

        table {
            border: 1px solid #ddd;
            border-collapse: collapse;
            max-width: 900px;
        }

        tr:hover {
            background-color: rgb(97, 179, 198);
        }

        th {
            font-szie: 120%;
            background-color: rgb(152, 236, 255);
        }

        td, th {
            padding: 10px;
            text-align: center;
            vertical-align: center;
            border-bottom: 1px solid #ddd;
        }

        .alert {
            color: rgb(111, 2, 144);
        }
        .no_issue {
            color: rgb(99, 99, 99);
        }

        ul {
            list-style-type: none;
        }

        ol {
            width: 70%;
        }

        li {
            margin: 5px;
        }

        .results {
            margin-bottom: 30px;
            padding: 30px;
        }

        .reporting {
            display: flex;
        }

        .reporting div {
            display: flex;
            flex-direction: column;
            padding: 10px;
        }

    </style>
    
</head>
<body>
    <section class= "introduction">
        <h1>Log File Analyser</h1>
        <p>Upload and process a log file.</p>
        <p>Log files must be of type .txt and entries formatted: [YYYY-MM-DD HH:MM:SS] [LOG_LEVEL] [SOURCE] Message</p>

    </section>        
    <!-- html form to allow users to add multiple emails -->
    <section class="validator">
        <h2>Log File Analyser</h2>
        <form action = "" method = "post" enctype="multipart/form-data"> 
            <label for="text">Choose a file to upload</label>
            <input name="text" type="file" id="text" ></input>
            <label for="filter">Text to Filter:</label>
            <input type="text" name="filter" id="filter">
            <label for="date">Filter by date</label>
            <input name="date" type="datetime-local" id="date" name="date" min="2025-01-01T00:00:00" max="2025-12-31T11:59:59"></input>
            <input id="submit-btn" type="submit" value="Submit"/>
        </form>
    </section>
    
    <section class="results">
        <?php
            $sample_file = false;

            // importing a file

            //check that the post method has been used
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                exit ("POST request method is required.");
            }

            // ensure file uploads are enabled
            if (empty($_FILES)) {
                exit ("$_FILES is empty, ensure file_uploads are enabled.");
            }

            //give feedback for file upload errors
            if ($_FILES["text"]["error"] !== UPLOAD_ERR_OK) {
                switch($_FILES["text"]["error"]){
                    case UPLOAD_ERR_PARTIAL:
                        exit ("File only partially uploaded");
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        echo ("No file was uploaded, using sample file");
                        $sample_file = true;
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        exit ("File upload blocked by a PHP extension.");
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        exit ("File exceeds MAX_FILE_SIZE in the HTML form");
                        break;
                    case UPLOAD_ERR_INI_SIZE:
                        exit ("File exceeds upload_max_filesize in php.ini");
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        exit("Temporary folder is not found.");
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        exit("Failed to write to file");
                        break;
                    default:
                        exit ("An unknown error occured");
                        break;
                }
            }

            //save the file
            if ($sample_file) {
                $destination = __DIR__ . "/uploads/simpletextfile.txt";
            } else {
                //restrict file size
                if ($_FILES["text"]["size"] > 1048576) {
                    exit ("file is larger than maximum size: 1MB");
                }
    
                //check the file type
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $finfo->file($_FILES["text"]["tmp_name"]);
                $accepted_mime_type = "text/plain";
                
                //warn the user if they haven't uploaded a text file
                if ($mime_type != $accepted_mime_type) {
                    exit("Invalid file type, please only upload plain text log files.");
                }
                
                $pathinfo = pathinfo($_FILES["text"]["name"]); //file path
                $base = $pathinfo["filename"];//file name
                $base = preg_replace("/[^\w-]/", "_", $base); //remove spaces from the name
    
                $filename = $base . "." . $pathinfo["extension"]; //create full filename
    

                $destination = __DIR__ . "/uploads/" . $filename; //full save location

    
                // check if a file already exsists 
                $i = 1; 
                
                //add 1 to the file name if it already exists
                while (file_exists($destination)) {
                    $filename = $base . "($i)." . $pathinfo["extension"];
                    $destination = __DIR__ . "/uploads/" . $filename;
                    $i++;
                }
    
                //if you can't move the file, inform the user
                if ( ! move_uploaded_file($_FILES["text"]["tmp_name"], $destination)) {
                    exit("Can't move uploaded file");
                };
    
                echo "<h2 class='valid'>File uploaded successfully.</h2>";

            }

            


            //read the file, if possible then display the contents
            $myfile = fopen("$destination", "r") or die ("unable to open file.");

            
            //declare variables and regex patterns
            $date_time_check = "/^\[[0-9-: ]+\]/";
            $dates = array();
            $log_level_check = "/\[[A-Z]+\]/";
            $log_levels = array();
            $source_check = "/\[[A-Z]{1,}[a-z]{1,}[A-Za-z]+\]/";
            $sources = array();
            $message_check = "/\s[\w]{1,}\s.{1,}$/";
            $messages = array();

            //until the end of the file go line by line printing out contents.
            while(!feof($myfile)) {
                $current_line = fgets($myfile);
                preg_match($date_time_check,$current_line, $match);
                array_push($dates, $match[0]);
                preg_match($log_level_check,$current_line, $match);
                array_push($log_levels, $match[0]);
                preg_match($source_check,$current_line, $match);
                array_push($sources, $match[0]);
                preg_match($message_check,$current_line, $match);
                array_push($messages, $match[0]);
            }
            fclose($myfile); //close file

            //display a table, based on the data no filtering
            function report_generator($dates, $log_levels, $sources, $messages){
                $iterations = 0;
                echo "<table>";
                echo "<tr><th>Date</th><th>Log Level</th><th>Source</th><th>Message</th></tr>";
                foreach ($dates as $date) {
                    echo "<tr>";
                    echo "<td>$date</td>";
                    echo "<td>$log_levels[$iterations]</td>";
                    echo "<td>$sources[$iterations]</td>";
                    echo "<td>$messages[$iterations]</td>";
                    echo "</tr>";
                    $iterations++;
                }

                echo "</table>";
            }

            //count each log type and return it
            function count_log($array, $name) {
                $log_counts = array_count_values($array);
                echo "<div>";
                echo "<h3>Log $name</h3>";
                foreach ($log_counts as $key => $value) {
                    echo "<span>" . str_replace(["[","]"],"",$key) . ": $value</span>";
                }
                echo "</div>";

            }
            //filter the table based on user input
            function filter_table($dates,$log_levels,$sources,$messages, $criteria, $date_filter) {
                $logs=[];

                // build the logs array with the values
                for ($i = 0; $i < count($dates); $i++) {
                    $logs[] = [
                        "timestamp" => $dates[$i],
                        "level" => $log_levels[$i],
                        "source" => $sources[$i],
                        "message" => $messages[$i]
                    ];
                }

                

                //filter logs by date
                $filteredLogs = array_filter($logs, function($log) use ($criteria, $date_filter){

                    //check the date filter
                    if (!empty($date_filter)) {
                        //Extract date portion from timestamp
                        $log_date = substr($log["timestamp"],1,10); //gets YYYY-MM-DD
                        $log_time = substr($log["timestamp"],12,5); // gets HH:MM
                        $log_date_time = $log_date . "T" . $log_time; // match html datetime-local format

                        //if dates don't match, exclude this log entry
                        if ($log_date_time != $date_filter) {
                            return false;
                        }

                    }

                    if(empty($criteria)) { // if there aren't any criteria specifed return everything
                        return true;
                    }
    
                    $pattern = "/" . preg_quote($criteria,'/') . "/i"; //create a pattern with the criteria

                    //return the filtered logs back
                    return preg_match($pattern, $log["timestamp"]) ||
                     preg_match($pattern, $log["level"]) || 
                     preg_match($pattern, $log["source"]) || 
                     preg_match($pattern, $log["message"]);

                });

                //print out filtered logs
                echo "<table>";
                echo "<tr><th>Date</th><th>Log Level</th><th>Source</th><th>Message</th></tr>";
                if (count($filteredLogs) > 0) {
                    foreach ($filteredLogs as $log) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($log['timestamp']) . "</td>";
                        echo "<td>" . htmlspecialchars($log['level']) . "</td>";
                        echo "<td>" . htmlspecialchars($log['source']) . "</td>";
                        echo "<td>".htmlspecialchars( $log['message']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No matching entries found</td></tr>";
                }

                echo "</table>";
            }
        
        //return count of levels and sources
        echo "<h3>Total Count of Levels and Sources</h3>";
        echo "<div class='reporting'>";
        count_log($log_levels, "Levels");
        count_log($sources, "Sources");
        echo "</div>";

        // process form submission
        $filter = isset($_POST["filter"]) ? htmlspecialchars(trim($_POST["filter"])) : "";
        $date_filter = isset($_POST["date"]) ? htmlspecialchars(trim($_POST["date"])) : "";

        //check if filters are required
        if (empty($filter) && empty($date_filter)) {
            echo "<h3>Unfiltered report</h3>";
            report_generator($dates, $log_levels,$sources,$messages);
        } else {
            echo "<h3>Filtered report</h3>";
           filter_table($dates, $log_levels, $sources, $messages, $filter, $date_filter);
        }
         


        ?>
    </section>
    
</body>
</html>