<?php
/**
 * Created by PhpStorm.
 * User: Samuel Merki
 * Date: 24.11.2014
 * Time: 22:09
 */

// include the configs / constants for the database connection
require_once("config/db.php");

include_once("classes/DBconnection.php");

class View {
    public $dbGetData = null;

    public function __construct($dbConnection)
    {
        if($dbConnection instanceof DBConnection){
            $this->dbGetData = $dbConnection;
            $this->loadHead();
            $this->loadBody();
        }

    }

    public function loadHead(){
        ?>
        <!DOCTYPE html>
        <html>
        <head lang="en">
            <meta charset="UTF-8">
            <title>Project WebChat</title>
            <script type="text/javascript" src="js/jquery-2.1.1.js"></script>
            <script type="text/javascript" src="js/bootstrap.js"></script>
            <script type="text/javascript" src="js/script.js"></script>
            <link rel="stylesheet" href="css/bootstrap.css">
            <link rel="stylesheet" href="css/dashboard.css">
            <meta name="viewport" content="width=device-width, initial-scale=1">
        </head>
        <?php
    }

    public function loadBody(){
        echo '<body>';
        $this->loadNavbarHeader();
        $this->loadChatContainer();
        echo '</body>';
        echo '</html>';
    }

    public function loadNavbarHeader(){
        ?>
        <nav class="navbar navbar-inverse navbar-fixed-top container-fluid" role="navigation">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="">Project WebChat</a>
                </div>  <!-- close of navbar-head -->
                <div id="navbar" class="navbar-collapse navbar-right collapse">
                    <ul class="nav navbar-nav">
                        <!-- <li><a href="#">Settings</a></li> -->
                        <li><a href="#">
                                <?php
                                if(isset($_SESSION{'user_id'})){
                                    echo $_SESSION['user_name'];
                                } else {
                                    echo "Profile";
                                }
                                ?>
                            </a></li>
                    </ul>
                    <form class="navbar-form navbar-right" method="post" action="index.php" name="loginform">
                        <?php
                        if(isset($_SESSION{'user_id'})){
                            ?>
                            <button type="submit" name="logout" class="btn btn-success">Log out</button>
                        <?php
                        } else {
                            ?>
                            <div class="form-group">
                                <input id="login_input_username" class="form-control login_input" type="text" name="user_name" placeholder="Email/Username" required>
                            </div>
                            <div class="form-group">
                                <input id="login_input_password" class="form-control login_input" type="password" name="user_password" placeholder="Password" autocomplete="off" required>
                            </div>

                            <button type="submit" name="login" class="btn btn-success">Sign in</button>
                            <button type="button" id="b-register" data-toggle="modal" data-target="#register" class="btn btn-success">Register</button>
                        <?php } ?>
                    </form>
                </div>
        </nav>
        <?php
    }

    public function loadChatContainer(){
        echo '<div id="main" class="container-fluid active">';
        $this->loadChatSelectionSidebar();
        if(isset($_SESSION{'user_id'})){
            $this->loadModalAddChat();
            $this->loadModalRemoveChat();
            $this->loadModalEditUsersInChat();
        } else {
            $this->loadWelcome();
        }
        $this->loadMainChatWindow();
        $this->loadModalRegister();
        echo '</div>';
    }

    public function loadWelcome() {
        ?>
        <p></p>
        <img class="welcome" src="res/WebchatWelcome2.png" alt="Welcome Page">
    <?php
    }

