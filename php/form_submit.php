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
if(empty($_POST['selected_track']) || empty($_POST['name_project']) || empty($_POST['current_sprint']) || empty($_POST['selected_semester']) || empty($_POST['sprint_phase'])){
        die("You did not fill out all of the required fields.");
    } else{
    //POST ARRAY VALUES INTO VARIABLES FOR CODE READABILITY
    $selected_track = $_POST['selected_track'];
    $name_project = $_POST['name_project'];
    $selected_semester = $_POST['selected_semester'];
    $current_sprint = $_POST['current_sprint'];
    $sprint_phase = $_POST['sprint_phase'];
    $description_project = $_POST['description_project'];
    if(isset($_POST['selected_sdg'])) { $selected_sdgs = $_POST['selected_sdg']; } //IS AN ARRAY EVEN IF ONLY ONE ITEM IS SELECTED. YOU CAN'T ECHO THIS.
}

if(isset($_POST['resource_name'])){$resource_name = $_POST['resource_name'];}
if(isset($_POST['resource_description'])){$resource_description = $_POST['resource_description'];}
if(isset($_POST['resource_URLs'])){$resource_URLs = $_POST['resource_URLs'];}

    //WE MAKE AN ARRAY WITH ALL THE RESOURCE INPUTS AND WE REMOVE ALL THE "EMPTY" INPUTS BY USING THE FILTER.
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

    //SANITIZE USER INPUTS AND CHECK IF THE SCRIPT RECEIVES THE INPUT THAT IS GIVEN
    if(!empty($resource_name) && !empty($resource_URLs)){
        $input_fields = array($name_project, $resource_name);
        foreach($input_fields as $input){ 
            if (strlen(trim($input)) == 0){ // CHECK IF USER INPUT CONTAINS ONLY WHITESPACES  EXAMPLE: "          " <---- THIS IS AN EMPTY STRING WITH ONLY WHITESPACES
                exit("One of the input fields is empty.");
            }
        }

    //CHECK IF THE INPUT NAMES ARE BETWEEN 8 AND 32
    foreach($input_fields as $input_fields){
        if(strlen(trim($input_fields)) < 5){ //MIN LENGTH
            exit("Input name(s) too short.");
        }
        
        if(strlen(trim($input_fields)) >= 64){ //MAX LENGTH
            exit("Input name(s) too long.");
        }
    }
}
//MAKE AN ARRAY WHERE WE CAN STORE OUR RESOURCES SO WE CAN INTERATE THROUGH THEM TO INSERT THEM INTO THE DATABASE
$resources = array(); //EMPTY ARRAY
$resources[0] = $resource_URLs;



//CHECK IF ONE OF THE INPUT FIELDS CONTAIN SPECIAL CHARACTERS
// $input_fields = array($name_project, $description_project, $resource_name, $resource_description);
// foreach($input_fields as $input){ 
//     if(preg_match('/[^a-zA-Z\d]/', $input)){ 
//         exit("Special characters are now allowed.");
//     }
// }


//NAME INPUT FIELDS CAN NOT CONTAINS ANY OF THE FOLLOWING VALUES (. <-- A DOT .. <-- TWO DOTS  / <-- SLASES ' ' <-- WHITE SPACE)
$name_project = str_replace(array('..', '.', '/'), ' ', $name_project); 
$resource_name = str_replace(array('..', '.', '/'), ' ', $resource_name); 


// CHECK IF USER INPUT CONTAINS MORE THAN ONE SPACE PER WORD SPACING EXAMPLE:   "MY   NAME    IS"  <-- CONTAINS TOO MANY SPACES, IT SHOULD BE "MY NAME IS"
$name_project = preg_replace('/\s+/', ' ', $name_project);
$description_project = preg_replace('/\s+/', ' ', $description_project);


//CHECK IF THE INPUT DESCRIPTION TEXT IS BETWEEN 50 AND 255
// $input_descriptions = array($description_project);
// foreach($input_descriptions as $input_description){
//     if(strlen(trim($input_description)) < 64){ //MIN LENGTH
//         exit("Input description is too short.");
//     }

//     if(strlen(trim($input_name)) >= 255){ //MAX LENGTH
//         exit("Input name is too long.");
//     }
// }

//DATABASE CONNECTION
require 'class/db_connection.php';
$database_connection = new DatabaseConnection();
$dbh = $database_connection->connection();

//THIS ARRAY WILL BE USED AS A MULTI-DIMENSIONAL, TO STORE FETCHED RESULTS FROM VARIOUS TABLES
$result_set = array();

//INSERT INTO PROJECT TABLE
$query = $dbh->prepare("INSERT INTO project (`track_id`, `semester_id`, `name`, `description`) VALUES (?, ?, ?, ?)");
$query->bindParam(1, $selected_track, PDO::PARAM_INT, 99);
$query->bindParam(2, $selected_semester, PDO::PARAM_INT, 99);
$query->bindParam(3, $name_project, PDO::PARAM_STR, 333);
$query->bindParam(4, $description_project, PDO::PARAM_STR, 999);
$query->execute();

