<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 13.11.2014
 * Time: 13:58
 */

class DBConnection {
    /**
     * @var object The database connection
     */
    private $db_connection = null;

    public function __construct()
    {
        $this->doLogin();
    }

    /**
     * log the given message.
     * @param $message the message.
     */
    function _log( $message ) {
        if( is_array( $message ) || is_object( $message ) )   {
             error_log( print_r( $message, true ) . "\n", 3, "log.txt" );
        } else {
             error_log( $message . "\n", 3, "log.txt" );
        }
    }

    /**
     * login to database
     */
    public function doLogin()
    {
        try {
            $this->db_connection = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
            if(!isset($_SESSION['chat_id'])) {

            }
        } catch (PDOException $e) {
            $this->_log("Error: " . $e->getMessage());
            die();
        }
    }

    /**
     * set default chat
     */
    public function doUserInDefaultChat(){
        $_SESSION['chat_id'] = 77;
        $_SESSION['chat_name'] = "Welcome";
    }

    /**
     * return chats with chatID.
     * @return mixed list of chats with chat_id and chat_name.
     */
    public function loadChatsOfUser(){
        try {
            // set the PDO error mode to exception
            $this->db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $db = $this->db_connection->prepare('SELECT chat_id, chat_name FROM user_has_chat JOIN chat USING (chat_id) WHERE user_id=?;');
            $db->execute(array($_SESSION['user_id']));
            return($db->fetchAll(PDO::FETCH_ASSOC));
        }
        catch(PDOException $e)
        {
            $this->_log("Error: " . $e->getMessage());
        }
    }

    /**
     * Add a new chat with username and users
     * @param $name the new of the new chat
     * @param null $arrayOfUsers the array with all the users.
     */
    public function addChat($name, $arrayOfUsers = null){
        if(is_string($name) && "" != $name){
            try {
                // set the PDO error mode to exception
                $this->db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $tmp = $this->db_connection->prepare("INSERT INTO chat(chat_name, chat_createdby_user_id, chat_w_allowed) VALUES(:chat_name, :chat_createdby_user_id, :chat_w_allowed)");
                $tmp->execute(array(':chat_name' => htmlspecialchars($name), ':chat_createdby_user_id' => $_SESSION['user_id'], ':chat_w_allowed' => '1'));
                $insertedId = $this->db_connection->lastInsertId();

                if(isset($arrayOfUsers)){
                    $db = $this->db_connection->prepare("INSERT INTO user_has_chat(chat_id, user_id) VALUES(:chat_id, :user_id)");
                    $db->execute(array(':chat_id' => $insertedId, ':user_id' => $_SESSION['user_id']));
                    foreach($arrayOfUsers as $userid){
                        $this->addUserToChat(htmlspecialchars($userid), $insertedId);
                    }
                } else if (0 == $_SESSION['user_id']) {
                    foreach ($this->db_connection->query("SELECT DISTINCT user_id FROM user") as $row){
                        $this->addUserToChat($row['user_id'], $insertedId);
                    }
                }
            }
            catch(PDOException $e)
            {
                $this->_log("Error: " . $e->getMessage());
            }
        }
    }

    /**
     * add user to chat.
     * @param $user_id add this user.
     * @param $chat_id to this chat.
     */
    public function addUserToChat($user_id, $chat_id) {
        try {
            // set the PDO error mode to exception
            $this->db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $count = $this->db_connection->query('SELECT COUNT(*) FROM user_has_chat WHERE user_id='. $user_id .' AND chat_id=' . $chat_id);
            if($count->fetch()[0] == 0){
                $tmp = $this->db_connection->prepare("INSERT INTO user_has_chat(chat_id, user_id) VALUES(:chat_id, :user_id)");
                $tmp->execute(array(':chat_id' => $chat_id, ':user_id' => $user_id));
            }
        }
        catch(PDOException $e)
        {
            $this->_log("Error: " . $e->getMessage());
        }
    }

    /**
     * remove user from Chat.
     * @param $user_id remove this user.
     * @param $chat_id from this chat.
     */
    public function removeUserFromChat($user_id, $chat_id) {
        try {
            // set the PDO error mode to exception
            $this->db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $count = $this->db_connection->query('SELECT COUNT(*) FROM user_has_chat WHERE user_id=' . $user_id . ' AND chat_id=' . $chat_id);
            if ($count->fetch()[0] > 0) {
                $this->db_connection->query('DELETE FROM user_has_chat WHERE chat_id=' . $chat_id . ' AND user_id=' . $user_id);
            }
        }
        catch(PDOException $e)
        {
            $this->_log("Error: " . $e->getMessage());
        }
    }

    /**
     * remove the active chat.
     */
    public function removeChat(){
        try {
            // set the PDO error mode to exception
            $this->db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->db_connection->query('DELETE FROM user_has_chat WHERE chat_id=' . $_SESSION['chat_id'] . ' AND user_id=' . $_SESSION['user_id']);

            $count = $this->db_connection->query('SELECT COUNT(*) FROM user_has_chat WHERE chat_id=' . $_SESSION['chat_id']);
            if($count->fetch()[0] == 0){
                $this->db_connection->query('DELETE FROM chat WHERE chat_id=' . $_SESSION['chat_id']);
            }
        }
        catch(PDOException $e)
        {
            $this->_log("Error: " . $e->getMessage());
        }
    }

