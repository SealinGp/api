<?php
namespace API\ext;
class xml
{
    //xml file path
    private  $file = '';
    
    //SimpleXMLElement object
    private  $simple_xml;
    
    /**
     * Xml constructor.
     *
     * @param string $file
     */
    public function __construct(string $file = '')
    {
        $this->set_file($file);
        
        unset($file);
    }
    
    /**
     * clean head '/' and foot '/',Win:change '\' to '/'
     *
     * @param string $path
     * @return void
     */
    protected function clean(string &$path):void
    {
        $path = strtr($path,['\\'=>'/']);
        
        stripos($path,'/') === 0 && $path = substr($path,1);
        
        strrpos($path,'/') == strlen($path) - 1 &&
        $path = substr_replace($path,'',strlen($path)-1,strlen($path)-1);
    }
  
    /**
     * get file mime
     *
     * @param string $file
     * @return string
     */
    protected  function mime(string $file):string
    {
        $res  = finfo_open(FILEINFO_MIME);
        $file = @finfo_file($res,$file);
        finfo_close($res);
        $file = !$file ? '' : $file;
        
        unset($res);
        return $file;
    }
    
    
    /**get the extension of file
     *
     * @param string $file  file path
     * @param bool   $check if check real
     * @return string
     */
    public function ext(string $file, bool $check = false):string
    {
        $file = (
            $check && false === realpath($file)
        ) ? '' : $file;
        $file = pathinfo($file,PATHINFO_EXTENSION);
        
        unset($check);
        return $file;
    }
    
    
    /**set xml file path
     *
     * @param string $file
     */
    public function set_file(string $file):xml
    {
        $this->file = ($file && 'xml' == $this->ext($file)) ?
            $file : '';
    
        $this->file = (string) (
            !($file && 'xml' == $this->ext($file)) ?: $file
        );
             
        
        unset($file);
        return $this;
    }
    
    //get xml file
    public function get_file():string
    {
        return $this->file;
    }
    
    /** alter xml file
     *  rmk:root tag start with second (rewrite file)
     *
     * @param  array $arr muti-relation array
     * ej1 :xml file content:<a>  <b> <c> 2 </c> </b>  </a> ==> $arr = ['b' => [ 'c' => 'value'] ];
     * ej2 :xml file content:<a> <b> <c>2</c> <c>3</c> </b> </a> ==> $arr = ['b' => [ 'c' => ['value1',''value2]] ];
     * @param  string $file file path
     * @return bool
     * */
    public function rewrite(array $arr, string $file = ''):bool
    {
        //check
        $file = realpath($file ?  : $this->file);
        
        //handle xml file -> xml string -> xml object  -> xml array-> array replace
        $xml  = @file_get_contents($file);
        $arr  = array_replace_recursive(json_decode(json_encode(new \SimpleXMLElement($xml)),true),$arr);
        $xml  = $this->getRootTag(['xmlStr' => $xml]);
        
        
        //xml array rewrite to xml file
        $return = ('' == $xml[0] || is_null($arr)) ?
            false                                                   :
            $this->write($this->to_str([ $xml[0] => $arr ]),$file);
        
        unset($xml,$file,$arr);
        return $return;
    }
    
    
    /** alter tag value from xml file
     * rmk:root tag start with second (rewrite file)
     *
     * @param  $arr one dimension array, '/' means the deep of dimension, '--index' means the index of the same level and name tag
     * ej1: xml文件:<a> <b> <c> 2 </c> </b> <a> ==>  $arr = ['b/c' => 'value'];
     * ej2 :xml文件:<a>  <b>2</b> <b>3</b>  </a> ==> $arr = ['b--1' => 'value'] ;
     * @return array keys:
     * 'success_num' success alter number
     * 'err_key'   failed alter index from $arr
     * 'status'     only true when alter all tag value from $arr successfully
     * */
    public function alter(array $arr,string $file = ''):array
    {
        //check
        $result = ['success_num' => 0,'err_key' => [],'status' => false];
        $file   = realpath($file ?  : $this->file);
        if (false === $file            ||
            empty($arr)                ||
            count($arr) != count($arr,1)) {
            return $result;
        }
        
        //transfer file to object
        $this->simple_xml = new \SimpleXMLElement(@file_get_contents($file));
        foreach ($arr as $tag => $value) {
            //decompose '/' ['b--a' => 'value'];
            $nodes   = explode("/", $tag);
            $len     = count($nodes);
            $xml_obj = $this->simple_xml;
            
            //one dimension tag
            if ($len == 1) {
                //decompose'--',find the position
                $apart = $this->separate($nodes[0]);
                $this->aim($xml_obj, $apart['left'],$apart['right']);
                //alter it and record it to $result
                $this->record($result,$file,$xml_obj,$tag,$value);
            } else {
            //change muti dimension tag
                foreach ($nodes as $index => $v) {
                    $apart = $this->separate($v);
                    if ($index == $len - 1) {
                        $this->aim($xml_obj, $apart['left'], $apart['right']);
                        $this->record($result,$file,$xml_obj,$tag,$value);
                    } else {
                        $this->aim($xml_obj, $apart['left'], $apart['right']);
                        //alter failed, record it to $result
                        if (null === $xml_obj) {
                            $result['err_key'][] = $tag;
                            break;
                        }
                    }
                }
            }
        }
        
        $result['status'] = $result['success_num'] == count($arr);
        
        unset($xml_obj, $arr,$apart,$file,$node,$len,$tag,$value,$index,$v);
        return  $result;
    }
    
