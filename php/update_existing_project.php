<?php
/**THE SESSION MAKES SURE THAT, "IF" THE USER REFRESHES THE PAGE AFTER SUBMITTING THE DATA TO THE DB, THAT IT WON'T RE-INSERT WITH A NEW ID. 
 * THE USER SHOULD ONLY BE ABLE TO SUBMIT ONCE USING THIS URL. 
 * THIS MEANS THAT REFRESHING THE PAGE WILL SHOW THE RESULTS OF THE PREVIOUS SUBMIT, BUT WON'T ACTUALLY DO ANYTHING ELSE.
 * AT THE END OF THE CODE, THE SESSION IS UNSET AND DESTROYED.**/
// session_start(); //START A NEW SESSION
// if(!isset($_SESSION['id'])) {
//     exit("This project has already been submitted.");
// }

//CHECK IF THE USER HAS ACCESS TO THIS PAGE
if(!isset($_POST['submit'])){
    die("You do not have permission to view this page.");
}

//CHECK IF THE REQUIRED FIELDS HAVE BEEN SET
if(empty($_POST['selected_project']) || empty($_POST['current_sprint']) || empty($_POST['sprint_phase'])){
        die("You did not fill out all of the required fields.");
    } else{
    //POST ARRAY VALUES INTO VARIABLES FOR CODE READABILITY
    $selected_project_id = $_POST['selected_project'];
    $current_sprint = $_POST['current_sprint'];
    $sprint_phase = $_POST['sprint_phase'];
}

if(isset($_POST['resource_name'])){$resource_name = $_POST['resource_name'];}
if(isset($_POST['resource_description'])){$resource_description = $_POST['resource_description'];}
if(isset($_POST['resource_URLs'])){$resource_URLs = $_POST['resource_URLs'];}

    //WE MAKE AN ARRAY WITH ALL THE RESOURCE INPUTS AND WE REMOVE ALL THE "EMPTY" INPUTS BY USING THE ARRAY_FILTER FUNCTION.
    $resources = array_filter(array($_POST['resource_URLs'], $_POST['resource_name'], $_POST['resource_description'], $resource_description, $_FILES['uploaded_file']['name'][0]));
    if(count($resources) > 1){ //IF THERE ARE MORE THAN 1 RESOURCES, THEN IT MEANS THE USER HAS ENTERED OR "PUT" SOMETHING IN ONE OF THESE RESOURCE FIELDS.
        //THE FOLLOWING FIELDS MUST BE SET IN ORDER FOR USERS TO ADD RESOURCES
        if(empty($_POST['resource_name'])){ //CHECK IF THE USER ADDED A RESOURCE NAME
            die("You forgot to add a resource name");
        }
        if(empty($_FILES['uploaded_file']['name'][0])){ //CHECK IF THE USER UPLOADED A RESOURCE
            die("Uploads are missing.");
        }
    }

    //SANITIZE USER INPUT(s), CHECK IF USER INPUT CONTAINS ONLY WHITESPACES  EXAMPLE: "          " <---- THIS IS AN EMPTY STRING WITH ONLY WHITESPACES
    if(!empty($resource_URLs)){
        $input_fields = array($resource_name);
        foreach($input_fields as $input){ 
            if (strlen(trim($input)) == 0){
                exit("One of the input fields is empty.");
            }
        }

    //CHECK IF THE INPUT NAMES ARE BETWEEN 5 AND 32 
    //NOTE!! IT REALLY MUST BE AT LEAST 5 CHARACTERS, SQL FULLTEXT SEARCH ENGINE NEEDS AT LEAST 5 CHARACTERS TO GIVE GOOD SEARCH RESULTS.
    //MYSQL FULLTEXT DOES NOT WORK IF THE CHARACTERS ARE BELOW 5 LETTERS
    foreach($input_fields as $input_fields){
        if(strlen(trim($input_fields)) < 5){ //MIN LENGTH
            exit("Input name(s) too short.");
        }
        
        if(strlen(trim($input_fields)) >= 64){ //MAX LENGTH
            exit("Input name(s) too long.");
        }
    }
}

//MAKE AN ARRAY WHERE WE CAN STORE OUR RESOURCE URLs SO WE CAN INTERATE THROUGH THEM LATER ON TO INSERT THEM INTO THE DATABASE
$resources = array(); //EMPTY ARRAY
$resources['URLs'] = $resource_URLs;

//CHECK IF ONE OF THE INPUT FIELDS CONTAIN SPECIAL CHARACTERS
// $input_fields = array($name_project, $description_project, $resource_name, $resource_description);
// foreach($input_fields as $input){ 
//     if(preg_match('/[^a-zA-Z\d]/', $input)){ 
//         exit("Special characters are now allowed.");
//     }
// }

