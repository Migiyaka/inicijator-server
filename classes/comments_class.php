<?php

class Comment extends Entity {
    protected $id;
    protected $initiative_id;
    protected $user_id;
    protected $date;
    protected $text;
    
    protected $creator;
    
    public function __construct($properties = NULL) {
        parent::__construct($properties);
    }
    
    public function saveToDB() {
        $values = '("' . $this->initiative_id . '", "' . $this->user_id . '", "' . $this->date . '", "' . $this->text . '")';
        $query = 'INSERT INTO comments (initiative_id, user_id, date, text) VALUES ' . $values;
        
        $database = new DatabaseConnection();
        $comment = $database->execute($query);
        
        if ($comment == FALSE)
            return FALSE;
        else
            return TRUE;
    }
    
    public function getCreator() {
        $query = 'SELECT * FROM users WHERE id = "' . $this->user_id . '"';
        
        $database = new DatabaseConnection();
        $creatorDB = $database->execute($query);
        
        if ($database->hasResults($creatorDB)) {
            $creatorDB = $database->getAsArray($creatorDB);
            $this->creator = new User($creatorDB);
        }
    }
}

?>