    /**separate string $str with '--',get the left part and right part of $str
     *
     * @param string $str
     * @return array
     */
    private  function separate(string &$str):array
    {
        $result = [];
        //没有多个同级节点
        if (false === strrpos($str, '--')) {
            $result['left']  = $str;
            $result['right'] = 0;
        } else {
        //有多个同级节点
            $result['left']  = strstr($str, '--', true);
            $result['right'] = (int)strtr($str,[
                    $result['left'].'--' => ''
            ]);
        }
        
        unset($str);
        return $result;
    }
    
    //alter xml file and save it to $result
    private  function record(array &$result,string &$file, &$xml_obj, string &$index,string &$value):void
    {
        if ($this->simple_xml instanceof \SimpleXMLElement &&
            $xml_obj          instanceof \SimpleXMLElement ) {
            $result['success_num']+=1;
            //修改最里面一层节点的值
            $xml_obj[0]           = $value;
            //指针重置
            $xml_obj              = '';
            //保存已修改的xml数组
            $this->simple_xml->saveXML($file);
            return;
        }
        //标记修改失败的index
        $result['err_key'][] = $index;
        return;
    }
    
    
    /**access xml Object's attribute value
     *
     * @param \SimpleXMLElement $xml_obj
     * @param string            $att     \SimpleXMLElement's attribute
     * @param int               $index
     */
    private  function aim(\SimpleXMLElement &$xml_obj,string $att, int $index = 0):void
    {
        $xml_obj = $xml_obj->$att[$index];
    }
    
    /* write xml string to file
     * rmk: overwrite existed file
     *
     * @param string $file file path
     * @return bool
     * */
    public function write(string $str, string $file = ''):bool
    {
        //check
        $file = $file ? : $this->file;
        $this->clean($file);
        if ( !@is_dir(dirname($file))  ||
            $this->ext($file) != 'xml' ||
            false === @simplexml_load_string($str)  ) {
            return false;
        }
        //write
        false !== stripos('<?xml',$str)&&
        $str    = '<?xml version="1.0"?>'."\n".$str;
        $file   = file_put_contents($file,$str);
        
        $return = !(false === $file);
        unset($str,$file);
        return $return;
    }
    
    /**transfer xml string to array
     *
     * @param string $mixed xml file path or xml string
     * @return array
     */
    public  function to_arr(string $mixed):array
    {   //check
        $mixed = $mixed ? : $this->file;
        if (!$mixed  ||
            false ===
            ( $mixed = file_exists($mixed) ?
                @simplexml_load_file($mixed) : @simplexml_load_string($mixed)  )) {
            return [];
        }//transfer
        $mixed = json_decode(json_encode($mixed), true);
        $mixed = is_null($mixed) ? [] : $mixed;
        return $mixed;
    }
    
