<?php
// session_unset(); //UNSERT PREVIOUS SESSION (IF ANY)
// session_start(); //START A NEW SESSION
// $_SESSION['id'] = session_id();

//DATABASE CONNECTION
require 'php/class/db_connection.php';
$database_connection = new DatabaseConnection();
$dbh = $database_connection->connection();
?> <!-- PAUSE PHP CODE -->

<!DOCTYPE html> 
<html>
<head>
    <title>Update Existing Project | DSS</title>
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
        <form action="php/update_existing_project.php" method="POST" id="user_form" enctype="multipart/form-data">
            <!-- SEARCH BY TRACK -->
            <div id="content_wrapper_3">
                <h1>SELECT YOUR PROJECT</h1>
                <?php
                    $query = $dbh->prepare("SELECT project.id, project.name, semester.season, semester.year 
                                            FROM project INNER JOIN semester ON project.semester_id = semester.id");
                    $query->execute();
                    echo '<select name="selected_project" class="selectpicker" custom-select">';
                    echo '<option disabled selected value>select project</option>';
                            foreach($query->fetchAll(PDO::FETCH_ASSOC) as $result){ 
                                echo '<option value="' . $result['id'] . '">' . $result['name'] . " | " . $result['season'] . " " . $result['year'] . '</option>';
                            }
                    echo "</select>";
                ?>
            </div>
            <!-- SEARCH BY SPRINT -->
            <div id="content_wrapper_4">
              <h1>SELECT SPRINT PLAN</h1>
                <?php
                    //SELECT SPRINT NUMBER
                    $query = $dbh->prepare("SELECT `id`, `number` FROM sprint_numbers");
                    $query->execute(); //EXECUTE QUERY.
                    echo '<select name="current_sprint" class="selectpicker" data-live-search="true">'; 
                    echo '<option disabled selected value>select current sprint</option>';
                            foreach($query->fetchAll(PDO::FETCH_NUM) as $sprint_number){ 
                                echo '<option data-tokens="' . $sprint_number[1] . '" value="' . $sprint_number[0] . '">' . $sprint_number[1] . '</option>';
                            }
                    echo "</select>";
                    echo " ";
                    //SELECT SPRINT PHASE
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
                <h1>ADD RESOURCES (OPTIONAL) --- currently has no progress bar, please be patient when submitting large files.</h1>
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Upload</button>
                <div class="dropdown-menu">
                    <a class="dropdown-item"><label for="upload_document">File(s)</label></a>
                    <input id="upload_document" type="file" value="SELECT FILE" name="uploaded_file[]" hidden multiple>

                    <a class="dropdown-item"><label for="upload_folder">Folder(s)</label></a>
                    <input id="upload_folder" type="file" value="SELECT FILE" name="uploaded_file[]" hidden webkitdirectory mozdirectory multiple>
                </div>
                <br>
                <input type="text" placeholder="Search by name" name="resource_name" maxlength="500" class="form-control name_list">
                <br>
                <input type="text" placeholder="Search by description" name="resource_description" class="form-control name_list" maxlength="255">
                <br>
                    <table class="table table-bordered" id="dynamic_field">  
                        <td>
                            <tr>
                                <td><input type="text" name="resource_URLs[]" placeholder="Add URL(s)..." class="form-control name_list"></td>  
                                <td><button type="button" name="add" id="add" class="btn btn-success">Add More</button></td>  
                            </tr>
                        <td>
                    </table>  
                </div>
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

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdbootstrap/4.10.1/js/mdb.min.js"></script>
<script src="javascript/dynamicallyAddRemoveInputFields.js"></script>
</body>
</html>