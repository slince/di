<?php
class ClassA {
    
}
class ClassB
{
    
}
class ClassC
{
    private $_a;
    private $_b;
    function __construct(ClassA $a, ClassD $b)
    {
        $this->_a = $a;
        $this->_b = $b;
    } 
}