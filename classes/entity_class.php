<?php

class Entity {
    
    public function __construct($properties = NULL) {
        if ($properties != NULL) {
            foreach ($properties as $key => $value)
                    $this->{$key} = $value;
        }
    }
    
    public function getAsArray($array = NULL) {
        $result = array();
        $iterate = ($array !== NULL) ? $array : $this;
        
        foreach ($iterate as $key => $value) {
            if (gettype($value) == 'object')
                $result[$key] = $value->getAsArray();
            else if (is_array($value)) {
                $result[$key] = $this->getAsArray($value);
            }
            else
                $result[$key] = $value;
        }
        
        return $result;
    }
}

?>