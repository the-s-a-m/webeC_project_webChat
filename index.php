<?php
// checking for minimum PHP version
if (version_compare(PHP_VERSION, '5.3.7', '<')) {
    exit("Sorry, Simple PHP Login does not run on a PHP version smaller than 5.3.7 !");
} else if (version_compare(PHP_VERSION, '5.5.0', '<')) {
    // if you are using PHP 5.3 or PHP 5.4 you have to include the password_api_compatibility_library.php
    // (this library adds the PHP 5.5 password hashing functions to older versions of PHP)
    require_once("libraries/password_compatibility_library.php");
}

// include the configs / constants for the database connection
require_once("config/db.php");

// load the registration class
require_once("classes/Registration.php");
include_once ("classes/Login.php");
include_once("classes/DBconnection.php");
include_once("classes/View.php");

/**
 * create log with given message.
 * @param $message the message.
 */
function _log( $message ) {
    if( is_array( $message ) || is_object( $message ) )   {
        error_log( print_r( $message, true ), 3, "log.txt" );
    } else {
        error_log( $message, 3, "log.txt" );
    }
}

$dbGetData = null;
$view = null;
$dbGetData = new DBConnection();
$login = new Login($dbGetData);
if(!isset($_SESSION['user_id'])){
    $registration = new Registration();
}

// show potential errors / feedback (from login object)
if (isset($login)) {
    if ($login->errors) {
        foreach ($login->errors as $error) {
            echo '<p class="loginErrors"> ' . $error . ' </p>';
        }
    }
    if ($login->messages) {
        foreach ($login->messages as $message) {
            echo $message;
        }
    }
}


/**
 * Handle requests from users
 */
if(isset($_POST['message']))
{
    if(isset($_SESSION['user_id'])){
        $dbGetData->addMessage($_POST["message"]);
    }
} else if (isset($_GET['request'])){
    if(isset($_SESSION['user_id'])){
        $dbGetData->loadNewUserMessages($_GET['chat_id']);
    }
}  else {
    //if user is logged in
    if (isset($login)) {
        if ($login->isUserLoggedIn()) {
            if (isset($_POST["addChat"])) {
                if(isset($_POST['userIDNr'])){
                    $dbGetData->addChat($_POST["chatname"], $_POST['userIDNr']);
                } else {
                    $dbGetData->addChat($_POST["chatname"]);
                }
            }
            //remove the chat
            if (isset($_POST["removeChat"])) {
                $dbGetData->removeChat();
            }
            //set chat_id
            if (isset($_GET["chat_id"])) {
                $_SESSION['chat_id'] = $_GET['chat_id'];
            }
            //set chat name
            if (isset($_GET["chat_name"])) {
                $_SESSION['chat_name'] = $_GET['chat_name'];
            }
            //add and remove users
            if (isset($_POST['userIDNr2'])){
                $dbGetData->editUsersInChat($_POST['userIDNr2']);
            }
        }
    }
    //create the chat view if needed
    $view = new View($dbGetData);
}