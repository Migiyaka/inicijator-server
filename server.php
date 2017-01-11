<?php

    require('server_class.php');
    
    if (isset($_POST) && isset($_POST['action'])) {
        $server = new ServerClass();
        $server->$_POST['action']();
    }

?>