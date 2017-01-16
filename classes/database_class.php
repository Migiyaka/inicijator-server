<?php

class DatabaseConnection {
    private $connection = null;
    private $hostname = 'localhost';
    private $database = 'inicijator';
    private $databaseUser = 'root';
    private $databasePass = '';
    
    public function __construct() {
        $this->connection = mysqli_connect($this->hostname, $this->databaseUser, $this->databasePass, $this->database);
    }
    
    public function disconnect() {
        mysqli_close($this->connection);
    }
    
    public function query($query) {
        $result = mysqli_query($this->connection, $query);
        return $result;
    }
    
    public function execute($query) {
        $result = $this->query($query);
        $this->disconnect();
        
        return $result;
    }
    
    public function getLastQueryID() {
        return mysqli_insert_id($this->connection);
    }
    
    public function getAsArray($dbResult) {
        return mysqli_fetch_assoc($dbResult);
    }
    
    public function hasResults($dbResult) {
        return $dbResult !== FALSE && mysqli_num_rows($dbResult) > 0;
    }
}

?>