    /* transfer xml array to xml string
     *  you can add attribute when build xml string
     *  if add attribute you can write as 'tagName-attribute$value#' => 'tagValue'
     *
     * @param $xmlArr
     * @return xml string
     * */
    public function to_str(array $xmlArr):string
    {
        //init
        $DOMDocument               = new \DOMDocument("1.0", "utf-8");
        $DOMDocument->formatOutput = true;
        $DOMElement                = &$DOMDocument;
        //check xml format (only one root node except version node)
        if (count(array_keys($xmlArr)) != 1 ) return '';
        //start build xml string
        try {
            $this->setDom($xmlArr, $DOMElement, $DOMDocument);
            $return = $DOMDocument->saveXML();
        } catch (\Throwable $e) {
            $return =  $e->getMessage();
        }
        unset($DOMDocument,$DOMElement,$xmlArr);
        return $return;
    }
    
    //loop find the tag , tag value ,tag attribute, tag attribute value and set them
    private  function setDom (array $mixed,\DOMNode $domElement, \DOMNode $DOMDocument):void
    {
        foreach ($mixed as $index=>$mixedElement) {
            if (is_int($index)) {
                if ($index==0) {//create first
                    $node = $domElement;
                } else {//create muti same node
                    $node = $DOMDocument->createElement($domElement->tagName);
                    $domElement->selfNode->appendChild($node);
                }
            } else {
                //create node in document
                $index = $this->separate($index);
                $node = $DOMDocument->createElement($index['left']);
                
                //attributes append to element
                if ($index['right']) {
                    //filter last '#' if it existed
                    ($end = strrpos($index['right'], '#')) == strlen($index['right']) - 1 &&
                    $index['right'] = substr($index['right'], 0, $end);
                    $attributes = explode('#', $index['right']);
                    $end = [];
                    //pairs of attributes ,means how many number do string 'attr$val#' have
                    foreach ($attributes as $v) {
                        if (count($value = explode('$', $v)) == 2) {
                            $end[$value[0]] = $value[1];
                        }
                    }
                    $this->setAttr($end, $DOMDocument, $node);
                }
                $domElement->appendChild($node);
            }
            is_array($mixedElement) ?
                $this->setDom($mixedElement,$node,$DOMDocument)   :
                $this->setTagVal($mixedElement,$node,$DOMDocument);
        }
    }
    
    //set tag value,the end of loop
    public function setTagVal(string $value,\DOMNode $domElement,\DOMNode $DOMDocument):void
    {
        $domElement->appendChild($DOMDocument->createTextNode($value));
    }
    
    //dom element('a') create attribute('attr') value('val') :  <a attr=val></a>
    public function setAttr(array $attr,\DOMDocument &$dom,\DOMElement &$domEle):void
    {
        foreach ($attr as $k => $v) {
            //dom create attribute
            $attri = $dom->createAttribute(trim($k));
            $attri->value = trim($v);
            //element insert pairs of attributes
            $domEle->appendChild($attri);
        }
    }
    
   
    
    /* transfer xml array to xml file
     * @param array $xmlArr
     * */
    public function arrToFile(array $xmlArr, string $file = ''):bool
    {
        return $this->write($this->to_str($xmlArr), $file ? : $this->file);
    }
    
    //hook function
    /* mention: the following function is used for parase xml string ,and get value from it
     * xml string: <tagName attr=attrVal>   tagVal  </tagName>
     * tagName:tag name
     * attr: tag attribute name
     * attrVal: tag attribute value
     * tagVal: tag value
     * xmlStr: xml string
     * */
    
