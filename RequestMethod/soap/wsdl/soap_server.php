<?php
namespace wsdl;
class soap_server
{
    private $_conf = [
        'servName' => '',
        'servClass' => '',
        'servObject' => ''
    ];
    
    //enter
    const n = "\n";
    
    //wsdl file path
    private $_wsdlFilePath = '';
    
    /**init
     * soap_server constructor.
     * @param string $servName  soap server name
     * @param string $servClass soap handle class
     * @param HandleClass $servObject soap handle class instance
     */
    public function __construct(string $servName,string $servClass,HandleClass $servObject)
    {
        $this->setWsdl($servName, $servClass, $servObject);
    }
    public function setWsdl (string $servName,string $servClass,HandleClass $servObject):void
    {
        foreach ($this->_conf as $k => $v) {
            ($this->_conf)[$k] = $$k;
        }
    }
    
    /**get wsdl url (build it)
     * @param string $wsdlFilePath
     * @param string $location
     * @return string
     * @throws Exception
     */
    public function getWsdlUrl (string $wsdlFileDir,string $location = ''):string
    {
        $object = $this->_conf['servObject'];
        $class = new \ReflectionClass($object);
        if (!$class->isInstantiable()) {//检测此类是否可实例化
            throw new Exception('Class is not instantiable.');
        }
        $wsdl = [
            'definitions' => '',
            'types' => '',
            'message' => '',
            'portType' => '',
            'binding' => '',
            'serviceWSDL' => ''
        ];
        
        $wsdl['definitions'] = '<?xml version="1.0" encoding="UTF-8"?>'.self::n.
            '<definitions name="'.$this->_conf['servName'].'" '.
            'targetNamespace="http://tempuri.org/wsdl/" '.
            'xmlns:wsdlns="http://tempuri.org/wsdl/" '.
            'xmlns:typens="http://tempuri.org/xsd" '.
            'xmlns:xsd="'.XSD_NAMESPACE.'" '.
            'xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/" '.
            'xmlns:stk="http://schemas.microsoft.com/soap-toolkit/wsdl-extension" '.
            'xmlns="http://schemas.xmlsoap.org/wsdl/">'.self::n;
        
        
        $wsdl['types'] = '<types>'.self::n.
            '<xsd:schema targetNamespace="http://tempuri.org/xsd" '.
            'xmlns="'.XSD_NAMESPACE.'" '.
            'xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" '.
            'xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/" '.
            'elementFormDefault="qualified">'.self::n.
            '</xsd:schema>'.self::n.
            '</types>'.self::n;
        
        $wsdl['portType'] .= "<portType name='".$this->_conf['servName']."'>".self::n;
        
        $wsdl['binding'] .= "<binding name='".$this->_conf['servName']."Binding' type='wsdltns:".$this->_conf['servName']."'>".self::n.
            '<stk:binding preferredEncoding="utf-8"/>'.
            '<soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http" />'.self::n;
        
        $methods = $class->getMethods();
        
        foreach ($methods as $method) {
            if ($method->isPublic() && !$method->isConstructor()) {
                //request
                $wsdl['message'] .= '<message name="' . $method->getName() . 'Request">' . self::n;
                $params = $method->getParameters();
                foreach ($params as $param) {
                    $wsdl['message'] .= '<part name="' . $param->getName() . '" type="xs:'.$param->getType()->__toString().'" />' . self::n;
                }
                $wsdl['message'] .= '</message>'.self::n;
                
                //response                                
                $wsdl['message'] .= '<message name="' . $method->getName() . 'Response">' . self::n;
                $wsdl['message'] .= '<part name="' . $method->getName() . '" type="xs:'.$param->getType()->__toString().'" />' . self::n;
                $wsdl['message'] .= '</message>'.self::n;
                
                //port type
                $wsdl['portType'] .= '<operation name="' . $method->getName() . '" >' . self::n;
                $wsdl['portType'] .= '<input message="' . $method->getName() . 'Request" />' . self::n;
                $wsdl['portType'] .= '<output message="' . $method->getName() . 'Response" />' . self::n;
                $wsdl['portType'] .= "</operation>".self::n;
                
                //binding
                $wsdl['binding'] .= '<operation name="' . $method->getName() . '">' . self::n.
                    '<soap:operation soapAction="http://tempuri.org/action/'.
                    $this->_conf['servName'].'.'.$method->getName().'"/>' . self::n.
                    
                    '<input>'.self::n.
                    '<soap:body use="encoded" namespace="urn:' . $this->_conf['servName'] .'" '.
                    'encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>' . self::n.
                    '</input>'.self::n.
                    
                    '<output>'.self::n.
                    '<soap:body use="encoded" namespace="urn:' . $this->_conf['servName'] . '"
                                     encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>' . self::n.
                    '</output>'.self::n.
                    
                    '</operation>'.self::n;
                
            }
        }
        $wsdl['portType'] .= '</portType>'.self::n;
        $wsdl['binding'] .= '</binding>'.self::n;
        
        $location = $location?:'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['PHP_SELF'];
        $wsdl['service'] ='<service name="'.$this->_conf['servName'].'">'.self::n.
            '<port name="'.$this->_conf['servName'].'Port" binding="wsdlns:'.$this->_conf['servName'].'Binding">'.self::n.
            "<soap:address location='{$location}' />".self::n.
            "</port>".self::n.
            "</service>".self::n;
        
        $wsdlStr = '';
        foreach ($wsdl as $v) {
            $wsdlStr .= $v;
        }
        $wsdlStr .= '</definitions>';
        
        //wsdl file and string
        $this->_wsdlFilePath = $wsdlFileDir.'/' . $this->_conf['servClass'] . ".wsdl";
        
        $fso = @fopen($this->_wsdlFilePath, "w");
        if (!$fso) return '';
        fwrite($fso, $wsdlStr);
        fclose($fso);
        unset($wsdl,$wsdlStr);
        $location = $location.'?wsdl';
        return $location;
    }
    
    /**run soap server
     * @param int $version SOAP_1_2 | SOAP_1_1
     * @return array $result
     */
    public function run(string $wsdlDir,$version = SOAP_1_2)
    {
        if (isset($_GET['wsdl'])) {
            $result = [
                'status' => 'failed',
                'msg' => ''
            ];
            try {
                $wsdlDir = $wsdlDir ? : $this->_wsdlFilePath;
                $wsdlDir = $wsdlDir.'/'.$this->_conf['servClass'].'.wsdl';
                $wsdlDir = realpath($wsdlDir);
                $soapSever = new \SoapServer($wsdlDir,array('soap_version' => $version));
                //set soap server
                $soapSever->setObject($this->_conf['servObject']);
                //run
                $soapSever->handle();
            } catch (\Throwable $e) {
                var_dump($e->getMessage());
            }
            unset($this->_conf);
            
        }
    }
    
    
}