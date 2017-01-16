<?php

class InitiativeRepository {
    private $initiatives;
    
    public function __construct() {
        $this->initiatives = array();
    }
    
    public function getFromDB() {
        $query = 'SELECT * FROM initiatives';
        
        $database = new DatabaseConnection();
        $initiativesDB = $database->execute($query);
        
        if ($database->hasResults($initiativesDB)) {
            while ($initiativeDB = $database->getAsArray($initiativesDB)) {
                $initiative = new Initiative($initiativeDB);
                
                array_push($this->initiatives, $initiative);
            }
            
            return TRUE;
        }
        else
            return FALSE;
    }
    
    public function orderBy($property, $direction) {
        for ($i = 1; $i < count($this->initiatives); $i++) {
            $j = $i;
            
            while ($j > 0) {
                if ($direction == 'asc' && $this->initiatives[$j]->{$property} < $this->initiatives[$j - 1]->{$property} || 
                    $direction == 'desc' && $this->initiatives[$j]->{$property} > $this->initiatives[$j - 1]->{$property}) {
                        $tmp = $this->initiatives[$j];
                        $this->initiatives[$j] = $this->initiatives[$j - 1];
                        $this->initiatives[$j - 1] = $tmp;
                }
                
                $j--;
            }
        }
    }
    
    public function initiatives() {
        return $this->initiatives;
    }
}

?>