    public function loadChatSelectionSidebar(){
        ?>
        <div id="sidebar-content">
            <ul id="sidebar_menu" class="sidebar-nav">
                <li class="sidebar-brand"><a id="menu-toggle">Chats<span id="main_icon" class="glyphicon glyphicon-align-justify"></span></a></li>
            </ul>
            <ul class="sidebar-nav" id="sidebar">
                <?php
                if(isset($_SESSION['user_id'])) {
                    foreach($this->dbGetData->loadChatsOfUser() as $row) {
                        if($_SESSION['chat_id'] == $row['chat_id']){
                            echo ('<li class="selected"><a class="selected" href="index.php?chat_id=' . $row['chat_id'] . '&chat_name='. $row['chat_name'] .'">'. $row['chat_name'] . '</a></li>');
                        } else {
                            echo ('<li><a href="index.php?chat_id=' . $row['chat_id'] . '&chat_name='. $row['chat_name'] .'">'. $row['chat_name'] . '</a></li>');
                        }
                    }
                }
                ?>
                <li><div role="toolbar">
                        <div class="btn-group btn-group-sm">
                            <button class="btn black2" data-toggle="modal" data-target="#addChat" type="button"><span class="glyphicon glyphicon-plus"></span> ADD</button>
                            <button class="btn black2" data-toggle="modal" data-target="#removeChat" type="button"><span class="glyphicon glyphicon-minus"></span> REMOVE</button>
                        </div>
                </div></li>
            </ul>
        </div>
        <?php
    }

    public function loadMainChatWindow(){
        echo '<div class="chat-content">';
        if(isset($_SESSION['chat_id'])) {
            ?>
            <h3 class="sub-header text-left" id="getChatId" chatID="<?php echo $_SESSION['chat_id'];?>">
                <?php
                echo $_SESSION['chat_name'];
                ?>
            </h3>
                <div id="chatDiv">
                    <p class="message userInChat">
                        <b>Users in this Chat</b>
                        <?php
                        if($this->dbGetData->isOwnerOfChat()){
                            echo '<button type="button" id="b-editUsersinChat" data-toggle="modal" data-target="#editUsersInChat" class="btn btn-success btn-sm">Edit</button>';
                        }
                        echo '<br>';
                        foreach($this->dbGetData->loadUsersInChat() as $row) {
                            //echo " " . $row['user_name'];
                            echo " " . ' <span style="color:#'. dechex(mt_rand(0, 16777215)) .';">'. $row['user_name'] .'</span>';
                        }
                        ?>
                    </p>
                    <?php
                        foreach($this->dbGetData->loadChatContent() as $row) {
                            if ($row['user_id'] == $_SESSION['user_id']) {
                                echo('<p class="message myMessage"> you: ' . $row['timestamp'] . '<br>' . $row['message'] . '</p>');
                            } else {
                                echo('<p class="message extMessage">' . $row['user_name'] . ': ' . $row['timestamp'] . '<br>' . $row['message'] . '</p>');
                            }
                            $_SESSION['last_message_id'] = $row['message_id'];
                        }
                    ?>
                </div>
                <div id="messageDiv" class="form-group">
                    <form id="sendMessage">
                        <label>Message: </label>
                        <div class="input-group">
                           <input id="message" type="text" class="text-primary form-control custom-control" autocomplete="off" maxlength="200" rows="3">
                           <span id="send" type="button" class="input-group-addon btn btn-primary">Send</span>
                        </div>
                    </form>
                </div>
        <?php
        }
        echo '</div>';
    }