//NAME INPUT FIELDS CAN NOT CONTAINS ANY OF THE FOLLOWING VALUES (. <-- A DOT .. <-- TWO DOTS  / <-- SLASES ' ' <-- WHITE SPACE)
if(isset($resource_name)){
    $resource_name = str_replace(array('..', '.', '/'), ' ', $resource_name); 
}
// CHECK IF USER INPUT CONTAINS MORE THAN ONE SPACE PER WORD SPACING EXAMPLE:   "MY   NAME    IS"  <-- CONTAINS TOO MANY SPACES, IT SHOULD BE "MY NAME IS"
if(isset($description_project)){
    $description_project = preg_replace('/\s+/', ' ', $description_project);
}
//---------------END OF BASIC USER VALIDATION CHECKS---------------


//DATABASE CONNECTION
require 'class/db_connection.php';
$database_connection = new DatabaseConnection();
$dbh = $database_connection->connection();

//THIS ARRAY WILL BE USED AS A MULTI-DIMENSIONAL, TO STORE FETCHED RESULTS FROM VARIOUS TABLES
$result_set = array();

//INSERT SPRINT PLAN INTO SPRINT TABLE
$query = $dbh->prepare("INSERT INTO sprint (`project_id`, `sprint_number_id`, `sprint_option_id`) VALUES (?, ?, ?)");
$query->bindParam(1, $selected_project_id, PDO::PARAM_INT);
$query->bindParam(2, $current_sprint, PDO::PARAM_INT, 99);
$query->bindParam(3, $sprint_phase, PDO::PARAM_INT, 99);
$query->execute();


