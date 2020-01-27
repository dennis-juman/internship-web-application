<?php
    //CHECK IF THE USER HAS ACCESS TO THIS PAGE
    if(!isset($_POST['submit'])){
        die("You do not have permission to view this page.");
    }

    //PUT USER INPUT $_POST ARRAY INPUT INTO VARIABLES FOR READABILITY
    if(isset($_POST['name_project'])){$name_project = $_POST['name_project'];}
    if(isset($_POST['description_project'])){$description_project = $_POST['description_project'];}
    if(isset($_POST['name_resource'])){$name_resource = $_POST['name_resource'];}
    if(isset($_POST['description_resource'])){$description_resource = $_POST['description_resource'];}
    if(isset($_POST['selected_semester'])){$selected_semester = $_POST['selected_semester'];}
    if(isset($_POST['current_sprint'])){$current_sprint = $_POST['current_sprint'];}
    if(isset($_POST['sprint_phase'])){$sprint_phase = $_POST['sprint_phase'];}
    if(isset($_POST['selected_track'])){$selected_track = $_POST['selected_track'];}
    if(isset($_POST['selected_sdg'])){$selected_sdgs = $_POST['selected_sdg'];}


    if(isset($_POST['sdg_search_option_2'])){$sdg_search_option_1 = $_POST['sdg_search_option_1'];}
    if(isset($_POST['sdg_search_option_2'])){$sdg_search_option_2 = $_POST['sdg_search_option_2'];}
    if(isset($_POST['project_name_search_option_1'])){$project_name_search_option_1 = $_POST['project_name_search_option_1'];}
    if(isset($_POST['project_name_search_option_2'])){$project_name_search_option_2 = $_POST['project_name_search_option_2'];}
    if(isset($_POST['project_description_search_option_1'])){$project_description_search_option_1 = $_POST['project_description_search_option_1'];}
    if(isset($_POST['project_description_search_option_2'])){$project_description_search_option_2 = $_POST['project_description_search_option_2'];}
    if(isset($_POST['resource_name_search_option_1'])){$resource_name_search_option_1 = $_POST['resource_name_search_option_1'];}
    if(isset($_POST['resource_name_search_option_2'])){$resource_name_search_option_2 = $_POST['resource_name_search_option_2'];}
    if(isset($_POST['resource_description_search_option_1'])){$resource_description_search_option_1 = $_POST['resource_description_search_option_1'];}
    if(isset($_POST['resource_description_search_option_2'])){$resource_description_search_option_2 = $_POST['resource_description_search_option_2'];}


    //CHECK IF THE USER HAS ENTERED ANY SEARCH CONDTIONS WHATSOEVER
    $search_counts = count($_POST) - 1; //COUNT HOW MANY SEARCH CONDITIONS ARE INSIDE THE POST ARRAY.  THE SUBMIT BUTTON IS ALWAYS SET, OTHERWISE THIS SCRIPT WOULDN'T lOAD. THAT'S WHY WE DO MINUS 1 "-1", WE DON'T HAVE TO COUNT THE SUBMIT BUTTON INTO THE EQUATION
    $counter = 0; //WE DEFINE A VARIABLE THAT CAN ACT AS A COUNTER 1, 2, 3, 4, 5, ETC.
    foreach($_POST as $input){ //WE ITERATE THROUGH EACH ELEMENT ISNIDE OF THE ARRAY
        if(empty($input)){ //IF ONE OF THOSE ELEMENTS IS EMPTY, INCREMENT THE COUNTER BY ONE
        ++$counter; //INCREMENT BY ONE
        }
        if($counter == $search_counts){ //IF THE COUNTER IS EQUAL TO THE COUNT OF THE SUBMITTED DATA, THEN IT MEANS THAT ALL OF THEM ARE EMPTY
            die("You did not search for anything."); //THE USER DID NOT SEARCH FOR ANYTHING, END THE SCRIPT
        }
    }

    //NAME INPUT FIELDS CAN NOT CONTAINS ANY OF THE FOLLOWING VALUES (. <-- A DOT .. <-- TWO DOTS  / <-- SLASES ' ' <-- WHITE SPACE)
    $name_project = str_replace(array('..', '.', '/'), ' ', $name_project); 
    $name_resource = str_replace(array('..', '.', '/'), ' ', $name_resource); 


    // CHECK IF USER INPUT CONTAINS MORE THAN ONE SPACE PER WORD SPACING EXAMPLE:   "MY   NAME    IS"  <-- CONTAINS TOO MANY SPACES, IT SHOULD BE "MY NAME IS"
    $name_project = preg_replace('/\s+/', ' ', $name_project);
    $description_project = preg_replace('/\s+/', ' ', $description_project);


    //DATABASE CONNECTION
    require 'class/db_connection.php';
    $database_connection = new DatabaseConnection();
    $dbh = $database_connection->connection();

    //INITIALIZE SEARCH QUERY 
    $query = "SELECT 

            project.name, project.description,
            track.name, track.description, 
            semester.season, semester.year,
            SDG.position, SDG.title, SDG.description, 
            sprint_numbers.number, sprint_options.option,
            resources.name, resources.description, URLs.URL

            FROM project 
                    
            INNER JOIN track ON project.track_id = track.id 

            INNER JOIN semester ON semester.id = project.semester_id 

            INNER JOIN project_SDG ON project.id = project_SDG.project_id 
            INNER JOIN SDG ON project_SDG.SDG_id = SDG.id 

            INNER JOIN sprint ON sprint.project_id = project.id 
            INNER JOIN sprint_numbers ON sprint_numbers.id = sprint.sprint_number_id 
            INNER JOIN sprint_options ON sprint_options.id = sprint.sprint_option_id 

            LEFT JOIN resources ON project.id = resources.project_id
            LEFT JOIN URLs ON URLs.id = resources.id

            WHERE 1=1";



    //DEFINE ARRAY TO AVOID ERRORS
    $arguments = array(); 

    //THIS FUNCTION CAN CHANGE THE QUERY OPERATOR FROM "AND" TO "OR"
    //THE DEFAULT PARAMETER IS THE "AND" OPERATOR IN CASE NO ARGUMENTS HAVE BEEN GIVEN
    function selected_search_operator_1($search_operator = "AND"){
        if($search_operator == "option_2"){
            $search_operator = "OR";
        } else{
            $search_operator = "AND";
        }
        return $search_operator;
    }

    //SAME FUNCTION AS OPERATOR 1 BUT IN REVERSE
    function selected_search_operator_2($search_operator = "OR"){
        if($search_operator == "option_1"){
            $search_operator = "AND";
        } else{
            $search_operator = "OR";
        }
        return $search_operator;
    }

    // SEARCH FOR PROJECT NAME
    if(isset($name_project)){
        if(!empty($name_project)){
            $query .= " " . selected_search_operator_1($project_name_search_option_2) . " MATCH (project.name) AGAINST (:name_project)"; //IF IT'S *NOT* EMPTY, THEN INITIATE THIS QUERY
            $arguments[':name_project'] = $name_project;
        }
    }

    // SEARCH ON PROJECT DESCRIPTION
    if(isset($description_project)){
        if(!empty($description_project)){
            $query .= " " . selected_search_operator_1($project_description_search_option_2) . " MATCH (project.description) AGAINST (:description_project)"; //IF IT'S EMPTY, THEN CREATE THIS QUERY
            $arguments[':description_project'] = $description_project;
        }
    }

    //SEARCH BY SEMESTER
    if(isset($selected_semester)){
        if(!empty($selected_semester)){
            $query .= " " . selected_search_operator_1() . " semester.id = :selected_semester"; //IF IT'S EMPTY, THEN INITIATE THIS QUERY
            $arguments[':selected_semester'] = $selected_semester;
        }   
    }

    // //SEARCH FOR GLOBAL GOALS (SDGs)
    if(isset($selected_sdgs)){
        if(!empty($selected_sdgs)){
            $sdg = array(); //DEFINE ARRAY VARIABLE TO AVOID ERRORS
                foreach($selected_sdgs as $selected_sdg){ //LOOP THROUGH EACH SDG ONE BY ONE 
                    $sdg[] = ":SDG_" . $selected_sdg; //FOR EACH SDG, PUT THEM INSIDE OF THE SDG ARRAY, THE VALUES WILL LOOK LIKE SDG_1, SDG_2 ETC.
                    $index = ":SDG_" . $selected_sdg; //CREATE AN INDEX FOR OUR SDG ARGUMENTS VARIABLE
                    $arguments[$index] = $selected_sdg; //STORE THE SELECTED SDG NUMBERS INSIDE THEIR CORRESPONDING SDG INDEX
                }
                
                $comma_separated = implode(", ", $sdg); //ADD COMMA'S AFTER EACH SDG IF NECESSARY. THE VALUES WILL LOOK LIKE SDG_1, SDG_2 ETC. (SEPERATED BY A COMMA)
                $query .= " " . selected_search_operator_2($sdg_search_option_1) . " project.id IN (SELECT project_id FROM project_SDG WHERE SDG_id IN (" . $comma_separated . ") GROUP BY project_id HAVING COUNT(DISTINCT SDG_id) = " . count($selected_sdgs) . ")";
        }
    }

    //SEARCH FOR SELECTED TRACK
    if(isset($selected_track)){
        if(!empty($selected_track)){
            $query .= " " . selected_search_operator_1() . " track.id = :selected_track"; //IF IT'S EMPTY, THEN INITIATE THIS QUERY
            $arguments[':selected_track'] = $selected_track;
        }
    }

    // //SEARCH SPRINT NUMBER
    if(isset($current_sprint)){
        if(!empty($current_sprint)){
            $query .= " " . selected_search_operator_1() . " sprint.sprint_number_id = :sprint_number"; //IF IT'S EMPTY, THEN INITIATE THIS QUERY
            $arguments[':sprint_number'] = $current_sprint;
        }
    }

    // //SEARCH SPRINT OPTION
    if(isset($sprint_phase)){
        if(!empty($sprint_phase)){
            $query .= " " . selected_search_operator_1() . " sprint.sprint_option_id = :sprint_option"; //IF IT'S EMPTY, THEN INITIATE THIS QUERY
            $arguments[':sprint_option'] = $sprint_phase;
        }
    }

    //SEARCH FOR RESOURCES BY NAME
    if(isset($name_resource)){
        if(!empty($name_resource)){
            $query .= " " . selected_search_operator_1($resource_name_search_option) . " MATCH (resources.name) AGAINST (:name_resource)"; //IF IT'S EMPTY, THEN CREATE THIS QUERY
            $arguments[':name_resource'] = $name_resource;
        }
    }

    //SEARCH FOR RESOURCES BY DESCRIPTION
    if(isset($description_resource)){
        if(!empty($description_resource)){
            $query .= " " . selected_search_operator_1($resource_description_search_option) . " MATCH (resources.description) AGAINST (:description_resource)"; //IF IT'S EMPTY, THEN CREATE THIS QUERY
            $arguments[':description_resource'] = $description_resource;
        }
    }

    //INVOKE QUERY
    $query = $dbh->prepare($query); //PREPARE QUERY
    $query->execute($arguments);  //INVOKE QUERY ARGUMENTS
    $result_set = $query->fetchAll(PDO::FETCH_BOTH); //FETCH QUERY RESULTS
    $result_set = array_unique($result_set, SORT_REGULAR); //REMOVE DUPLICATES FROM MULTI-DIMENSIONAL ARRAY
    $result_set = array_map("unserialize", array_unique(array_map("serialize", $result_set)));

    print_r($result_set);
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
    <link rel="stylesheet" type="text/css" href="css/search_project.css">
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
            echo "<p>" . $result_set[0][0] . "</p>";

            if(isset($result_set[0][1])){
                if(!empty($result_set[0][1])){
                    echo "<br>";
                    echo "<p>" . $result_set[0][1] . "</p>";
                }
            }
        ?>
    </div>
    <div class="content_wrapper_1">
        <h1>SEMESTER</h1>
        <?php
            echo "<p>" . $result_set[0]['season'] . "</p>";
            echo "<br>";
            echo "<p>" . $result_set[0]['year'] . "</p>";
        ?>
    </div>
    <div class="content_wrapper_1">
        <h1>TRACK</h1>
        <?php
            echo "<p>" . $result_set[0][2] . "</p>";
            echo "<p>" . $result_set[0][3] . "</p>";
        ?>
    </div>
    <div class="content_wrapper_1">
        <h1>GLOBAL GOALS</h1>
        <?php
            $i = -1; // ARRAYS START AT INDEX 0, IF I PUT THE VARIABLE TO ZERO, IT WILL GO UP BY ONE INSIDE OF THE LOOP, THAT'S NOT WHAT WE WANT. IT SHOULD BECOME 0 ON THE FIRST ITERATION
            // $result_set[8]array_unique($result_set[8]);
            foreach($result_set as $result){ //DISPLAY EACH SDG ONE BY ONE USING FOREACH LOOP
                $i++;
                echo "<p>" . $result_set[$i]['position'] . ". " . $result_set[$i]['title'] . "</p>";

                echo "<p>" . $result_set[$i][8] . "</p>";

            }
        ?>
    </div>
    <div class="content_wrapper_1">
        <?php
            echo "<h1>Sprint " . $result_set[0][9] . "</h1><p>" . $result_set[0][10] . "</p>";
        ?>
    </div>
    <?php
        if(isset($result_set[0][11])){
            if(!empty($result_set[0][11])){
                echo '<div class="content_wrapper_1">';
                    echo "<h1>RESOURCES</h1>";
                        echo "<p>" . $result_set[0][9] . " " . $result_set[0][10] . "</p>";
                        echo '<p><a href="' . $result_set[0][11] . '">' . $result_set[0][11] . '</p></a>';
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