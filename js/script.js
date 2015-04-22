/** Created by Samuel Merki on 30.10.2014 */

$( document ).ready(function() {
    $("#menu-toggle").click(function(e) {
        e.preventDefault();
        $("#main").toggleClass("active");
    });

    $("#send").click(function(e) {
        $('#sendMessage').submit();
    });

    $("#sendMessage").submit(function(){
        var message = $('#message').val();
        if(message.trim()) {
            $('#message').val("");
            try
            {
                $.ajax({
                    type: 'POST',
                    url: 'index.php',
                    data: { message: message },
                    onComplete: handleRefresh("you", message)
                });
            }
            catch (e)
            {
                alert('Error: ' + e.toString());
            }
            return false;
        }
        return false;
    });

    // add user to create a new Chat
    $("#addUserToGroup").click(function() {
        var user = $('#users').val();
        if(user.length > 0) {
            var id = $('#userList').find('option').filter(function() { return $.trim( $(this).val() ) === user; }).attr('userid');

            $('#selectedUsers').append('<p id="modal-user-'+ id +'">'
            + '<button type="button" onclick="removeId(' + id + ')" + class="btn btn-sm remSelectedUser"><span class="glyphicon glyphicon-minus-sign"></span></button>'
            + user
            + '<input name="userIDNr[]" type="hidden" value='+ id + '> </p>');

            $('#users').val("");
        }
    });

    //add users to existing chat
    $("#addUserToGroup2").click(function() {
        var user = $('#users2').val();
        if(user.length > 0) {
            var id = $('#userList2').find('option').filter(function() { return $.trim( $(this).val() ) === user; }).attr('userid');

            $('#selectedUserOfEdit').append('<p id="modal-edit-user-'+ id +'">'
            + '<button type="button" onclick="removeIdfromChat(' + id + ')" + class="btn btn-sm remSelectedUser"><span class="glyphicon glyphicon-minus-sign"></span></button>'
            + user
            + '<input name="userIDNr2[]" type="hidden" value='+ id + '> </p>');

            $('#users2').val("");
        }
    });

    //activate sidebar
    $('#sidebar_menu').click(function () {
            $('.row-offcanvas').toggleClass('active');
    });

    // auto request to load new messages
    setInterval(requestNewMessages, 5000);

    scrollDownInChat();
});

//remove user from new chat
function removeId(id){
    $('#modal-user-'+id).remove();
}

//remove user from already existing chat
function removeIdfromChat(id) {
    $('#modal-edit-user-'+id).remove();
}


//get the message and displays it
function handleRefresh(user, message){
    var d = new Date();
    var time = d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate() + ' ' + d.getHours() + ':' + d.getMinutes() + ':' + d.getSeconds();
    if(user == "you"){
        $('#chatDiv').append('<p class="message myMessage"> you: ' + time + '<br>'+ message +'</p>');
    } else {
        $('#chatDiv').append('<p class="message extMessage"> ' + user +': ' + time + '<br>'+ message +'</p>');
    }
    scrollDownInChat()
}

//auto scroll down the page
function scrollDownInChat() {
    $chatid = $('#chatDiv');
    $chatid.scrollTop($chatid[0].scrollHeight);

}


//request new message
function requestNewMessages() {
    var chatid = $('#getChatId').attr("chatID");
    $.getJSON('index.php', { request: "getNewMessages", chat_id: chatid }, loadNewMessages);
}

//loead eatch new message
function loadNewMessages(input){
    var i;
    for (i = 0; i < input.length; i++) {
        handleRefresh(input[i].user_name, input[i].message);
    }
}