//SELECT FROM EVERYTHING RELATED TO THE "PROJECT" THAT IS BEING UPDATED
$query = $dbh->prepare("SELECT track.name AS track_name, 
                               project.name AS project_name, 
                               semester.season AS semester_season, 
                               semester.year AS semester_year,
                               sprint_numbers.number AS sprint_number,
                               sprint_options.option AS sprint_option

                                FROM project 

                              INNER JOIN track ON project.track_id = track.id 
                              INNER JOIN semester ON project.semester_id = semester.id
                              INNER JOIN sprint ON sprint.project_id = project.id
                              INNER JOIN sprint_options ON sprint_options.id = sprint.sprint_option_id
                              INNER JOIN sprint_numbers ON sprint_numbers.id = sprint.sprint_number_id
                              
                              WHERE project.id = ?");

$query->bindParam(1, $selected_project_id, PDO::PARAM_INT);
$query->execute();
$result_set = $query->fetch(PDO::FETCH_ASSOC);

// //UPLOAD FUNCTIONALITY
if(isset($_FILES['uploaded_file']['name'][0]) && isset($resource_name)){ //CHECK IF THE UPLOAD BUTTON HAS BEEN CLICKED
    if(!empty($_FILES['uploaded_file']['name'][0]) && !empty($resource_name)){ //CHECK IF DOCUMENTS HAVE BEEN UPLOADED

        $file_temp_dir  = $_FILES['uploaded_file']['tmp_name']; //TEMPORARY FILE DIRECTORY OF THE UPLOADED FILE ON THE PHP SERVER
        $file_name = $_FILES['uploaded_file']['name']; //NAME OF THE UPLOADED FILE
        $file_type  = $_FILES['uploaded_file']['type']; //TYPE (EXTENSION) OF THE UPLOADED FILE
        $file_size = $_FILES['uploaded_file']['size']; //SIZE OF THE UPLOADED FILE
        /**NOTE:
         * IF YOU WANT TO INCREASE OR DECREASE THE UPLOAD SIZE OR EXECUTION / INPUT TIME THEN YOU HAVE TO CONFIGURE THESE SETTINGS IN THE PHP.INI FILE OF YOUR PHP VERSION
         * HERE'S AN EXAMPLE OF THE 4 THINGS THAT YOU CAN RECONFIGURE IN THE PHP.INI FILE
         *      upload_max_filesize = 50M
                post_max_size = 50M
                max_input_time = 300
                max_execution_time = 300 
           YOU CAN QUICKLY FIND THEM BY USING CTRL+F or CMD+F
           
           AN ALTERNATIVE METHOD WOULD BE TO EDIT THE HTACCESS FILE
           .HTACCESS EXAMPLE TO LOOK FOR:
                php_value upload_max_filesize 50M
                php_value post_max_size 50M
                php_value max_input_time 300
                php_value max_execution_time 300
        **/

        //TRANSFER UPLOADS FROM PHP SERVER TO UPLOADS DIRECTORY FOR EACH FILE
        for($i = 0; $i < count(array_filter($file_name)); ++$i){ //COUNT HOW MANY FILES ARE BEING UPLOADED
            $upload_location = "/var/www/library/uploads/" . $result_set['track_name'] . "/" . $result_set['project_name'] . "/" . $result_set['semester_season'] . " " . $result_set['semester_year'] . "/Sprint " . $result_set['sprint_number'] . "/" . $result_set['sprint_option'] . "/";
            // $upload_location = dirname(getcwd()) . "/uploads/" . $result_set['track_name'] . "/" . $result_set['project_name'] . "/" . $result_set['semester_season'] . " " . $result_set['semester_year'] . "/Sprint " . $result_set['sprint_number'] . "/" . $result_set['sprint_option'] . "/";

            //CHECK IF THE GENERATED UPLOAD LOCATION ALREADY EXIST
            if (!file_exists($upload_location)) { //<-- WE CHECK IF THE DIRECTORY ALREADY EXISTS
                mkdir($upload_location, 0775, true); //<-- WE CREATE THE DIRECTORY HERE IF IT DOES NOT EXIST YET
            }

            $upload_location .= basename($file_name[$i]); //THIS IS THE COMPLETE UPLOAD LOCATION + THE FILE NAME
            $xfer_to_file_location = move_uploaded_file($file_temp_dir[$i], $upload_location); //TRANSFER FROM TEMP PHP SERVER TO ACTUAL UPLOAD DIRECTORY

            //CHECK FOR UPLOAD FAILURE
            if (!$xfer_to_file_location) {
                echo "<br>";
                exit("The script failed to upload your files."); //IF ONE FAILS, STOP THE ENTIRESCRIPT
            } 
            //PUT UPLOAD DIRECTORY PATH INTO ARRAY SO WE CAN LATER INSERT THE PATH LOCATION INTO THE DATABASE
            $resources[1] = $upload_location; //WE BASICALLY SET THE INITIAL VALUE FOR INDEX 1, THE INITIAL VALUE IS THE UPLOAD LOCATION STRING
        } 

        //INSERT INTO RESOURCE TABLE
        $query = $dbh->prepare("INSERT INTO resources (`name`, `description`, `project_id`) VALUES (?, ?, ?)");
        $query->bindParam(1, $resource_name, PDO::PARAM_STR, 333);
        $query->bindParam(2, $resource_description, PDO::PARAM_STR, 999);
        $query->bindParam(3, $selected_project_id, PDO::PARAM_INT);
        $query->execute();

        //RETREIVE LAST GENERATED PRIMARY KEY
        $resource_id = $dbh->lastInsertId();

        //INSERT INTO URLs TABLE
        foreach($resource_URLs as $URL){
            $query = $dbh->prepare("INSERT INTO URLs (`id`, `URL`) VALUES (?, ?)");
            $query->bindParam(1, $resource_id, PDO::PARAM_INT);
            $query->bindParam(2, $URL, PDO::PARAM_STR, 999);
            $query->execute();
        }

        $query = $dbh->prepare("SELECT 
                                resources.name AS resource_name,
                                resources.description AS resource_description, 
                                URLs.URL AS URLs
                                FROM resources 
                                INNER JOIN URLs ON URLs.id = resources.id
                                WHERE resources.id = ?");
        $query->bindParam(1, $resource_id, PDO::PARAM_INT);
        $query->execute();
        $resources = $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!doctype html>
<html lang="en">
<head>
<!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

    <!-- MY OWN CSS STYLE ONTOP OF BOOSTRAP -->
    <link rel="stylesheet" type="text/css" href="css/update_existing_project.css">
    <link rel="stylesheet" type="text/css" href="../css/bootstrap_overriden.css">

    <title>Return projects</title>
</head>
<body>
    <div id="logo">
        <a href="../index.php"><img src="../resources\images\logo\dss_logo_3.png"></img></a>
    </div>
    <div class="content_wrapper_1">
        <h1>PROJECT</h1>
        <?php
            echo "<p>" . $result_set['project_name'] . "</p>";
        ?>
    </div>
    <div class="content_wrapper_1">
        <h1>SEMESTER</h1>
        <?php
            echo "<p>" . $result_set['semester_season'] . " " . $result_set['semester_year'] . "</p>";
        ?>
    </div>
    <div class="content_wrapper_1">
        <h1>TRACK</h1>
        <?php
            echo "<p>" . $result_set['track_name'] . "</p>";
        ?>
    </div>
    <div class="content_wrapper_1">
        <?php
            echo "<h1>Sprint " . $result_set['sprint_number'] . "</h1><p>" . $result_set['sprint_option'] . "</p>";
        ?>
    </div>
    <?php
        if(isset($resources)){
            if(!empty($resources)){
                echo '<div class="content_wrapper_1">';
                    echo "<h1>RESOURCES</h1>";
                        echo "<p>" . $resources[0]['resource_name'] . "</p>";
                        
                        if(isset($resources[0]['resource_description'])){
                            if(!empty($resources[0]['resource_description'])){
                                echo "<p>" . $resources[0]['resource_description'] . "</p>";
                            }
                        }

                        if(isset($resources[0]['URLs'])){
                            if(!empty($resources[0]['URLs'])){
                                for($i = 0; $i < count($resources); $i++){
                                    echo '<p><a href="' . $resources[$i]['URLs'] . '">' . $resources[$i]['URLs'] . '</p></a>';
                                }
                            }
                        }
                echo '</div>';
            }
        }
    ?>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
</body>
</html>

<?php
session_unset(); //UNSET THE CURRENT SESSION
session_destroy(); //DESTROY THE CURRENT SESSION
exit(); //CLOSE THE SCRIPT