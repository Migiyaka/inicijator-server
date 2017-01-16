<?php

    require 'classes/server_class.php';
    require 'classes/database_class.php';
    require 'classes/entity_class.php';
    require 'classes/user_class.php';
    require 'classes/initiative_class.php';
    require 'classes/initiative_repository.php';
    require 'classes/comments_class.php';
    require 'phpmailer/PHPMailerAutoload.php';
    
    $server = new ServerClass();
    if (isset($_POST) && isset($_POST['action']))
        $server->$_POST['action']();

?>