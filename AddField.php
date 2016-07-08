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
  
  $myConnection = new mysqli("oniddb.cws.oregonstate.edu", "brauerr-db", $myPassword, "brauerr-db");
  $organization = $_SESSION['organization_id'];
  //$organization = 2;
  if ($myConnection->connect_errno) {
    echo "Failed to connect to MySQL: (" . $myConnection->connect_errno . ") " . $myConnection->connect_error;
  }
  
  if (isset($_POST['addField'])) {
    $stmt = $myConnection->prepare("INSERT INTO field(field_name, latitude, longitude, acres, fk_organization_id) VALUES(?,?,?,?,?)");
    $stmt->bind_param("sdddi", $_POST['fieldName'], $_POST['latitude'], $_POST['longitude'], $_POST['acres'], $organization);
    $stmt->execute();
    $stmt->close();
  }
?>

<!DOCTYPE html>
<html
  <head>
    <meta charset="utf-8">
    <title>Add Field</title>
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
    
    <div class ="container">
      <form action="AddField.php" method="POST" class="form">
        <h2 class="form-signin-heading">Add Field:</h2>
          <label for="fieldName">Field Name: </label>
          <input class="form-control" type="text" id="fieldName" name="fieldName">
          <label for="latitude">Latitude:</label>
          <input class="form-control" type="text" id="latitude" name="latitude">
          <label for="longitude">Longitude:</label>
          <input class="form-control" type="text" id="longitude" name="longitude">
          <label for="acres">Acres:</label>
          <input class="form-control" type="text" id="acres" name="acres">
        <input class="btn btn-lg" type="submit" name="addField" value="Add Field">
      </form>
    </div>
    <br>
    <br>

    <div class="container">
      <fieldset style="width:90% text-align:center">
        <legend>Field List:</legend>
        <table class="table table-striped" style="padding:5px">
          <thead>
            <tr>
            <th>Field Name
            <th>latitude
            <th>longitude
            <th>acres
          </thead>
          <tbody>
        <?php
            if (!($stmt = $myConnection->prepare("SELECT field_name, latitude, longitude, acres FROM field WHERE fk_organization_id = ?"))) {
              echo "Prepare failed: (" . $myConnection->errno . ") " . $myConnection->error;
            }
            $stmt->bind_param("i", $organization);
            if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            }
            if (!$stmt->bind_result($fieldName, $latitude, $longitude, $acres)) {
              echo "Binding results failed: (" . $stmt->errno . ") " . $stmt->error;
            }
            while ($stmt->fetch()) {
              echo "<tr><td>{$fieldName}<td>{$latitude}<td>{$longitude}<td>{$acres}";
            }
            $stmt->close();  
            ?>
          </tbody>
        </table>
      </fieldset>
    </div>
    
  </body>
</html>