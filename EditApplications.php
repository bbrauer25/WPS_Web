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
  
  //initialize database
  $myConnection = new mysqli("oniddb.cws.oregonstate.edu", "brauerr-db", $myPassword, "brauerr-db");
  if ($myConnection->connect_errno) {
    echo "Failed to connect to MySQL: (" . $myConnection->connect_errno . ") " . $myConnection->connect_error;
  }
  
  if(isset($_POST['updateApplication'])) {
    //first delete all records from chemical_application_record table
    $stmt = $myConnection->prepare("DELETE FROM chemical_application_record WHERE fk_application_record_id = ?");
    $stmt->bind_param("i", $_POST['ar_id']);
    $stmt->execute();
    $stmt->close();
     
    //second update application record,
    $stmt = $myConnection->prepare("UPDATE application_record SET application_datetime = ?, wind_speed = ?, wind_direction = ?,
        temperature = ?, application_method = ?, acres_applied = ?, comments = ? WHERE application_record_id = ?");
    $stmt->bind_param("sdddsdsi", $_POST['applicationDate'], $_POST['windSpeed'], $_POST['windDirection'], $_POST['temperature'], $_POST['applicationMethod'],
        $_POST['acres'], $_POST['comments'], $_POST['ar_id']);
    $stmt->execute();
    $stmt->close();
    
    //lastly, create new records in car table
    $stmt = $myConnection->prepare("INSERT INTO chemical_application_record(fk_chemical_id, fk_application_record_id, rate_applied)
        VALUES(?,?,?)");
    $stmt->bind_param("iid", $_POST['chemicalOneId'], $_POST['ar_id'], $_POST['rateOne']);
    $stmt->execute();
    $stmt->close();
    
    if ($_POST['rateTwo'] > 0) {
      $chemicalTwoId = $_POST['chemicalTwoId'];
      $rateTwo = $_POST['rateTwo'];
      
      $stmt = $myConnection->prepare("INSERT INTO chemical_application_record(fk_chemical_id, fk_application_record_id, rate_applied) VALUES(?,?,?)");
      $stmt->bind_param("iid", $chemicalTwoId, $_POST['ar_id'], $rateTwo);
      $stmt->execute();
      $stmt->close();
    }
    
    if ($_POST['rateThree'] > 0) {
      $chemicalThreeId = $_POST['chemicalThreeId'];
      $rateThree = $_POST['rateThree'];
      
      $stmt = $myConnection->prepare("INSERT INTO chemical_application_record(fk_chemical_id, fk_application_record_id, rate_applied) VALUES(?,?,?)");
      $stmt->bind_param("iid", $chemicalThreeId, $_POST['ar_id'], $rateThree);
      $stmt->execute();
      $stmt->close();
    }
  }
  
?>

<!DOCTYPE html>
<html
  <head>
    <meta charset="utf-8">
    <title>Edit Application</title>
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
    
    <div class="container">
      <div class="col-md-6" id="oldData">
        <h2 class="form-signin-heading">Current Application Data</h2>
        <?php
        
          if (!($stmt = $myConnection->prepare("SELECT ar.wind_speed, ar.wind_direction, ar.application_method, ar.acres_applied, ar.comments,
              ar.temperature, ar.application_datetime, c.product_name, car.rate_applied, f.field_name FROM application_record ar
              INNER JOIN chemical_application_record car ON car.fk_application_record_id = ar.application_record_id
              INNER JOIN chemical c ON c.chemical_id = car.fk_chemical_id
              INNER JOIN field f ON f.field_id = ar.fk_field_id
              WHERE ar.application_record_id = ?"))) {
            echo "Prepare failed: (" . $myConnection->errno . ") " . $myConnection->error;
          }
          if(!$stmt->bind_param("i", $_POST['ar_id'])) {
            echo "Binding parameters failed: (" . $myConnection->errno . ") " . $myConnection->error;
          }
          if (!$stmt->execute()) {
          echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
          }
          if (!$stmt->bind_result($wind_speed, $wind_direction, $application_method, $acres_applied, 
            $comments, $temperature, $application_datetime, $product_name, $rate_applied, $field_name)) {
            echo "Binding results failed: (" . $stmt->errno . ") " . $stmt->error;
          }
          //set up main data
          $stmt->fetch();
          echo "<p>Field Name: {$field_name}</p>";
          echo "<p>Application Date Time: {$application_datetime}</p>";
          echo "<p>Acres: {$acres_applied}</p>";
          echo "<p>Wind Speed: {$wind_speed}</p>";
          echo "<p>Wind Direction: {$wind_direction}</p>";
          echo "<p>Temperature: {$temperature}</p>";
          echo "<p>Application Method: {$application_method}</p>";
          echo "<p>Comments: {$comments} </p>";
          echo "<p>Product Name: {$product_name}</p>";
          echo "<p>Rate Applied: {$rate_applied}</p>";
          //add additional chemical data if more than one chem in the application record
          while ($stmt->fetch()) {
            echo "<p>Product Name: {$product_name}</p>";
            echo "<p>Rate Applied: {$rate_applied}</p>";
          }
          $stmt->close();  
        ?>
      </div>
      <div class="col-md-6" id="newData">
        <form class="form" role="form">
          <h2 class="form-signin-heading">Update Application:</h2>
          <div class="form-group">
            <label for="applicationDate">Date and Time of Application</label>
            <input class="form-control" type="datetime-local" id="applicationDate">
          </div>
          <div class="form-group">
            <label for="acres">Acres</label>
            <input class="form-control" type="number" id="acres">
          </div>
          <div class="form-group">
            <label for="windSpeed">Wind Speed:</label>
            <input class="form-control" type="number" id="windSpeed">
          </div>
          <div class="form-group">
            <label for="windDirection">Wind Direction</label>
            <input class="form-control" type="number" id="windDirection">
          </div>
          <div class="form-group">
            <label for="temperature">Temperature</label>
            <input class="form-control" type="number" id="temperature">
          </div>
          <div class="form-group">
            <label for="applicationMethod">Application Method</label>
            <input class="form-control" type="text" id="applicationMethod">
          </div>
          <div class="form-group">
            <label for="comments">Comments</label>
            <input class="form-control" type="text" id="comments">
          </div>
          <div class="form-inline">
            <label for="chemicalNameOne">Chemical Name</label>
            <select class="form-control" id="chemicalNameOne">
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
          <button class="btn btn-lg btn-primary btn-block" type="button" id="updateApplication" hidefocus="true">Update Application Record</button>
          <button class="btn btn-lg btn-block" type="button" id="deleteApplication">Delete Application Record</button>
        </form>
      </div>
    </div>
    
    <script>

    $(document).ready(function() {
        $('#updateApplication').click(function(){
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
             editApplication();
          }
        });
        
        $('#deleteApplication').click(function(){
          deleteApplication();
        });
      });    
       
      function editApplication() {
        //if edit, post to edit application page, redisplay with updated data for the current record 
        var applicationDate = $('#applicationDate').val();
        var windSpeed = $('#windSpeed').val();
        var windDirection = $('#windDirection').val();
        var temperature = $('#temperature').val();
        var applicationMethod = $('#applicationMethod').val();
        var acres = $('#acres').val();
        var comments = $('#comments').val();
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
        var arId = <?php echo $_POST['ar_id']; ?>;
        $.post("EditApplications.php", {applicationDate : applicationDate, windSpeed: windSpeed, windDirection : windDirection,
            temperature : temperature, applicationMethod : applicationMethod, acres : acres, comments : comments, chemicalOneId : chemicalOneId, rateOne : rateOne,
            chemicalTwoId : chemicalTwoId, rateTwo : rateTwo, chemicalThreeId : chemicalThreeId, rateThree : rateThree, ar_id : arId, updateApplication : true}, function () {
                window.location.href = "ManageApplications.php";
              }
            );
      }
      
      //if delete, then redirect to manage applications page
      function deleteApplication() {
        var arId = <?php echo $_POST['ar_id']; ?>;
        $.post("ManageApplications.php", {ar_id : arId, deleteApplication : true}, function () {
          window.location.href = "ManageApplications.php";
        });
      }

    </script>
    
  </body>
</html>