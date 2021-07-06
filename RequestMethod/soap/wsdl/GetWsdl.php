<?php
include '../../../autoload.php';

/*//soap server build class
include 'soap_server.php';

//handle class
include 'HandleClass.php';*/


function getWsdl () {
    //soap server hander class
    $servClass = 'HandleClass';
    //soap server name
    $servName = 'myTestServer';
    $servObject = 'wsdl\\'.$servClass;
    $soap_server = new wsdl\soap_server( $servName, $servClass, new $servObject());
    
    $wsdlDir = __DIR__.'\\';
    //wsdl url location
    $wsdlUrl = 'http://localhost'.$_SERVER['SCRIPT_NAME'];
    $wsdlUrl = strtr($wsdlUrl,[strrchr($wsdlUrl,'/') => '']).'/RunServer.php';
    $wsdlUrl = $soap_server->getWsdlUrl($wsdlDir, $wsdlUrl);
    return $wsdlUrl;
}

try {
    $wsdlUrl = getWsdl();
    var_dump($wsdlUrl);
} catch (\SoapFault $e) {
    var_dump($e->getMessage());
}