//GET THE LAST INSERTED ID
$project_id = $dbh->lastInsertId();

//SELECT PROJECT NAME & DESCRIPTION
$query = $dbh->prepare("SELECT `name`, `description` FROM project WHERE `id` = ?");
$query->bindParam(1, $project_id, PDO::PARAM_INT);
$query->execute();
$result_set['project'] = $query->fetch(PDO::FETCH_ASSOC);

//SELECT TRACK
$query = $dbh->prepare("SELECT `id`, `name`, `description` FROM track WHERE `id` = ?");
$query->bindParam(1, $selected_track, PDO::PARAM_STR, 99);
$query->execute();
$result_set['track'] = $query->fetch(PDO::FETCH_ASSOC);

//SELECT SEMESTER
$query = $dbh->prepare("SELECT `season`, `year` FROM semester WHERE `id` = ?"); //SPRINT OPTIONS, SPRINT REVIEW ETC.
$query->bindParam(1, $selected_semester, PDO::PARAM_INT, 99);
$query->execute();
$result_set['semester'] = $query->fetch(PDO::FETCH_ASSOC);

//INSERT GLOBAL GOALS (SDGs)
if(isset($selected_sdgs)){
    if(!empty($selected_sdgs)){
        foreach($selected_sdgs as $selected_sdg){ //INSERT SELECTED SDGs ONE BY ONE INTO THE DATABASE
            $query = $dbh->prepare("INSERT INTO project_SDG (`project_id`, `SDG_id`) VALUES (?, ?)");
            $query->bindParam(1, $project_id, PDO::PARAM_INT);
            $query->bindParam(2, $selected_sdg, PDO::PARAM_INT, 99);
            $query->execute();
        }
    }
}

//SELECT GLOBAL GOALS (SDGs)
$query = $dbh->prepare("SELECT `position`, `title`, `description` FROM SDG INNER JOIN project_SDG ON project_SDG.SDG_id = SDG.id WHERE project_SDG.project_id = ?");
$query->bindParam(1, $project_id, PDO::PARAM_INT);
$query->execute();
$result_set['global_goals'] = $query->fetch(PDO::FETCH_ASSOC);



//INSERT INTO SPRINT TABLE
$query = $dbh->prepare("INSERT INTO sprint (`project_id`, `sprint_number_id`, `sprint_option_id`) VALUES (?, ?, ?)");
$query->bindParam(1, $project_id, PDO::PARAM_INT);
$query->bindParam(2, $current_sprint, PDO::PARAM_INT, 99);
$query->bindParam(3, $sprint_phase, PDO::PARAM_INT, 99);
$query->execute();

//SELECT SPRINT
$query = $dbh->prepare("SELECT `id`, `number` FROM sprint_numbers WHERE `number` = ?"); //CURRENT SPRINT 1-5
$query->bindParam(1, $current_sprint, PDO::PARAM_INT, 99);
$query->execute();
$result_set['sprint_numbers'] = $query->fetch(PDO::FETCH_ASSOC); //FETCH SPRINT NUMBER

$query = $dbh->prepare("SELECT `id`, `option` FROM sprint_options WHERE `id` = ?"); //CURRENT SPRINT 1-5
$query->bindParam(1, $sprint_phase, PDO::PARAM_INT, 99);
$query->execute();
$result_set['sprint_options'] = $query->fetch(PDO::FETCH_ASSOC); //FETCH SPRINT OPTION



