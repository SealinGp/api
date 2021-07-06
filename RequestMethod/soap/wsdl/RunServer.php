<?php
include '../../../autoload.php';
//soap server build class
/*include 'soap_server.php';
include 'HandleClass.php';*/

if (isset($_GET['wsdl'])) {
    $soap_server = new wsdl\soap_server('myTestServer','HandleClass',new wsdl\HandleClass());
    $wsdlDir =  __DIR__;
    $soap_server->run($wsdlDir);
    
}