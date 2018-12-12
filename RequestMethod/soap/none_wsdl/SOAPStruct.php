<?php
class SOAPStruct
{
    private $varString;
    private $varInt;
    private $varFloat;
    function SOAPStruct($s, $i, $f)
    {
        $this->varString = $s;
        $this->varInt = $i;
        $this->varFloat = $f;
    }
}