    /*
    * @param string $function
    * @param array $param  the parameters which function need
    * */
    public function hook(string $function,array $param):array
    {
        return is_callable(array('self',$function)) ?
            $this->$function($param) : [''];
    }
    //get functions and relevant required parameters
    public function getHookFun():string
    {
        $functions = [
            'getValByTagName' => [
                'getValByTagName(array $param)' => 'function name',
                '$param' => [
                    'xmlStr' => 'xml string',
                    'tagName' => 'tag name',
                ],
                'description' => 'xmlStr + tagName -> tag value'
            ],
            'getAttrValByTagName' => [
                'getAttrValByTagName(array $param)' => 'function name',
                '$param' => [
                    'xmlStr' => 'xml string',
                    'tagName' => 'tag name',
                    'attr' => 'tag attribute',
                ],
                'description' => 'xmlStr + tagName + attr -> tag attribute value'
            ],
            'getValByTagNameAtt' => [
                'getValByTagNameAtt(array $param)' => 'function name',
                '$param' => [
                    'xmlStr' => 'xml string',
                    'tagName' => 'tag name',
                    'attr' => 'tag attribute name',
                    'attrVal' => 'tag attribute value',
                ],
                'description' => 'xmlStr + tagName + attr + attrVal -> tag value'
            ],
            'getAttrValByAttr' => [
                'getAttrValByAttr(array $param)' => 'function name',
                '$param' => [
                    'xmlStr' => 'xml string',
                    'tagName' => 'tag name',
                    'attr' => 'tag attribute name',
                    'attrVal' => 'tag attribute value',
                    'searchAttr' => 'tag attribute name searched',
                ],
                'description' => 'xmlStr + tagName + attr + attrVal + searchAttr -> tag attribute value searched'
            ],
            'getRootTag' => [
                'getRootTag(array $param)' => 'function name',
                '$param' => [
                    'xmlStr' => 'xml string',
                ],
                'description' => 'xmlStr -> root tag name'
            ],
            'levelSearch' => [
                'levelSearch(array $param)' => 'function name',
                '$param' => [
                    'xmlStr' => 'xml string',
                    'tagName' => 'tag name',
                    'attr' => 'tag attribute name',
                    'attrVal' => 'tag attribute value',
                    'level' => 'tag level(root tag level is 1)'
                ],
                'description' => 'xmlStr + tagName + attr + attrVal + level -> tag value',
            ],
        ];
        return json_encode($functions,JSON_PRETTY_PRINT);
    }
    
    /*find the tag value with tag name
    @param array $param keys:tagName,xmlStr
    @return array $value tag values
     * */
    private  function getValByTagName(array $param):array
    {
        $funParam = array_keys((json_decode($this->getHookFun(),true))['getValByTagName']['$param']);
        $param = $this->checkParam($funParam, $param);
        if (!$param) return [''];
        $param['type'] = 'tagVal';
        $value = $this->strSearch($param);
        return $value;
    }
    /*find the tag attribute value
    *@param array $param keys:tagName,xmlStr,attr
     * */
    private  function getAttrValByTagName(array $param):array
    {
        $funParam = array_keys((json_decode($this->getHookFun(),true))['getAttrValByTagName']['$param']);
        $param = $this->checkParam($funParam, $param);
        if (!$param) return [''];
        $param['type'] = 'attrVal';
        $value = $this->strSearch($param);
        return $value;
    }
    /*find the tag value with tag name,attribute,attribute value
    @param array $param keys:tagName,xmlStr，attr,attrVal
    @return array $value  tag values
     * */
    private  function getValByTagNameAtt(array $param):array
    {
        $funParam = array_keys((json_decode($this->getHookFun(),true))['getValByTagNameAtt']['$param']);
        $param = $this->checkParam($funParam, $param);
        if (!$param) return [''];
        $param['type']='tagValByAttr';
        $value = $this->strSearch($param);
        return $value;
    }
    
    /*find the attribute value with tag name,attribute,attribute value,attribute
     @param array $param keys:tagName,xmlStr，attr,attrVal,searchAttr
     @return array $value attribute values
     * */
    private  function getAttrValByAttr(array $param):array
    {
        $funParam = array_keys((json_decode($this->getHookFun(),true))['getAttrValByAttr']['$param']);
        $param = $this->checkParam($funParam, $param);
        if (!$param) return [''];
        $param['type']='attrValByAttr';
        $value = $this->strSearch($param);
        return $value;
    }
    
