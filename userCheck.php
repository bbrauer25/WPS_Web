<?php

  //inspiration taken from:
  //http://www.sanwebe.com/2013/04/username-live-check-using-ajax-php
  
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  include 'storedInfo.php'; //for storing password and other secure data
  
  $myConnection = new mysqli("oniddb.cws.oregonstate.edu", "brauerr-db", $myPassword, "brauerr-db");
  if ($myConnection->connect_errno) {
    echo "Failed to connect to MySQL: (" . $myConnection->connect_errno . ") " . $myConnection->connect_error;
  } 
  
  //code to handle user check login request
  if(isset($_POST['inputUsername'])) {
    //ensure this is an ajax request
    if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
      die();
    }
    
    //sanitize username and password passed via post
    $username = trim($_POST["inputUsername"]);
    $username = filter_var($username, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
    $password = trim($_POST["inputPassword"]);
    $password = filter_var($password, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
    
    //return user data with organization id from foreign key
    if (!($myUsers = $myConnection->query("SELECT username, password, applicator_id, fname, lname, fk_organization_id FROM applicator WHERE username = '$username' AND password = '$password'"))) {
      echo "Query failed<br>";
    }

    $rowCount = $myUsers->num_rows;
    $data = $myUsers->fetch_array(MYSQLI_ASSOC);
    
    //start session - with user id and organization id
    if ($rowCount == 1) {
      session_start();
      $_SESSION['username'] = $data["username"];
      $_SESSION['applicator_id'] = $data["applicator_id"];
      $_SESSION['name'] = $data["fname"] . " " . $data["lname"];
      $_SESSION['organization_id'] = $data["fk_organization_id"];
    }
    echo $rowCount; //should be 1
    $myConnection->close();
  }
  
  //code to handle create user request
  if(isset($_POST['newUsername'])) {
    $username = $_POST['newUsername'];
    $email = $_POST['newEmail'];
    $fname = $_POST['fName'];
    $lname = $_POST['lName'];
    $licenseNumber = $_POST['licenseNumber'];
    $licenseExpiration = $_POST['licenseExpiration'];
    $password = $_POST['newPassword'];
    $organizationId = $_POST['organizationId'];
    
    if(!($stmt = $myConnection->prepare("INSERT INTO applicator(username, email, fname, lname, license_number, license_expiration, password, fk_organization_id) VALUES (?,?,?,?,?,?,?,?)"))) {
      echo "Prepare failed: (" . $myConnection->errno . ") " . $myConnection->error;
    }
    if(!$stmt->bind_param("sssssssi", $username, $email, $fname, $lname, $licenseNumber, $licenseExpiration, $password, $organizationId)) {
      echo "Binding parameters failed: (" . $myConnection->errno . ") " . $myConnection->error;
    }
    if(!$stmt->execute()) {
      //check for duplicate records
      if ($myConnection->errno == 1062) {
        echo 'duplicate';
      } else {
        echo "Execute failed: (" . $myConnection->errno . ") " . $myConnection->error;
      }
    }
    $stmt->close();
    echo 1;
  } 
  
?>