// //UPLOAD FUNCTIONALITY
if(isset($_FILES['uploaded_file']['name'][0]) && isset($name_project)){ //CHECK IF THE UPLOAD BUTTON HAS BEEN CLICKED
    if(!empty($_FILES['uploaded_file']['name'][0]) && !empty($name_project)){ //CHECK IF DOCUMENTS HAVE BEEN UPLOADED

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
           YOU CAN QUICKLY FIND THEM BY USING CTRL+F
           
           AN ALTERNATIVE METHOD WOULD BE TO EDIT THE HTACCESS FILE
           .HTACCESS EXAMPLE TO LOOK FOR:
                php_value upload_max_filesize 50M
                php_value post_max_size 50M
                php_value max_input_time 300
                php_value max_execution_time 300
        **/

        //TRANSFER UPLOADS FROM PHP SERVER TO UPLOADS DIRECTORY FOR EACH FILE
        for($i = 0; $i < count(array_filter($file_name)); ++$i){ //COUNT HOW MANY FILES ARE BEING UPLOADED
            $upload_location = "/var/www/library/uploads/" . $result_set['track']['name'] . "/" . $result_set['project']['name'] . "/" . $result_set['semester']['season'] . " " . $result_set['semester']['year'] . "/Sprint " . $result_set['sprint_numbers']['number'] . "/" . $result_set['sprint_options']['option'] . "/";
            // $upload_location = dirname(getcwd()) . "/uploads/" . $result_set['track']['name'] . "/" . $result_set['semester']['season'] . " " . $result_set['semester']['year'] . "/" . $result_set['project']['name'] . "/Sprint " . $result_set['sprint_numbers']['number'] . "/" . $result_set['sprint_options']['option'] . "/";
            // $safe_cloud_upload_location = "/library/uploads/" . $result_set['track']['name'] . "/" . $result_set['semester']['season'] . " " . $result_set['semester']['year'] . "/" . $result_set['project']['name'] . "/Sprint " . $result_set['sprint_numbers']['number'] . "/" . $result_set['sprint_options']['option'] . "/"; //GENERATED LINK FOR SAFE CLOUD

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
        $query->bindParam(3, $project_id, PDO::PARAM_INT);
        $query->execute();

        //RETREIVE LAST GENERATED PRIMARY KEY
        $resource_id = $dbh->lastInsertId();

        $query = $dbh->prepare("SELECT `name`, `description` FROM resources WHERE `id` = ?");
        $query->bindParam(1, $resource_id, PDO::PARAM_INT);
        $query->execute();
        $result_set['resources'] = $query->fetch(PDO::FETCH_ASSOC);

        //INSERT INTO URLs TABLE
        foreach($resource_URLs as $URL){
            $query = $dbh->prepare("INSERT INTO URLs (`id`, `URL`) VALUES (?, ?)");
            $query->bindParam(1, $resource_id, PDO::PARAM_INT);
            $query->bindParam(2, $URL, PDO::PARAM_STR, 999);
            $query->execute();
        }

        //SELECT URL RESOURCES (URLs)
        $query = $dbh->prepare("SELECT `URL` AS URLs FROM URLs INNER JOIN resources ON URLs.id = resources.id WHERE resources.id = ?");
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
    <link rel="stylesheet" type="text/css" href="css/form_submit.css">
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
            echo "<p>" . $result_set['project']['name'] . "</p>";
            if(isset($result_set['project']['description'])){
                if(!empty($result_set['project']['description'])){
                    echo "<br>";
                    echo "<p>" . $result_set['project']['description'] . "</p>";
                }
            }
        ?>
    </div>
    <div class="content_wrapper_1">
        <h1>SEMESTER</h1>
        <?php
            echo "<p>" . $result_set['semester']['season'] . " " . $result_set['semester']['year'] . "</p>";
        ?>
    </div>
    <div class="content_wrapper_1">
        <h1>TRACK</h1>
        <?php
            echo "<p>" . $result_set['track']['name'] . "</p>";
            echo "<p>" . $result_set['track']['description'] . "</p>";
        ?>
    </div>
    <div class="content_wrapper_1">
        <h1>GLOBAL GOALS</h1>
        <?php
            if(isset($selected_sdgs)){
                foreach($selected_sdgs as $selected_sdg){
                    $query = $dbh->prepare("SELECT position, title FROM SDG WHERE position = ?");
                    $query->bindParam(1, $selected_sdg, PDO::PARAM_INT, 99);
                    $query->execute(); //EXECUTE QUERY.
                    $sdgs = $query->fetch(PDO::FETCH_NUM);
                
                    //TRAVERSE ELEMENTS IN ARRAY AND DISPLAY THEM
                    echo "<p>";
                    foreach($sdgs as $sdg){
                        echo $sdg . ". ";
                    }

                    //FOR EVERY 2ND ITEM, PLACE A BR. THERE ARE TWO VALUES IN THE ARRAY, THE POSITION AND THE TITLE, TOGETHER THEY ARE ONE. SO FOR EACH COMPLETE ITERATION, PLACE A BR.
                    for($i = 0; $i < count($sdgs); ++$i){
                        if($i % 2){
                        }
                    }
                    echo "</p>";
                } 
            }
        ?>
    </div>
    <div class="content_wrapper_1">
        <?php
            echo "<h1>Sprint " . $result_set['sprint_numbers']['number'] . "</h1><p>" . $result_set['sprint_options']['option'] . "</p>";
        ?>
    </div>
    <?php
        if(isset($result_set['resources'])){
            if(!empty($result_set['resources'])){
                echo '<div class="content_wrapper_1">';
                    echo "<h1>RESOURCES</h1>";
                        echo "<p>" . $result_set['resources']['name'] . "</p>";
                        
                        if(isset($result_set['resources']['description'])){
                            if(!empty($result_set['resources']['description'])){
                                echo "<p>" . $result_set['resources']['description'] . "</p>";
                            }
                        }

                        // if(isset($result_set['URLs']['URL'])){
                        //     if(!empty($result_set['URLs']['URL'])){
                        //         echo '<p><a href="' . $result_set['URLs']['URL'] . '">' . $result_set['URLs']['URL'] . '</p></a>';
                        //     }
                        // }

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