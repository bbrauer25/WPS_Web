<?php
ini_set('display_errors', 'On');
include 'storedInfo.php'; //for storing password and other secure data
session_start();
?>

<!DOCTYPE html>
<html
  <head>
    <meta charset="utf-8">
    <title>Add Applicator</title>
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
      <form class="form-signin" role="form">
        <h2 class="form-signin-heading">Create a New User:</h2>
        <label for="firstName">First Name</label>
        <input type="text" id="firstName" class="form-control" placeholder="First Name" required>
        <label for="lastName">Last Name</label>
        <input type="text" id="lastName" class="form-control" placeholder="Last Name" required>
        <label for="newEmail">Email</label>
        <input type="email" id="newEmail" class="form-control" placeholder="Your Email" required>
        <label for="newUsername">Username</label>
        <input type="text" id="newUsername" class="form-control" placeholder="New Username" required>
        <label for="newPassword">Password</label>
        <input type="password" id="newPassword" class="form-control" placeholder="New Password" required>
        <label for="confirmPassword">Confirm Password</label>
        <input type="password" id="confirmPassword" class="form-control" placeholder="Confirm Password" required>
        <label for="licenseNumber">Applicator's License Number</label>
        <input type="text" id="licenseNumber" class="form-control" placeholder="License Number" required>
        <label for="licenseExpiration">Applicator's License Expiration Date</label>
        <input type="date" id="licenseExpiration" class="form-control" placeholder="License Expiration Date" required>
        
        <!-- populate organization dropdown from organization table, linked with Id's-->
        <label for="organizationId">Organization</label>
        <select class="form-control" id="organizationId">
        <?php
          $myConnection = new mysqli("oniddb.cws.oregonstate.edu", "brauerr-db", $myPassword, "brauerr-db");
          if ($myConnection->connect_errno) {
            echo "Failed to connect to MySQL: (" . $myConnection->connect_errno . ") " . $myConnection->connect_error;
          }
          if (!($stmt = $myConnection->prepare("SELECT organization_id, name FROM organization"))) {
            echo "Prepare failed: (" . $myConnection->errno . ") " . $myConnection->error;
          }
          if (!$stmt->execute()) {
          echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
          }
          if (!$stmt->bind_result($organization_id, $name)) {
            echo "Binding results failed: (" . $stmt->errno . ") " . $stmt->error;
          }
          while ($stmt->fetch()) {
            echo "<option value=\"{$organization_id}\">{$name}</option>";
          }
          $stmt->close(); 
        ?>
        </select>
        <br>
        <!--<input type="text" id="organizationId" class="form-control" required>-->
        <button class="btn btn-lg btn-primary btn-block" type="button" id="createUser" hidefocus="true">Create New Applicator Account</button>
        <span id="createuserMessage"></span><br>
        <a href="Login.php">Login</a>
      </form>
    </div>
    
    <script>
    $(document).ready(function() {
        $('#createUser').click(function(){
          
          //check email format (from http://www.w3schools.com/js/js_form_validation.asp)
          var email = $('#newEmail').val();
          var atpos = email.indexOf('@');
          var dotpos = email.indexOf('.');
          
          //ensure all fields are filled in
          if ($('#newUsername').val().length < 1) {
            $('#createuserMessage').html('Please enter a username');
          } else if ($('#newPassword').val().length < 1) {
            $('#createuserMessage').html('Please enter a password');
          } else if ($('#lastName').val().length < 1) {
            $('#createuserMessage').html('Please enter your last name');
          } else if ($('#licenseNumber').val().length < 1) {
            $('#createuserMessage').html('Please enter your license number');
          } else if ($('#licenseExpiration').val().length < 1) {
            $('#createuserMessage').html('Please enter a license expiration date');
          } else if ($('#firstName').val().length < 1) {
            $('#createuserMessage').html('Please enter your first name');
          } else if ($('#newEmail').val().length < 1) {
            $('#createuserMessage').html('Please enter your email');
          }
          //ensure email is in email format
          else if (atpos < 1 || dotpos + 2 >= email.length) {
            $('#createuserMessage').html('Ensure email is in correct format');
          }
          //make sure passwords match
          else if ($('#newPassword').val() != $('#confirmPassword').val()) {
            $('#createuserMessage').html('Passwords do not match');
          } else {
             createUser();
          }
        });
      });
      
      function createUser() {
        var username = $('#newUsername').val();
        var password = $('#newPassword').val();
        var email = $('#newEmail').val();
        var fname = $('#firstName').val();
        var lname = $('#lastName').val();
        var licenseNumber = $('#licenseNumber').val();
        var licenseExpiration = $('#licenseExpiration').val();
        var organizationId = $('#organizationId').val();
        $.post("userCheck.php", {newUsername : username, newPassword : password, newEmail : email, fName : fname,
             lName : lname, licenseNumber : licenseNumber, licenseExpiration : licenseExpiration, organizationId : organizationId}, function(result) {
          if (result == 1) {
            $('#createuserMessage').html('New account succesfully created, please login');
          } else if (result == 'duplicate1') {
            $('#createuserMessage').html('Sorry, someone already has that username');
          } else {
            $('#createuserMessage').html('Create user failed');
          }
        });
      }
      
      //enable enter to login after filling in password box
      $("#newPassword").keyup(function(event){
        if(event.keyCode == 13){
          $("#createUser").click();
        }
      });
    </script>
  
  </body>
</html>