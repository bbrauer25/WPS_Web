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
  
  if (isset($_POST['rei'])) {
    $stmt = $myConnection->prepare("INSERT INTO chemical(product_name, active_ingredients, epa_number, rei) VALUES(?,?,?,?)");
    $stmt->bind_param("sssd", $_POST['productName'], $_POST['activeIngredients'], $_POST['epaNumber'], $_POST['rei']);
    $stmt->execute();
    $stmt->close();
  }
  
?>

<!DOCTYPE html>
<html
  <head>
    <meta charset="utf-8">
    <title>Add Chemical</title>
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
      <form action="AddChemicals.php" method="POST" class="form">
        <h2 class="form-signin-heading">Add Chemical:</h2>
          <label for="epaNumber">EPA Number: </label>
          <input class="form-control" type="text" id="epaNumber" name="epaNumber">
          <label for="active_ingredients">Active Ingredients:</label>
          <input class="form-control" type="text" id="activeIngredients" name="activeIngredients">
          <label for="productName">Product Name:</label>
          <input class="form-control" type="text" id="productName" name="productName">
          <label for="rei">REI (hours):</label>
          <input class="form-control" type="number" id="rei" name="rei">
        <input class="btn btn-lg" type="submit" name="addChemical" value="Add Chemical">
      </form>
    </div>
    <br>
    <br>

    <div class="container">
      <fieldset style="width:90% text-align:center">
        <legend>Chemical List:</legend>
        <table class="table table-striped" style="padding:5px">
          <thead>
            <tr>
            <th>Product Name
            <th>Active Ingredients
            <th>EPA Number
            <th>REI
          </thead>
          <tbody>
        <?php
            if (!($stmt = $myConnection->prepare("SELECT product_name, active_ingredients, epa_number, rei FROM chemical"))) {
              echo "Prepare failed: (" . $myConnection->errno . ") " . $myConnection->error;
            }
            if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            }
            if (!$stmt->bind_result($product_name, $active_ingredients, $epa_number, $rei)) {
              echo "Binding results failed: (" . $stmt->errno . ") " . $stmt->error;
            }
            while ($stmt->fetch()) {
              echo "<tr><td>{$product_name}<td>{$active_ingredients}<td>{$epa_number}<td>{$rei}";
            }
            $stmt->close();  
            ?>
          </tbody>
        </table>
      </fieldset>
    </div>
    
  </body>
</html>