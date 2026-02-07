<?php

namespace React\Dns\Model;


final class Record
{
    
    public $name;

    
    public $type;

    
    public $class;

    
    public $ttl;

    
    public $data;

    
    public function __construct($name, $type, $class, $ttl, $data)
    {
        $this->name     = $name;
        $this->type     = $type;
        $this->class    = $class;
        $this->ttl      = $ttl;
        $this->data     = $data;
    }
}
