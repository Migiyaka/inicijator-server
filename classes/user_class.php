<?php

class User extends Entity {
    protected $id;
    protected $name;
    protected $surname;
    protected $email;
    protected $index_no;
    
    protected $createdInits;
    protected $supportedInits;
    
    public function __construct($properties = NULL) {
        parent::__construct($properties);
            
        $this->createdInits = array();
        $this->supportedInits = array();
    }
    
    public function getFromDB($getBy, $getValue) {
        $query = 'SELECT * FROM users WHERE ' . $getBy . ' = "' . $getValue . '"';
        
        $database = new DatabaseConnection();
        $userDB = $database->execute($query);
        
        if ($database->hasResults($userDB)) {
            $user = $database->getAsArray($userDB);
            foreach ($user as $property => $value)
                if ($property != 'password')
                    $this->{$property} = $value;
            
            return TRUE;
        }
        else
            return FALSE;
    }
    
    public function comparePassword($password) {
        $query = 'SELECT password FROM users WHERE id = "' . $this->id . '"';
        
        $database = new DatabaseConnection();
        $passwordDB = $database->execute($query);
        
        if ($database->hasResults($passwordDB)) {
            $passwordDB = $database->getAsArray($passwordDB);
            
            return password_verify($password, $passwordDB['password']);
        }
        else
            return FALSE;
    }
    
    public function getInitiatives($type) {
        $query = ($type == 'created') ? 'SELECT * FROM initiatives WHERE creator_id = "' . $this->id . '"' :
                'SELECT * FROM initiatives WHERE id IN (SELECT initiative_id FROM signatories WHERE user_id = "' . $this->id . '")';
        
        $database = new DatabaseConnection();
        $initiatives = $database->execute($query);
        
        if ($database->hasResults($initiatives)) {
            while ($initDB = $database->getAsArray($initiatives)) {
                $init = new Initiative($initDB);
                
                $array = $type . 'Inits';
                array_push($this->{$array}, $init);
            }
        }
    }
}

?>