    /**
     * return the content of the given chat.
     * @return mixed return a list with the content of the chat.
     */
    public function loadChatContent(){
        try {
            // set the PDO error mode to exception
            $this->db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $db = $this->db_connection->prepare('SELECT m.message_id, m.timestamp, m.user_id, u.user_name, m.chat_id, m.message
                  FROM message m
                  INNER JOIN user u
                  ON u.user_id = m.user_id
                  WHERE chat_id = ? ORDER BY m.timestamp');
            $db->execute(array($_SESSION['chat_id']));
            return($db->fetchAll(PDO::FETCH_ASSOC));
        }
        catch(PDOException $e)
        {
            $this->_log("Error: " . $e->getMessage());
        }
    }

    public function doLogout()
    {
        $db_connection = null;
    }

    /**
     * return a list of all users from this chat
     * @return mixed list of all users of this chat.
     */
    public function loadUsersInChat() {
        try {
            // set the PDO error mode to exception
            $this->db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $db = $this->db_connection->prepare("Select user_id, user_name FROM user_has_chat JOIN user USING (user_id) WHERE chat_id = ?");
            $db->execute(array($_SESSION['chat_id']));
            return($db->fetchAll(PDO::FETCH_ASSOC));
        }
        catch(PDOException $e)
        {
            $this->_log("Error: chat_id: " . $_SESSION['chat_id'] . " SQL error: " . $e->getMessage());
        }
    }

    /**
     * return user list.
     * @return mixed return list of users.
     */
    public function loadUserList(){
        try {
            // set the PDO error mode to exception
            $this->db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $db = $this->db_connection->query('SELECT DISTINCT * FROM user');
            $db->execute();
            return($db->fetchAll(PDO::FETCH_ASSOC));
        }
        catch(PDOException $e)
        {
            $this->_log("Error: " . $e->getMessage());
        }
    }

    /**
     * return the editable users as options.
     * @return mixed list of editable users in a chat.
     */
    public function loadEditableUsers() {
        try {
            // set the PDO error mode to exception
            $this->db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $db = $this->db_connection->prepare("Select user_id, user_name FROM user_has_chat JOIN user USING (user_id) WHERE chat_id = ?");
            $db->execute(array($_SESSION['chat_id']));

            return($db->fetchAll(PDO::FETCH_ASSOC));
        }
        catch(PDOException $e)
        {
            $this->_log("Error: chat_id: " . $_SESSION['chat_id'] . " SQL error: " . $e->getMessage());
        }
    }

    /**
     * compare existing users in chat with changed list.
     * add and remove if necessary
     * @param $listOfUsers the list of users
     */
    public function editUsersInChat($listOfUsers) {
        try {
            // set the PDO error mode to exception
            $this->db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if($this->isOwnerOfChat()) {
                foreach ($this->db_connection->query('SELECT user_id FROM user_has_chat WHERE chat_id=' . $_SESSION['chat_id']) as $row) {
                    $contains = False;
                    foreach($listOfUsers as $userid) {
                        if($row['user_id'] == $userid) {
                            $contains = True;
                        }
                    }
                    if(!$contains && !($row['user_id'] == $_SESSION['user_id'])){
                        $this->removeUserFromChat($row['user_id'],$_SESSION['chat_id']);
                    }
                }

                foreach($listOfUsers as $userid) {
                    $this->addUserToChat($userid, $_SESSION['chat_id']);
                }
            }
        }
        catch(PDOException $e)
        {
            $this->_log("Error: " . $e->getMessage());
        }
    }

    /** check if user is owner of this chat.
     * @return bool yes if is owner of chat.
     */
    public function isOwnerOfChat() {
        try {
            // set the PDO error mode to exception
            $this->db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $count = $this->db_connection->query('SELECT COUNT(*) FROM chat WHERE chat_id=' . $_SESSION['chat_id'] . " AND chat_createdby_user_id=" . $_SESSION['user_id']);
            return($count->fetch()[0] > 0);
        }
        catch(PDOException $e)
        {
            $this->_log("Error: " . $e->getMessage());
        }
    }

    /**
     * add a new message sent by the user.
     * @param $message the message
     */
    public function addMessage($message){
        if(is_string($message)){
            try {
                // set the PDO error mode to exception
                $this->db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $tmp = $this->db_connection->prepare("INSERT INTO message(chat_id, message, user_id) VALUES(:chat_id, :message, :user_id)");
                $tmp->execute(array(':chat_id' => $_SESSION['chat_id'], ':message' => htmlspecialchars($message), ':user_id' => $_SESSION['user_id']));
                //$_SESSION['last_message_id'] = $this->db_connection->lastInsertId();
            }
            catch(PDOException $e)
            {
                $this->_log("Error: " . $e->getMessage());
            }
        }
    }

    /**
     * return the requestet user messages by a user
     * @param $chat_id  the chat id.
     */
    public function loadNewUserMessages($chat_id){
        try {
            // set the PDO error mode to exception
            $this->db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db = $this->db_connection->prepare('SELECT m.message_id, m.timestamp, m.user_id, u.user_name, m.chat_id, m.message
                  FROM message m
                  INNER JOIN user u
                  ON u.user_id = m.user_id
                  WHERE chat_id = ? AND message_id > ? AND m.user_id != ?;');
            $db->execute(array($chat_id, $_SESSION['last_message_id'], $_SESSION['user_id']));
            $arrayOfNewMessages = $db->fetchAll(PDO::FETCH_ASSOC);

            $lastMessageId = $_SESSION['last_message_id'];

            foreach($arrayOfNewMessages as $value){
                if(isset($value['message_id'])){
                    $_SESSION['last_message_id'] = $value['message_id'];
                }
            }
            if($lastMessageId != $_SESSION['last_message_id']){
                echo json_encode($arrayOfNewMessages);
            }
        }
        catch(PDOException $e)
        {
            $this->_log("Error: " . $e->getMessage());
        }
    }
} 