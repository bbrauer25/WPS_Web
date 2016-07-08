<?php
ini_set('display_errors', 'On');
include 'storedInfo.php'; //for storing password and other secure data
session_start();

  //if user not logged in (session not started) redirect to login page
  if (!isset($_SESSION['username'])) {
      $filePath = explode('/', $_SERVER['PHP_SELF'], -1);
      $filePath = implode('/', $filePath);
      $redirect = "http://" . $_SERVER['HTTP_HOST'] . $filePath;
      header("Location: {$redirect}/Login.php");
  }
  
  //initialize database connection
  $myConnection = new mysqli("oniddb.cws.oregonstate.edu", "brauerr-db", $myPassword, "brauerr-db");
  $organization = $_SESSION['organization_id'];
  $applicatorId = $_SESSION['applicator_id'];
  //$organization = 2;
  if ($myConnection->connect_errno) {
    echo "Failed to connect to MySQL: (" . $myConnection->connect_errno . ") " . $myConnection->connect_error;
  }
  
  //if post sent with deleteApplication set, delete the application record (posted from edit application page)
  if (isset($_POST['deleteApplication'])) {
    //first delete from chemical application record
    $stmt = $myConnection->prepare("DELETE FROM chemical_application_record WHERE fk_application_record_id = ?");
    $stmt->bind_param("i", $_POST['ar_id']);
    $stmt->execute();
    $stmt->close();
    
    //second delete from application record
    $stmt = $myConnection->prepare("DELETE FROM application_record WHERE application_record_id = ?");
    $stmt->bind_param("i", $_POST['ar_id']);
    $stmt->execute();
    $stmt->close();
  }

  //if post set with submitted application - update application_record and chemical_application_record tables
  if (isset($_POST['applicationDate'])) {
    //first create application record
    $applicationDate = $_POST['applicationDate'];
    $fieldId = $_POST['fieldId'];
    $windSpeed = $_POST['windSpeed'];
    $windDirection = $_POST['windDirection'];
    $temperature = $_POST['temperature'];
    $applicationMethod = $_POST['applicationMethod'];
    $acres = $_POST['acres'];
    $comments = $_POST['comments'];
    
    $stmt = $myConnection->prepare("INSERT INTO application_record(wind_speed, wind_direction, application_method, acres_applied,
        comments, application_datetime, temperature, fk_field_id, fk_applicator_id) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ddsdssdii", $windSpeed, $windDirection, $applicationMethod, $acres, $comments, $applicationDate, $temperature, $fieldId, $applicatorId);
    $stmt->execute();
    $ar_id = $myConnection->insert_id;
    $stmt->close();
    
    //second create chemical application record(s)
    $chemicalOneId = $_POST['chemicalOneId'];
    $rateOne = $_POST['rateOne'];
    
    $stmt = $myConnection->prepare("INSERT INTO chemical_application_record(fk_chemical_id, fk_application_record_id, rate_applied) VALUES(?,?,?)");
    $stmt->bind_param("iid", $chemicalOneId, $ar_id, $rateOne);
    $stmt->execute();
    $stmt->close();
    
    //if ratetwo is set, add a second chemical application record
    if ($_POST['rateTwo'] > 0) {
      $chemicalTwoId = $_POST['chemicalTwoId'];
      $rateTwo = $_POST['rateTwo'];
      
      $stmt = $myConnection->prepare("INSERT INTO chemical_application_record(fk_chemical_id, fk_application_record_id, rate_applied) VALUES(?,?,?)");
      $stmt->bind_param("iid", $chemicalTwoId, $ar_id, $rateTwo);
      $stmt->execute();
      $stmt->close();
    }
    
    //if ratethree is set, add a third chemical application record
    if ($_POST['rateThree'] > 0) {
      $chemicalThreeId = $_POST['chemicalThreeId'];
      $rateThree = $_POST['rateThree'];
      
      $stmt = $myConnection->prepare("INSERT INTO chemical_application_record(fk_chemical_id, fk_application_record_id, rate_applied) VALUES(?,?,?)");
      $stmt->bind_param("iid", $chemicalThreeId, $ar_id, $rateThree);
      $stmt->execute();
      $stmt->close();
    }
  }
  
