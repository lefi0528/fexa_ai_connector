<?php

namespace React\Dns\Query;

use React\Dns\Model\Message;


final class Query
{
    
    public $name;

    
    public $type;

    
    public $class;

    
    public function __construct($name, $type, $class)
    {
        $this->name = $name;
        $this->type = $type;
        $this->class = $class;
    }

    
    public function describe()
    {
        $class = $this->class !== Message::CLASS_IN ? 'CLASS' . $this->class . ' ' : '';

        $type = 'TYPE' . $this->type;
        $ref = new \ReflectionClass('React\Dns\Model\Message');
        foreach ($ref->getConstants() as $name => $value) {
            if ($value === $this->type && \strpos($name, 'TYPE_') === 0) {
                $type = \substr($name, 5);
                break;
            }
        }

        return $this->name . ' (' . $class . $type . ')';
    }
}