    public function loadModalRegister(){
        ?>
        <div class="modal fade" id="register" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                        <h4 class="modal-title" id="myModalLabel">Register</h4>
                    </div>
                    <form id="registerform" role="form" data-toggle="validator" class="form-horizontal" method="post" action="index.php" name="registerform">
                        <div class="modal-body">
                            <fieldset>
                                <div class="control-group">
                                    <!-- Username -->
                                    <label class="control-label" for="login_input_username">Username</label>
                                    <div class="controls">
                                        <input id="login_input_username" class="input-xlarge login_input" type="text" pattern="[a-zA-Z0-9]{2,64}" required name="user_name">
                                        <p class="help-block">Username can contain any letters or numbers, without spaces</p>
                                    </div>
                                </div>

                                <div class="control-group">
                                    <!-- E-mail -->
                                    <label class="control-label" for="login_input_email">E-mail</label>
                                    <div class="controls">
                                        <input  id="login_input_email" class="input-xlarge login_input" type="email" required name="user_email">
                                        <p class="help-block">Please provide your E-mail</p>
                                    </div>
                                </div>

                                <div class="control-group">
                                    <!-- Password-->
                                    <label class="control-label" for="login_input_password_new">Password</label>
                                    <div class="controls">
                                        <input id="login_input_password_new" class="input-xlarge login_input" type="password" name="user_password_new" pattern=".{6,}" required autocomplete="off">
                                        <p class="help-block">Password should be at least 6 characters</p>
                                    </div>
                                </div>

                                <div class="control-group">
                                    <!-- Password -->
                                    <label class="control-label"  for="login_input_password_repeat">Password (Confirm)</label>
                                    <div class="controls">
                                        <input id="login_input_password_repeat" class="input-xlarge login_input" type="password" name="user_password_repeat" pattern=".{6,}" required autocomplete="off">
                                        <p class="help-block">Please confirm password</p>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" name="register" value="Register" class="btn btn-primary">Register</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public function loadModalAddChat(){
        ?>
        <div class="modal fade" id="addChat" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form id="addRoleForm" role="form" data-toggle="validator" method="POST" action="index.php" name="addChat">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                            <h4 class="modal-title">Add Chat</h4>
                        </div>
                        <div class="modal-body">
                            <label class="control-label">Chat name</label>
                            <div class="controls">
                                <input type="text" id="chatname" name="chatname" placeholder="Chat name" autocomplete="off" pattern="[a-zA-Z0-9]{1,64}" required data-error="Chatname is invalid" class="input-xlarge">
                                <p class="help-block">You can use a-z, A-Z, 0-9</p>
                            </div>
                            <label class="control-label">Users</label>
                            <div class="controls">
                                <input type="text" list="userList" id="users" name="users" placeholder="Search Users here" class="input-xlarge" autocomplete="off">
                                <datalist id = "userList">
                                    <?php
                                    foreach($this->dbGetData->loadUserList() as $row) {
                                        if($row['user_id'] != $_SESSION['user_id']){
                                            echo('<option userid=' . $row['user_id'] . ' value=' . $row['user_name'] . '>');
                                        }
                                    }
                                    ?>
                                </datalist>
                                <button type="button" class="btn btn-sm" id="addUserToGroup"><span class="glyphicon glyphicon-plus"></span></button>
                                <p class="help-block">To add press +</p>
                            </div>
                            <div id="selectedUsers"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" value="addChat" name="addChat" class="btn btn-primary">AddChat</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    public function loadModalRemoveChat(){
        ?>
        <div class="modal fade" id="removeChat" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="index.php" name="removeChat">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                            <h4 class="modal-title">Remove Chat</h4>
                        </div>
                        <div class="modal-body">
                            <label class="control-label">Remove yourself from chat:
                                <?php
                                echo $_GET['chat_name'];
                                ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" value="removeChat" name="removeChat" class="btn btn-primary">Remove Chat</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    public function loadModalEditUsersInChat() {
        ?>
        <div class="modal fade" id="editUsersInChat" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="index.php" name="editUsersInChat">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                            <h4 class="modal-title">Edit Users</h4>
                        </div>
                        <div id="selectedUserOfEdit" class="modal-body">
                            <?php
                            foreach($this->dbGetData->loadEditableUsers() as $row){
                                echo '
                                 <p id="modal-edit-user-' . $row['user_id'] . '"><button onclick="removeIdfromChat(' . $row['user_id'] . ')" class="btn btn-sm remSelectedUser"><span class="glyphicon glyphicon-minus-sign"></span></button>'
                                    . $row['user_name'] . '<input name="userIDNr2[]" type="hidden" value="' . $row['user_id'] . '">
                                    </p>';
                            }
                            ?>
                            <div class="controls">
                                <p>To add press +</p>
                                <input type="text" list="userList" id="users2" name="users2" placeholder="Search users here" class="input-xlarge" autocomplete="off">
                                <datalist id = "userList2">
                                    <?php
                                    foreach($this->dbGetData->loadUserList() as $row) {
                                        if($row['user_id'] != $_SESSION['user_id']){
                                            echo('<option userid=' . $row['user_id'] . ' value=' . $row['user_name'] . '>');
                                        }
                                    }
                                    ?>
                                </datalist>
                                <button type="button" class="btn btn-sm" id="addUserToGroup2"><span class="glyphicon glyphicon-plus"></span></button>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" value="editUsersInChat" name="editUsersInChat" class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php
    }
} 