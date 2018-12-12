<?php
namespace API\company;
include "../autoload.php";
use API\ext\xml;
/* 支持认我们的单号
 * 换号模式: alljoy API换号 tracking number(alljoy api返回字段) = order_no(alljoy api请求参数字段) => service_no(alljoy数据库字段服务商单号)
 * */
class Aras
{
    private static $_env = [
        'test' => [//测试环境
            'user' => 'neodyum',
            'pass' => 'nd2580',
            'create' => 'http://customerservicestest.araskargo.com.tr/arascargoservice/arascargoservice.asmx'
        ],
        'product' => [//正式环境
            'user' => 'khl',
            'pass' => '5pe3x82xs7',
            'create' => 'http://customerws.araskargo.com.tr/arascargoservice.asmx',
            'track' => [
                'user' => 'alljoy',
                'pass' => 'QuanHeYue_88888',
                'CustomerCode' => '1724944311544',
                'url' => 'http://customerservices.araskargo.com.tr/ArasCargoCustomerIntegrationService/ArasCargoIntegrationService.svc?singleWsdl',
            ]
        ]
    ];
    //结果码
    private static $_errorMsg = [
        '0' => 'success',
        '70020' => 'Total piece number doesn’t equal to part numbers that was sent before',
        '70021' => 'Total piece number must be sent | PieceCount is not equal with BarcodeNumber',
        '70025' => 'File type of shipment has to be one piece ',//不填写重量，默认货物为文件类型,文件类型的数量跟barcode必须为1
        '70026' => 'Volume type must be decimal ',
        '70028' => 'Weight type must be decimal',
        '70029' => 'Shipment was processed. You cannot update',
        '70031' => 'Order code shipment was processed',
        '1000' => 'User Name and Password are wrong',
        '70019' => 'Invoice Key field can be maximum 20 digit',
        '70121' => 'TradingWaybillNumber field can be maximum 16 digit',
        '70023' => 'The volume information is missing from the order number.',
    ];
    /**创建物流订单
     * @param array $param 请求参数
     * @param bool $env 环境
     * */
    public function orderCreate(array $param, $env = false)
    {
        //环境
        $env = !$env ? 'test' : 'product';
        //请求参数设置
        $url = (self::$_env)[$env]['create'];
        $details = array();
        $requestData = [
            'soap:Envelope--xmlns:xsi$http://www.w3.org/2001/XMLSchema-instance#            
            xmlns:xsd$http://www.w3.org/2001/XMLSchema#
            xmlns:soap$http://schemas.xmlsoap.org/soap/envelope/#' => [
                'soap:Body'=> [
                    'SetOrder--xmlns$http://tempuri.org/#' => [
                        'orderInfo' => [
                            'Order' => [
                                //必填参数
                                'IntegrationCode'      => $param['order_no'],               //订单号(客户识别码)
                                'TradingWaybillNumber' =>                                   //贸易账单号
                                    substr($param['order_no'],0,strlen($param['order_no']) - 6),
                                'InvoiceNumber'        => $param['order_no'],               //发票号码
                                //收件人信息
                                'ReceiverName'         => $param['recipientName'],          //名字
                                'ReceiverAddress'      =>                                   //地址
                                    $param['recipientCountryCode'].' '.$param['recipientProvince'].' '.
                                    $param['recipientCity'].' '.$param['recipientStreet'].' '.
                                    $param['recipientStreetNo'],
                                'ReceiverPhone1'       => $param['recipientPhone'],         //电话
                                'ReceiverCityName'     => $param['recipientCity'],          //城市
                                'ReceiverTownName'     => $param['recipientCity'],          //城镇
                                'PieceCount'           => $param['orderPieces'],            //包裹数量
                                'PayorTypeCode'        => '0',                              //付款:0发件人付,1收件人付
                                'IsWorldWide'          => '1',                              //国际包裹 是:1 不是:0
                                'BarcodeNumber'        => $param['order_no'],               //条形码
                                //可选参数
                                'ReceiverPhone2'       => $param['recipientTel'],           //收件人电话2
                                'ReceiverPhone3'       => '',                               //收件人电话3
                                'VolumetricWeight'     =>                                   //总体积
                                    (float)$param['length']*(float)$param['length']*(float)$param['length'],
                                'Weight'               => $param['orderWeight'],            //总重量
                                'SpecialField1'        => '',                               //备注字段1
                                'SpecialField2'        => '',                               //备注字段2
                                'SpecialField3'        => '',                               //备注字段3
                                'IsCod'                =>                                   //COD订单 0:不是,1:是
                                    'D' == $param['payType'] ? '1':'0',
                                'CodAmount'            => 'D' == $param['payType'] ?        //COD费用
                                    $param['salesMoney'] : '',
                                'CodCollectionType'    => '0',                              //COD付款方式 0:现金 1:信用卡
                                'CodBillingType'       => '0',                              //此次运输总费是否包括商品费用,0|1(分开发票)
                                'Description'          => $param['parcelType'],             //包裹描述
                                'TaxNumber'            => $param['recipientTaxNo'],         //税号
                                'TaxOffice'            => '',                               //收税办公司代码
                                'PrivilegeOrder'       => '',                               //交货的优先级
                                'Country'              => '',                               //交货的优先级
                                'CountryCode'          => '',                               //交货的优先级
                                'CityCode'             => '',                               //city code
                                'TownCode'             => '',                               //town code
                                'ReceiverDistrictName' => '',                               //地区名称
                                'ReceiverQuarterName'  => '',                               //季度名称
                                'ReceiverAvenueName'   => '',                               //大道名称
                                'UnitID'               => '',                               //大道名称
                                'ReceiverStreetName'   => $param['recipientStreet'],        //街道名称
                                'PieceDetails'         => &$details,                        //包裹细节
//                                'SenderAccountAddressId' => ''
//                                'TtDocumentId' => '11',
                            ]
                        ],
                        'userName' => self::$_env[$env]['user'],
                        'password' => self::$_env[$env]['pass']
                    ]
                ]
            ]
        ];
        for ($i=0;$i<$param['orderPieces'];$i++) {//多个箱子的内容
            $details[$i]['PieceDetail']['BarcodeNumber'] =  $param['order_no'];             //条形码
            $details[$i]['PieceDetail']['VolumetricWeight'] =                               //体积
                (float)$param['length']*(float)$param['length']*(float)$param['length'];
            $details[$i]['PieceDetail']['Weight'] =                                         //重量
                $param['declareItems'][$i]['declareWeight'];
            $details[$i]['PieceDetail']['Description'] =                                    //描述
                $param['declareItems'][$i]['declareEnName'];
            $details[$i]['PieceDetail']['ProductNumber'] = '';                              //产品码
        }
        $requestData = xml::arrToStr($requestData);

