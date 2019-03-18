<?php
session_start();
// include the class that handles database connections
require "../prog03/database.php";

// include the class containing functions/methods for "customer" table
// Note: this application uses "customer" table, not "cusotmers" table
require "customer.class.php";
$cust = new Customer();

// set active record field values, if any 
// (field values not set for display_list and display_create_form)
if(isset($_GET["id"]))          $id = $_GET["id"]; 
if(isset($_POST["name"]))       $cust->name = $_POST["name"];
if(isset($_POST["email"]))      $cust->email = $_POST["email"];
if(isset($_POST["mobile"]))     $cust->mobile = $_POST["mobile"];

// "fun" is short for "function" to be invoked 
if(isset($_GET["fun"])) { 
    $fun = $_GET["fun"];
}
else {
    $fun = "login"; 
}

// Decide the form to display absed on the $fun variable
switch ($fun) {
    case "display_list":  
        if(isset($_SESSION['email'])) {
            $cust->list_records();
        }
        else {
            $cust->logout();
        }
        break;
    case "display_create_form": $cust->create_record(); 
        break;
    case "display_read_form":   $cust->read_record($id); 
        break;
    case "display_update_form": $cust->update_record($id);
        break;
    case "display_delete_form": $cust->delete_record($id); 
        break;
    case "insert_db_record":    $cust->insert_db_record(); 
        break;
    case "update_db_record":    $cust->update_db_record($id);
        break;
    case "delete_db_record":    $cust->delete_db_record($id);
        break;
    case "login":               
        if(!isset($_SESSION['email'])) {
            $cust->login();
        }
        else {
            $cust->list_records();
        }
        break;
    case "signup":              
        if(!isset($_SESSION['email'])) {
            $cust->signup();
        }
        else {
            $cust->list_records();
        }
        break;
    case "logout":              $cust->logout();
        break;
    default: 
        echo "Error: Invalid function call (customer.php)";
        exit();
        break;
}