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
    function __construct(ClassA $a, ClassB $b)
    {
        $this->_a = $a;
        $this->_b = $b;
    } 
}

class ClassD
{
    private $_str1;
    private $_str2;
    private $_c;
    function __construct(ClassC $c, $str)
    {
        $this->_c = $c;
        $this->_str1 = $str;
    }
    function setStr2($str){
        $this->_str2 = $str;
    }
    function echoStr()
    {
        echo $this->_str1, $this->_str2;
    }
}