        //开始请求
        $result = self::curlRaw($url, $requestData);

        //请求结果解析
        $tags = [//aras 返回结果字段
            'ResultCode' => '',
            'ResultMessage' => '',
        ];
        foreach ($tags as $k => $v) {
            $param1 = [
                'xmlStr' => $result['data'],
                'tagName' => $k,
            ];
            $tagVal = xml::hook('getValByTagName',$param1);
            $tags[$k] = $tagVal[0];
        }
        //对面Aras系统报错或Aras请求失败(带Aras错误信息)或curl请求失败(带curl错误信息)
        if ('' === $tags['ResultCode'] ||
            $tags['ResultCode']!='0' ||
            'failed'==$result['status']) {
            LogServer::error('1','Aras',$requestData,$result['data']);
            $result = [
                'status' => 'failed',
                'msg' => in_array($tags['ResultCode'],array_keys(self::$_errorMsg)) ?
                    self::$_errorMsg[$tags['ResultCode']] : (''==$tags['ResultMessage'] ?
                        'Aras api server error.time:'.date('Y/m/d H:i:s',time()) : $tags['ResultMessage'])
            ];
            return $result;
        }

        //成功
        LogServer::success('1','Aras',$requestData,$result['data']);
        $result = [
            'status' => 'success',
            'trackingNo' => $param['order_no']
        ];
        return $result;
    }

    //curl请求
    private static function curlRaw(string $url, string $paramStr)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $paramStr,
            CURLOPT_HTTPHEADER => [
                'Cache-Control: no-cache',
                'Content-Type: text/xml',
            ],
        ));
        $data = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $result = [
            //curl请求失败/成功
            'status' => $err ? 'failed' : 'success' ,
            //源数据的转换
            'data' => $err ? $err :  $data ,
        ];
        return $result;
    }

    public function orderQuery(string $service_no,bool $env = true) {
        $env = !$env ? 'test' : 'product';
        $trackNumberType = [ 14, 22, 26];
        $integergationType = [
            1,
            15,
            21,
            30,
            100,//Could not find any recognizable digits
            102,
            26,//不可用 There is no row at position 0
            9,//字段数量 < 15
            11//未知返回值  英文文档:未说明, 土耳其文档:没有此方法 ?
        ];
        try {
            $soap = new \SoapClient(self::$_env[$env]['track']['url']);
            $requestData = [
                'loginInfo' => [
                    'LoginInfo' => [
                        'UserName' => self::$_env[$env]['track']['user'],
                        'Password' => self::$_env[$env]['track']['pass'],
                        'CustomerCode' => self::$_env[$env]['track']['url'],
                    ]
                ],
                'queryInfo' => [
                    'QueryInfo' => [
                        'QueryType' => '22',
                        'IntegrationCode' => $service_no,
                        'TrackingNumber' => $service_no,
                    ]
                ]
            ];
            $requestData['loginInfo'] = xml::arrToStr($requestData['loginInfo']);
            $requestData['queryInfo'] = xml::arrToStr($requestData['queryInfo']);

            $data = json_decode(json_encode($soap->GetQueryDS($requestData)),true);

            var_dump($data);
//            if (!isset($data['GetQueryDSResult']))
            exit;
        } catch (\SoapFault $e) {
            return $result = [
                'stauts' => 'failed',
                'msg' => $e->getMessage()
            ];
        } catch (\Throwable $e) {
            return $result = [
                'stauts' => 'failed',
                'msg' => $e->getMessage()
            ];
        }

    }

    public function echoxml($str)
    {
        $str = <<<xml
$str
xml;
        header('content-type:application/xml');
        echo $str;
    }

}
