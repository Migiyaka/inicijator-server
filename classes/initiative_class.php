<?php

class Initiative extends Entity {
    protected $id;
    protected $title;
    protected $text;
    protected $start_date;
    protected $end_date;
    protected $creator_id;
    protected $image_url;
    protected $category;
    protected $lat;
    protected $lng;
    
    protected $textExcerpt;
    protected $isOver;
    
    public $sig_num;
    public $start_date_time;
    public $end_date_time;
    
    protected $creator;
    protected $signatories;
    protected $comments;
    
    public function __construct($properties = NULL) {
        parent::__construct($properties);
        
        $this->setExtras();
        
        $this->signatories = array();
        $this->comments = array();
    }
    
    public function getFromDB($getBy, $getValue) {
        $query = 'SELECT * FROM initiatives WHERE ' . $getBy . ' = "' . $getValue . '"';
        
        $database = new DatabaseConnection();
        $initiative = $database->execute($query);
        
        if ($database->hasResults($initiative)) {
            $initiative = $database->getAsArray($initiative);
            foreach ($initiative as $property => $value)
                $this->{$property} = $value;
            
            $this->setExtras();
            
            return TRUE;
        }
        else
            return FALSE;
    }
    
    public function saveToDB() {
        $values = '("' . $this->title . '", "' . $this->text . '", "' . $this->start_date . '", "' . $this->end_date . '", "' . $this->creator_id . '", "' . 
            $this->image_url . '", "' . $this->category . '", "' . $this->lat . '", "' . $this->lng . '")';
            
        $query = 'INSERT INTO initiatives (title, text, start_date, end_date, creator_id, image_url, category, lat, lng) VALUES ' . $values;
        
        $database = new DatabaseConnection();
        $initiative = $database->query($query);
        $result = FALSE;
        
        if ($initiative !== FALSE) {
            $tmp = explode('.', $_FILES['image']['name']);
            $this->id = $database->getLastQueryID();
            $imageName = $this->id . '.' . end($tmp);
            
            $imageQuery = 'UPDATE initiatives SET image_url = "http://' . $_SERVER['HTTP_HOST'] . '/inicijator-server/uploads/' . $imageName . '" WHERE id = "' . $this->id . '"';  
            $database->query($imageQuery);    
            
            move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $imageName);
            
            $result = TRUE;
        }
        
        return $result;
    }
    
    public function compareInit($keywords, $categories, $startdate_start, $startdate_end, $enddate_start, $enddate_end) {
        $result = true;
        
        if ($keywords != '' && stripos($this->title, $keywords) === FALSE && stripos($this->text, $keywords) === FALSE ||
            $categories != '' && !in_array($this->category, $categories) ||
            $startdate_start != '' && $this->start_date_time < strtotime($startdate_start) ||
            $startdate_end != '' && $this->start_date_time > strtotime($startdate_end) ||
            $enddate_start != '' && $this->end_date_time < strtotime($enddate_start) ||
            $enddate_end != '' && $this->end_date_time > strtotime($enddate_end))
                $result = false;
            
        return $result;
    }
    
    public function getCreator() {
        $this->creator = new User();
        $this->creator->getFromDB('id', $this->creator_id);
    }
    
    public function getComments() {
        $query = 'SELECT * FROM comments WHERE initiative_id = "' . $this->id . '"';
        
        $database = new DatabaseConnection();
        $commentsDB = $database->execute($query);
        
        if ($database->hasResults($commentsDB)) {
            while ($commentDB = $database->getAsArray($commentsDB)) {
                $comment = new Comment($commentDB);
                $comment->getCreator();
                
                array_push($this->comments, $comment);
            }
        }
    }
    
    public function addComment($userId, $text) {
        $values = '("' . $this->id . '", "' . $userId . '", "' . date("Y-m-d H:i:s") . '", "' . $text . '")';
        $query = 'INSERT INTO comments (initiative_id, user_id, date, text) VALUES ' . $values;
        
        $database = new DatabaseConnection();
        $resultDB = $database->execute($query);
        
        if ($resultDB !== FALSE)
            return TRUE;
        else
            return FALSE;
    }
    
    public function getSignatories() {
        $query = 'SELECT * FROM users WHERE id IN (SELECT user_id FROM signatories WHERE initiative_id = "' . $this->id . '")';
        
        $database = new DatabaseConnection();
        $signatoriesDB = $database->execute($query);
        
        if ($database->hasResults($signatoriesDB)) {
            while ($signatoryDB = $database->getAsArray($signatoriesDB)) {
                $signatory = new User($signatoryDB);
                array_push($this->signatories, $signatory);
            }
            
            $this->sig_num = count($this->signatories);
        }
    }
    
    public function getSigNum() {
        $query = 'SELECT COUNT(*) as sig_num FROM signatories WHERE initiative_id = "' . $this->id . '"';
        
        $database = new DatabaseConnection();
        $sigDB = $database->execute($query);
        
        if ($database->hasResults($sigDB)) {
            $sigDB = $database->getAsArray($sigDB);
            $this->sig_num = (int)$sigDB['sig_num'];
        }
    }
    
    public function supportInit($userId) {
        $values = '("' . $this->id . '", "' . $userId . '")';
        $query = 'INSERT INTO signatories (initiative_id, user_id) VALUES ' . $values;
        
        $database = new DatabaseConnection();
        $supportDB = $database->execute($query);
        
        if ($supportDB !== FALSE)
            return TRUE;
        else
            return FALSE;
    }
    
    public function setExtras() {
        $this->start_date_time = strtotime($this->start_date);
        $this->end_date_time = strtotime($this->end_date);
        $this->isOver = $this->end_date_time < time();
        $this->createExcerpt(15);
        $this->getSigNum();
        $this->getCreator();
    }
    
    public function createExcerpt($numOfWords) {
        $textArray = explode(' ', $this->text);
        $excerpt = '';
        
        if (count($textArray) > $numOfWords) {
            for ($i = 0; $i < $numOfWords; $i++)
                $excerpt .= $textArray[$i] . ' ';
            
            $excerpt .= '...';
        }
        else
            $excerpt = $this->text;
        
        $this->textExcerpt = $excerpt;
    }
}

?>