    /*find the root tag
     @param array $param keys: xmlStr
     * */
    private  function  getRootTag(array $param):array
    {
        $funParam = array_keys((json_decode($this->getHookFun(),true))['getRootTag']['$param']);
        $param = $this->checkParam($funParam, $param);
        if (!$param) return [''];
        $param['type']='getRoot';
        $value = $this->strSearch($param);
        return $value;
    }
    
    private  function levelSearch(array $param):array
    {
        $funParam = array_keys((json_decode($this->getHookFun(),true))['levelSearch']['$param']);
        $param = $this->checkParam($funParam, $param);
        if (!$param) return [''];
        $param['type']='level';
        //search
        $value = $this->strSearch($param);
        return $value;
    }
    
    /*check input parameters
    *@param array $funParam function required parameters
     * @param array $inputParam input parameters
     * @return array if failed []
     * */
    private  function checkParam(array $funParam, array $inputParam):array
    {
        
        foreach ($inputParam as $k1 => $v1) {
            if (!in_array($k1, $funParam,true)){
                $inputParam = [];
                break;
            }
        }
        return $inputParam;
    }
    /*decompose xml string to one dimension array
     *@param string $xmlStr xml string
     *@return array $xmlArr xml array (include all tag,tag value,attribute,attribute name,level)
      * */
    private  function strParse(string $xmlStr):array
    {
        $resouce = xml_parser_create();
        xml_parser_set_option($resouce, XML_OPTION_SKIP_WHITE, 1);
        xml_parser_set_option($resouce, XML_OPTION_CASE_FOLDING, 0);
        $xmlStr = xml_parse_into_struct($resouce, $xmlStr, $xmlArr);
        if (!$xmlStr) return [];
        foreach ($xmlArr as $k=>$v) {
            if ($v['type']=='cdata' || $v['type']=='close') {
                unset($xmlArr[$k]);
            }
        }
        xml_parser_free($resouce);
        return $xmlArr;
    }
    
    /* search in the xml array
     * @param array $param input parameters
     * @return array $value values if failed $value = [ 0 => '']
     * */
    private  function strSearch(array $param):array
    {
        $xmlArr =  $this->strParse($param['xmlStr']);
        $value = array();//save values
        if (!$xmlArr) return $value[''];
        $end = false;
        fo1:foreach ($xmlArr as $k => $item) {
        sw2: switch ($param['type']) {
            case 'getRoot':
                $item['level'] == 1 ? $value[0] = $item['tag'] : true;
                $end = true;
                break 2;
            case 'level':
                if ($item['level'] == (int)$param['level']) {
                    $attr = isset($item['attributes'][$param['attr']]) && $item['attributes'][$param['attr']] == $param['attrVal'] ? true : false;
                    if ($attr) {
                        $value[] = $item['value'] ??  '';
                    }
                }
                break 1;
            default:
                //based on tag name
                if ($item['tag'] == $param['tagName']) {
                    sw3: switch ($param['type']) {
                        case 'tagVal':
                            $value[] = isset($item['value']) ? $item['value'] : '';
                            break 2;
                        case 'attrVal':
                            $value[] = isset($item['attributes'][$param['attr']]) ? $item['attributes'][$param['attr']] : '';
                            break 2;
                        case 'tagValByAttr':
                            $attr = (isset($item['attributes'][$param['attr']]) && $item['attributes'][$param['attr']]==$param['attrVal'])?true:false;
                            if ($attr) {
                                $value[] = isset($item['value'])?$item['value'] : '';
                            }
                            break 2;
                        case 'attrValByAttr':
                            $attr = (isset($item['attributes'][$param['attr']]) && $item['attributes'][$param['attr']]==$param['attrVal'])?true:false;
                            if ($attr) {
                                $value[] = isset($item['attributes'][$param['searchAttr']]) ? $item['attributes'][$param['searchAttr']] : '';
                            }
                            break 2;
                    }
                };
                break 1;
        }
        if ($end) {
            break 1;
        }
    }
        if (!$value) {
            $value = [''];
        }
        return $value;
    }
    
    
}