?>

<!DOCTYPE html>
<html
  <head>
    <meta charset="utf-8">
    <title>Manage Applications</title>
    <link rel="stylesheet" href="style.css">
    
    <!-- Bootstrap core CSS -->
    <link href="dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <script src="dist/js/bootstrap.min.js"></script>
    
  </head>
  <body>
  
    <!--Top Nav Bar - identical for all pages-->
    <nav class="navbar navbar-default" role="navigation">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">WPS Web</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li class="active"><a href="ViewREI.php">View REI</a></li>
            <li><a href="ManageApplications.php">Manage Applications</a></li>
            <li class="dropdown">
              <a href="" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                Manage Data <span class="caret"></span>
              </a>
              <ul class="dropdown-menu" role="menu">
                <li><a href="AddApplicator.php">Add Applicator</a></li>
                <li><a href="AddField.php">Add Field</a></li>
                <li><a href="AddOrganization.php">Add Organization</a></li>
                <li><a href="AddChemicals.php">Add Chemicals</a></li>
              </ul>
            </li>
            <li><a href="Login.php?logout=true">Logout</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>
    
    <!--form to add an application (linked to applicator and org)-->
    <div class="container">
      <form class="form" role="form">
        <h2 class="form-signin-heading">Create a New Application:</h2>
        <div class="form-group">
          <label for="applicationDate">Date and Time of Application</label>
          <input class="form-control" type="datetime-local" id="applicationDate">
        </div>
        <!--add select drop down for field-->
        <div class="form-group">
          <label for="fieldName">Field Name</label>
          <select class="form-control" id="fieldName">
            <?php
              if (!($stmt = $myConnection->prepare("SELECT f.field_id, f.field_name FROM field f
                  INNER JOIN organization o ON o.organization_id = f.fk_organization_id
                  WHERE o.organization_id = ?"))) {
                echo "Prepare failed: (" . $myConnection->errno . ") " . $myConnection->error;
              }
              if(!$stmt->bind_param("i", $organization)) {
                echo "Binding parameters failed: (" . $myConnection->errno . ") " . $myConnection->error;
              }
              if (!$stmt->execute()) {
              echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
              }
              if (!$stmt->bind_result($field_id, $field_name)) {
                echo "Binding results failed: (" . $stmt->errno . ") " . $stmt->error;
              }
              while ($stmt->fetch()) {
                echo "<option value=\"{$field_id}\">{$field_name}</option>";
              }
              $stmt->close();  
            ?>
          </select>
        </div>
        <div class="form-group">
          <label for="windSpeed">Wind Speed (mph)</label>
          <input class="form-control" type="text" id="windSpeed">
        </div>
        <div class="form-group">
          <label for="windDirection">Wind Direction (degrees 0-360)</label>
          <input class="form-control" type="text" id="windDirection">
        </div>
        <div class="form-group">
          <label for="temperature">Temperature (degrees F)</label>
          <input class="form-control" type="text" id="temperature">
        </div>
        <div class="form-group">
          <label for="applicationMethod">Application Method</label>
          <input class="form-control" type="text" id="applicationMethod">
        </div>
        <div class="form-group">
          <label for="acres">Acres</label>
          <input class="form-control" type="number" id="acres">
        </div>
        <div class="form-group">
          <label for="comments">Comments</label>
          <input class="form-control" type="text" id="comments">
        </div>
        <div class="form-inline">
          <label for="chemicalNameOne">Chemical Name</label>
          <select class="form-control" id="chemicalNameOne">
            <option value="0"></option>
            <?php
              if (!($stmt = $myConnection->prepare("SELECT chemical_id, product_name FROM chemical"))) {
                echo "Prepare failed: (" . $myConnection->errno . ") " . $myConnection->error;
              }
              if (!$stmt->execute()) {
              echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
              }
              if (!$stmt->bind_result($chemical_id, $product_name)) {
                echo "Binding results failed: (" . $stmt->errno . ") " . $stmt->error;
              }
              while ($stmt->fetch()) {
                echo "<option value=\"{$chemical_id}\">{$product_name}</option>";
              }
              $stmt->close(); 
            ?>
          </select>          
          <label for="rateOne">Rate</label>
          <input class="form-control" type="text" id="rateOne">
        </div>  
        <div class="form-inline">
          <label for="chemicalNameTwo">Chemical Name</label>
          <select class="form-control" id="chemicalNameTwo">
            <option value="0"></option>
            <?php
              if (!($stmt = $myConnection->prepare("SELECT chemical_id, product_name FROM chemical"))) {
                echo "Prepare failed: (" . $myConnection->errno . ") " . $myConnection->error;
              }
              if (!$stmt->execute()) {
              echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
              }
              if (!$stmt->bind_result($chemical_id, $product_name)) {
                echo "Binding results failed: (" . $stmt->errno . ") " . $stmt->error;
              }
              while ($stmt->fetch()) {
                echo "<option value=\"{$chemical_id}\">{$product_name}</option>";
              }
              $stmt->close(); 
            ?>
          </select>          
          <label for="rateTwo">Rate</label>
          <input class="form-control" type="text" id="rateTwo">
        </div>  
        <div class="form-inline">
          <label for="chemicalNameThree">Chemical Name</label>
          <select class="form-control" id="chemicalNameThree">
            <option value="0"></option>
            <?php
              if (!($stmt = $myConnection->prepare("SELECT chemical_id, product_name FROM chemical"))) {
                echo "Prepare failed: (" . $myConnection->errno . ") " . $myConnection->error;
              }
              if (!$stmt->execute()) {
              echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
              }
              if (!$stmt->bind_result($chemical_id, $product_name)) {
                echo "Binding results failed: (" . $stmt->errno . ") " . $stmt->error;
              }
              while ($stmt->fetch()) {
                echo "<option value=\"{$chemical_id}\">{$product_name}</option>";
              }
              $stmt->close(); 
            ?>
          </select>          
          <label for="rateThree">Rate</label>
          <input class="form-control" type="text" id="rateThree">
        </div>  
        <br>
        <button class="btn btn-lg btn-primary btn-block" type="button" id="createApplication" hidefocus="true">Create New Application Record</button>
        <span id="createApplicationMessage"></span><br>
      </form>
    </div>
    <br><br><br>
    
    <!--jquery script to listen to createapplication button click and create new record by posting to manageapplications.php-->
    <script>
    $(document).ready(function() {
        $('#createApplication').click(function(){
          //createApplication();
          //ensure all fields are properly filled in
          if ($('#applicationDate').val().length < 1) {
            $('#createApplicationMessage').html('Please enter the application date and time');
          } else if ($('#windSpeed').val().length < 1) {
            $('#createApplicationMessage').html('Please enter the wind speed');
          } else if ($('#windDirection').val().length < 1) {
            $('#createApplicatoinMessage').html('Please enter the wind direction');
          } else if ($('#temperature').val().length < 1) {
            $('#createApplicationMessage').html('Please enter the temperature');
          } else if ($('#applicationMethod').val().length < 1) {
            $('#createApplicationMessage').html('Please enter an application method');
          } else if ($('#acres').val().length < 1) {
            $('#createApplicationMessage').html('Please enter the number of acres');
          } else if ($('#rateOne').val().length < 1) {
            $('#createApplicationMessage').html('Please select at least one chemical and enter the application rate');
          } else {
             createApplication();
          }
        });
      });
      
      function createApplication() {
        var applicationDate = $('#applicationDate').val();
        var fieldName = $('#fieldName').val();
        var windSpeed = $('#windSpeed').val();
        var windDirection = $('#windDirection').val();
        var temperature = $('#temperature').val();
        var applicationMethod = $('#applicationMethod').val();
        var acres = $('#acres').val();
        var comments = $('#comments').val();
        var fieldSelect = document.getElementById('fieldName');
        var fieldId = fieldSelect.options[fieldSelect.selectedIndex].value;
        //post the id's of the chemicals from the select boxes
        //when processing, if rate is null, ignore the creation of an additional record
        var chemicalOneSelect = document.getElementById('chemicalNameOne');
        var chemicalOneId = chemicalOneSelect.options[chemicalOneSelect.selectedIndex].value;
        var rateOne = $('#rateOne').val();
        var chemicalTwoSelect = document.getElementById('chemicalNameTwo');
        var chemicalTwoId = chemicalTwoSelect.options[chemicalTwoSelect.selectedIndex].value;
        var rateTwo = $('#rateTwo').val();
        var chemicalThreeSelect = document.getElementById('chemicalNameThree');
        var chemicalThreeId = chemicalThreeSelect.options[chemicalThreeSelect.selectedIndex].value;
        var rateThree = $('#rateThree').val();
        $.post("ManageApplications.php", {applicationDate : applicationDate, fieldId : fieldId, windSpeed: windSpeed, windDirection : windDirection,
            temperature : temperature, applicationMethod : applicationMethod, acres : acres, comments : comments, chemicalOneId : chemicalOneId, rateOne : rateOne,
            chemicalTwoId : chemicalTwoId, rateTwo : rateTwo, chemicalThreeId : chemicalThreeId, rateThree : rateThree}, function () {
                window.location.href = "ManageApplications.php";
              }
            );
      }
    </script>
    
    
    <!--table with all historical appllications, sorted by application date, with a view/edit button-->
    <div class="container">
      <fieldset style="width:90% text-align:center">
        <legend>Application Record History</legend>
        <table class="table table-striped" style="padding:5px">
          <thead>
            <tr>
            <th>Field
            <th>Chemical Name
            <th>Application Date
          </thead>
          <tbody>
        <?php
            if (!($stmt = $myConnection->prepare("SELECT ar.application_record_id, ar.application_datetime, f.field_name, c.product_name FROM application_record ar
                INNER JOIN chemical_application_record car ON car.fk_application_record_id = ar.application_record_id
                INNER JOIN chemical c ON c.chemical_id = car.fk_chemical_id
                INNER JOIN field f ON f.field_id = ar.fk_field_id
                INNER JOIN organization o ON o.organization_id = f.fk_organization_id
                WHERE o.organization_id = ?
                ORDER BY ar.application_datetime DESC"))) {
              echo "Prepare failed: (" . $myConnection->errno . ") " . $myConnection->error;
            }
            if(!$stmt->bind_param("i", $organization)) {
              echo "Binding parameters failed: (" . $myConnection->errno . ") " . $myConnection->error;
            }
            if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            }
            if (!$stmt->bind_result($ar_id, $application_date, $field, $chemical_name)) {
              echo "Binding results failed: (" . $stmt->errno . ") " . $stmt->error;
            }
            while ($stmt->fetch()) {
              echo "<tr><td>{$field}<td>{$chemical_name}<td>{$application_date}";
              echo "<td>
                <form action=\"EditApplications.php\" method=\"POST\">
                  <input type=\"hidden\" name=\"ar_id\" value=\"{$ar_id}\">
                  <input type=\"submit\" name=\"editProduct\" value=\"View/Edit\">
                </form>";
            }
            $stmt->close();  
            ?>
          </tbody>
        </table>
      </fieldset>
    </div>
  </body>
</html>