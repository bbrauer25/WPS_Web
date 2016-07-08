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
  
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset='utf-8'>
    <title>Central Notification</title>

    <!-- Bootstrap core CSS -->
    <link href="dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <script src="dist/js/bootstrap.min.js"></script>    
    <link rel="stylesheet" href="style.css">
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
    
    <div class="table.responsive">
      <table class="table table-striped" id="rei_table">
        <thead>
          <tr>
          <th>Field
          <th>Chemical Name
          <th>Active Ingredient
          <th>EPA Number
          <th>Application DateTime
          <th>REI
          <th>REI Expiration
        </thead>
        <tbody id="REIEntries">
    
        <?php
          //in future break down into separate java doc, for now just embed jquery, etc.
          
          //retrieve all application records within 30 days after REI expiration (longest REI chemical for each application record)
          //for the organization of the current logged in user
          //sort by application date (most recent)
          //will display one record for each chemistry x application
          
          //$organization = $_SESSION['organization_id'];
          $organization = 2;
          $myConnection = new mysqli("oniddb.cws.oregonstate.edu", "brauerr-db", $myPassword, "brauerr-db");
          if ($myConnection->connect_errno) {
            echo "Failed to connect to MySQL: (" . $myConnection->connect_errno . ") " . $myConnection->connect_error;
          }
          if (!($stmt = $myConnection->prepare("SELECT ar.application_datetime, f.field_name, c.epa_number, c.active_ingredients,
              DATE_ADD(ar.application_datetime, INTERVAL c.rei HOUR) AS rei_exp, c.product_name, c.rei FROM application_record ar
              INNER JOIN chemical_application_record car ON car.fk_application_record_id = ar.application_record_id
              INNER JOIN chemical c ON c.chemical_id = car.fk_chemical_id
              INNER JOIN field f ON f.field_id = ar.fk_field_id
              INNER JOIN organization o ON o.organization_id = f.fk_organization_id
              WHERE o.organization_id = ? AND DATEDIFF(NOW(), DATE_SUB(ar.application_datetime, INTERVAL c.rei HOUR)) < 30
              ORDER BY rei_exp DESC"))) {
            echo "Prepare failed: (" . $myConnection->errno . ") " . $myConnection->error;
          }
          if(!$stmt->bind_param("i", $organization)) {
            echo "Binding parameters failed: (" . $myConnection->errno . ") " . $myConnection->error;
          }
          if (!$stmt->execute()) {
          echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
          }
          if (!$stmt->bind_result($application_date, $field, $epa_number, $active_ingredient, $rei_exp, $chemical_name, $rei)) {
            echo "Binding results failed: (" . $stmt->errno . ") " . $stmt->error;
          }
          while ($stmt->fetch()) {
            echo "<tr><td>{$field}<td>{$chemical_name}<td>{$active_ingredient}<td>{$epa_number}<td>{$application_date}<td>{$rei}<td>{$rei_exp}";
          }
          $stmt->close();  
        
        ?>
    
        </tbody>
      </table>
    </div>
    
  </body>
</html>