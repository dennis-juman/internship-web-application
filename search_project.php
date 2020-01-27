<?php
    // session_unset(); //UNSERT PREVIOUS SESSIONS IF ANY
    // session_start(); //START A NEW SESSION
    // $_SESSION['id'] = session_id();

    //DATABASE CONNECTION
    require 'php/class/db_connection.php';
    $database_connection = new DatabaseConnection();
    $dbh = $database_connection->connection();
?>
<!doctype html>
<html lang="en">
<head>
    <title>Search Project | Digital Society School</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- GETBOOTSTRAP (STOCK BOOTSTRAP) -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">

    <!-- MD BOOTSTRAP (MATERIAL DESIGN BOOTSTRAP) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdbootstrap/4.10.1/css/mdb.min.css" rel="stylesheet">

    <!-- MY OWN CSS STYLE ONTOP OF BOOSTRAP -->
    <link rel="stylesheet" type="text/css" href="css/search_project.css">
    <link rel="stylesheet" type="text/css" href="css/bootstrap_overriden.css">
</head>
<body>
    <div id="logo">
      <a href="index.php"><img src="resources\images\logo\dss_logo_3.png"></img></a>
    </div>
        <!-- USER FORM -->
        <form action="php/search_project.php" method="POST" id="user_form" enctype="multipart/form-data">
            <div id="content_wrapper_1">  
                <h1>SEARCH BY PROJECT</h1>
                <table class="table table-bordered">
                    <tr>  
                        <td><input type="text" placeholder="Search by name" name="name_project" class="form-control name_list" minlength="5" maxlength="45"/></td>
                        <td>
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                <label class="btn btn-secondary active">
                                    <input type="radio" name="project_name_search_option_1" id="option1" value="option_1" checked> EXACT
                                </label>
                                <label class="btn btn-secondary">
                                    <input type="radio" name="project_name_search_option_2" id="option2" value="option_2"> APPROXIMATE
                                </label>
                            </div>
                        </td>
                    </tr> 
                    <tr>
                    <td>
                        <input type="text" placeholder="Search by description" name="description_project" class="form-control name_list" maxlength="255"/></td>
                    <td>
                        <div class="btn-group btn-group-toggle" data-toggle="buttons">
                            <label class="btn btn-secondary active">
                                <input type="radio" name="project_description_search_option_1" id="search_description_option_1" value="option_1" checked> EXACT
                            </label>
                            <label class="btn btn-secondary">
                                <input type="radio" name="project_description_search_option_2" id="search_description_option_2" value="option_2"> APPROXIMATE
                            </label>
                        </div>
                    </td>
                </tr>
            </table>  
        </div>
        <div id="content_wrapper_3">
            <h1>SEARCH BY SEMESTER</h1>
            <?php
                $query = $dbh->prepare("SELECT `id`, `season`, `year` FROM semester");
                $query->execute();
                echo '<select name="selected_semester" class="selectpicker" custom-select">';
                echo '<option disabled selected value>select semester</option>';
                        foreach($query->fetchAll(PDO::FETCH_NUM) as $result){ 
                            echo '<option value="' . $result[0] . '">' . $result[1] . " " . $result[2] . '</option>';
                        }
                echo "</select>";
            ?>
        </div>
        <div id="content_wrapper_2">
            <div>
                <div>
                    <h1>SEARCH BY GLOBAL GOALS</h1>
                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                        <label class="btn btn-secondary active">
                            <input type="radio" name="sdg_search_option_1" id="option1" value="option_1"> EXACT
                        </label>
                        <label class="btn btn-secondary">
                            <input type="radio" name="sdg_search_option_2" id="option2" value="option_2" checked> APPROXIMATE
                        </label>
                    </div>
                </div>
            </div>
                <?php
                    $query = $dbh->prepare("SELECT position, title FROM SDG");
                    $query->execute(); //EXECUTE QUERY.

                    //SDG IMAGE LOCATION
                    $sdg_image_dir = 'resources/images/SDGs/'; //SDG IMAGE DIRECTORY PATH
                    $images = scandir($sdg_image_dir); //SCANN THE SDG IMAGE DIRECTORY
                    sort($images, SORT_NUMERIC); //SORT IMAGES INTO THE NATURAL ORDER FROM 1 TO 17

                    //SHOW THE SDGs ON THE SCREEN
                    echo '<div class="flex-container">';
                    $i = 1;
                    foreach($query->fetchAll(PDO::FETCH_NUM) as $result){ 
                        ++$i;
                        echo "<div>";
                        // echo '<input type="checkbox" name="selected_sdg[]" id="' . $result[1] . '" value="' . $result[0] . '">' . $result[0] . ". " . $result[1] . '</input>';
                        echo '<input type="checkbox" name="selected_sdg[]" id="' . $result[1] . '" value="' . $result[0] . '"/>';
                        // echo '<br>';
                        echo '<label class="sdg_label" for="' . $result[1] . '"><img src="' . $sdg_image_dir . $images[$i] . '"/>';
                        echo "</div>";
                    }
                    echo "</div>";
                ?>
            </div>

            <!-- //SHOW THE SDGs ON THE SCREEN
                    echo "<ul>";
                    $i = 1;
                    foreach($query->fetchAll(PDO::FETCH_NUM) as $result){ 
                        ++$i;
                        echo "<li>";
                        // echo '<input type="checkbox" name="selected_sdg[]" id="' . $result[1] . '" value="' . $result[0] . '">' . $result[0] . ". " . $result[1] . '</input>';
                        echo '<input type="checkbox" name="selected_sdg[]" id="' . $result[1] . '" value="' . $result[0] . '"/>';
                        // echo '<br>';
                        echo '<label class="sdg_label" for="' . $result[1] . '"><img src="' . $sdg_image_dir . $images[$i] . '"/>';
                        echo "</li>";
                    }
                    echo "</ul>"; -->

            <!-- SEARCH BY TRACK -->
            <div id="content_wrapper_3">
                <?php
                    echo "<h1>SEARCH BY TRACK</h1>";
                    $query = $dbh->prepare("SELECT `id`, `name` FROM track");
                    $query->execute();
                    echo '<select name="selected_track" class="selectpicker" custom-select">';
                    echo '<option disabled selected value>select track</option>';
                            foreach($query->fetchAll(PDO::FETCH_NUM) as $result){ 
                                echo '<option value="' . $result[0] . '">' . $result[1] . '</option>';
                            }
                    echo "</select>";
                ?>
            </div>


            <!-- SEARCH BY SPRINT -->
            <div id="content_wrapper_4">
                <?php
                    // SELECT SPRINT STATE
                    echo "<h1>SEARCH BY SPRINT</h1>";
                    $query = $dbh->prepare("SELECT `id`, `number` FROM sprint_numbers");
                    $query->execute(); //EXECUTE QUERY.
                    echo '<select name="current_sprint" class="selectpicker" data-live-search="true">'; 
                    echo '<option disabled selected value>select current sprint</option>';
                            foreach($query->fetchAll(PDO::FETCH_NUM) as $sprint_number){ 
                                echo '<option data-tokens="' . $sprint_phase[1] . '" value="' . $sprint_number[0] . '">' . $sprint_number[1] . '</option>';
                            }
                    echo "</select>";
                    echo " ";
                    //SPRINT STATE (OPTIONS MENU)
                    $query = $dbh->prepare("SELECT `id`, `option` FROM sprint_options"); 
                    $query->execute(); //EXECUTE QUERY.
                    echo '<select name="sprint_phase" class="selectpicker" data-live-search="true">';
                    echo '<option disabled selected value>select sprint option</option>';
                            foreach($query->fetchAll(PDO::FETCH_NUM) as $sprint_phase){ 
                                echo '<option data-tokens="' . $sprint_phase[1] . '" value="' . $sprint_phase[0] . '">' . $sprint_phase[1] . '</option>';
                            }
                    echo "</select>";
                ?>
            </div>

            <!-- SEARCH BY RESOURCES -->
            <div id="content_wrapper_5">
                <h1>SEARCH BY RESOURCE</h1>
                <table class="table table-bordered" id="dynamic_field">  
                    <tr>  
                        <td><input type="text" placeholder="Search by name" name="name_resource" maxlength="500" class="form-control name_list"></td>
                        <td>
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                <label class="btn btn-secondary active">
                                    <input type="radio" value="option_1" name="resource_name_search_option_1" id="option1" value="option_1" checked> EXACT
                                </label>
                                <label class="btn btn-secondary">
                                    <input type="radio" value="option_2" name="resource_name_search_option_2" id="option2" value="option_2"> APPROXIMATE
                                </label>
                            </div>
                        </td>
                    </tr> 
                    <tr>
                        <td><input type="text" placeholder="Search by description" name="description_resource" class="form-control name_list" maxlength="255"/></td>
                        <td>
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                <label class="btn btn-secondary active">
                                    <input type="radio" name="resource_description_search_option_1" id="option1" value="option_1" checked> EXACT
                                </label>
                                <label class="btn btn-secondary">
                                    <input type="radio" name="resource_description_search_option_2" id="option2" value="option_2"> APPROXIMATE
                                </label>
                            </div>
                        </td>
                    </tr>
                </table>  
            </div>
        <div class="content_wrapper_0">
            <button class="btn btn-primary" type="submit" value="SUBMIT" name="submit">Submit</button>
        </div>
    </form> <!-- END OF USER FORM -->
<footer>
    <p>By submitting your files to Digital Society School, you acknowledge that you agree to Digital Society School's <a href="https://digitalsocietyschool.shop/terms-of-service">Terms of Service</a>.</p>
        <br>
    <p>Please be sure not to violate others' copyright or privacy rights. <a href="https://digitalsocietyschool.org/about/">Learn more</a></p>
</footer>

<!-- GETBOOTSTRAP (STOCK BOOTSTRAP) -->
<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

<!-- MD BOOTSTRAP (MATERIAL DESIGN BOOTSTRAP) -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.4/umd/popper.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdbootstrap/4.10.1/js/mdb.min.js"></script>
</body>
</html>