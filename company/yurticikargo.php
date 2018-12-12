<?php
class yurticikargo {//支持一次请求 批量查询,批量创建订单,一票到底
    //环境
    private static $_env = [
        'test' => [
            'user' => 'YKTEST',
            'pass' => 'YK',
            'url' => 'http://testwebservices.yurticikargo.com:9090/KOPSWebServices/ShippingOrderDispatcherServices?wsdl',
        ],
        'product' => [
            'user' => '',
            'pass' => '',
            'url' => 'http://webservices.yurticikargo.com:8080/KOPSWebServices/ShippingOrderDispatcherServices?wsdl',
        ]
    ];
    //创建订单
    public function createOrder($param = [], $env = false) {
        //返回值格式
        $result = [
            'status' => '',
            'data' => []
        ];

        //设置参数,环境
        $url = 'createOrder';
        $this->setCreateOrderParam($param, $url, $env);

        //开始请求
        try {
            $soap = new \SoapClient($url);
            $data = json_decode(json_encode($soap->createShipment($param)),true);
            $data = $data['ShippingOrderResultVO'] ? : '';
            //返回结果处理
            switch (isset($data['outFlag']) ? $data['outFlag'] : '2') {
                case '0'://成功
                    $result['status'] = 'success';
                    $result['data'] = [
                        'count' => $data['count'] ? : '0',//请求成功的订单数量(支持批量创建订单)
                        'jobId' => $data['jobId'] ? : '0',//Yurtici kargo Request number 请求记录号
                        'result' => $data['shippingOrderDetailVO'] ? : [],//该参数中含有cargoKey(物流单号),invoiceKey
                    ];
                    break;
                case '1'://失败
                    $result['status'] = 'failed';
                    $result['data'] = $data;
                    break;
                case '2'://服务器异常
                    $result['status'] = 'failed';
                    $result['data'] = [
                        'errCode' => '0',
                        'errMsg' => 'ykcargo server exception',
                    ];
                    break;
            }
        } catch (\SoapFault $e) {
            $result['status'] = 'failed';
            $result['data']['errCode'] = $e->getCode();
            $result['data']['errMsg'] = $e->getMessage();
        }
        return $result;
    }
    //创建订单的参数设置和说明
    private function setCreateOrderParam(array &$param, &$url, &$env) {
        $env = !$env ? 'test' : 'product';
        $url = self::$_env[$env]['url'];
        $param = [
            //环境+token
            'wsUserName' => self::$_env[$env]['user'],
            'wsPassword' => self::$_env[$env]['pass'],
            'userLanguage' => 'EN',
            'ShippingOrderVO' => [
//            //必填参数
                [
                    'cargoKey' => $param['cargoKey'] ? : '55000290',//barcode 唯一  55000282,3,4,5,6,7,8, 55000290,1
                    'invoiceKey' => $param['invoiceKey'] ? : 'A5500030',//invoice number (If shipment is cargo based,cargokey唯一,invocieKey给多个)
                    'receiverCustName' => $param['receiverCustName'] ? : 'MEHMET',//收件人名称
                    'receiverAddress' => $param['receiverAddress'] ? : 'Eski Büyük Dere Caddesi No.3',//收件人地址
                    'receiverPhone1' => $param['receiverPhone1'] ? : '02123652426',//收件人电话
                    'cargoCount' => $param['cargoCount'] ? : (int)'1',//货物数量
                    'taxOfficeId' => $param['taxOfficeId'] ? : '',//税务局id
                    'ttDocumentId' => $param['ttDocumentId'] ? : '',//COD document number
                    'dcSelectedCredit' => $param['dcSelectedCredit'] ? : '',//? instalment  choice  of receiver customer.邮件询问中
                    'dcCreditRule' => $param['dcCreditRule'] ? : '0',//信用卡规则,0:合作信用卡 1:单笔非合作信用卡
                    //可选参数
//ttCollectionType 填写时,ttInvoiceAmount,ttDocumentSaveType 必填
//                'ttCollectionType' => '0',//COD付款方式 0:现金 1:信用卡
//                'ttInvoiceAmount' => (double)'',//double(18,2) COD amount,
//                'ttInvoiceAmountSpecified' => (bool)'',//若desi填写,则此参数必填
//                'kg' => (double)'',//货物重量
//                'kgSpecified' => (bool)'',//若desi填写,则此参数必填

//                'receiverPhone2' => '',
//                'receiverPhone3' => '',
//                'cityName' => 'İstanbul',//收件人城市
//                'townName' => 'Maslak',//收件人城镇
//                'specialField1' => '',//备注说明,备注格式为  fieldName$value# ,可多个 字段名称$字段值#.字段名称$字段值#
//                'specialField2' => '',
//                'specialField3' => '',
//                'ttDocumentSaveType' => '',//是否包括COD产品服务费用,是否单独开具发票,0:相同发票,1:单独发票
//                'orgReceiverCustId' => '',//收件人customer code
//                'description' => '',//描述
//                'taxNumber' => '',//税号
//                'taxOfficeName' => '',税务局名称
//                'orgGeoCode' => '',//客户地址代码 Customer address code
//                'emailAddress' => '',//收件人邮箱

//                'custProdId' => '',//product code,?当客户系统中product code关联 ?deci跟kg 信息时,可使用此参数来代表deci跟kg信息 邮件询问中
//                'desi' => (double)'',//cargo desi?若user账号有transmit data的权限,则传输的Deci- data会被记录并且delivery时由此参数限制 邮件询问中
//                'desiSpecified' => (bool)'',//bool 若desi填写,则此参数必填
//                'waybillNo' => '',// If shipment is commercial,此参数必填
//                'privilegeOrder' => '',//dispatch center定义的优先级订单
                ],
                [//支持 批量创建订单
                    'cargoKey' => $param['cargoKey'] ? : '55000291',//barcode 唯一 55000282
                    'invoiceKey' => $param['invoiceKey'] ? : 'A5500030',//invoice number (If shipment is cargo based,cargokey唯一,invocieKey给多个)
                    'receiverCustName' => $param['receiverCustName'] ? : 'MEHMET',//收件人名称
                    'receiverAddress' => $param['receiverAddress'] ? : 'Eski Büyük Dere Caddesi No.3',//收件人地址
                    'receiverPhone1' => $param['receiverPhone1'] ? : '02123652426',//收件人电话
                    'cargoCount' => $param['cargoCount'] ? : (int)'1',//货物数量
                    'taxOfficeId' => $param['taxOfficeId'] ? : '',//税务局id
                    'ttDocumentId' => $param['ttDocumentId'] ? : '',//COD document number
                    'dcSelectedCredit' => $param['dcSelectedCredit'] ? : '',//? instalment  choice  of receiver customer.邮件询问中
                    'dcCreditRule' => $param['dcCreditRule'] ? : '0',//信用卡规则,0:合作信用卡 1:单笔非合作信用卡
                ]

            ],

        ];

    }

    //查询订单
    // yk cargo bug 说明:请求参数keys = ''时,其他参数正常, 查询状态显示成功,但是实际上没有查询结果,有错误码+错误信息,
    //通过在输入(请求)参数 keys = ''时, 将keys = null设置为未定义状态,可显示查询失败状态
    public function trackOrder($param = [], $env = false) {
        //返回格式
        $result = [
            'status' => '',
            'data' => []
        ];
        //参数环境设置
        $url = 'trackOrder';
        $this->setTrackOrderParam($param,$url,$env);

        //开始请求
        try {
            $soap = new \SoapClient($url);
            $data = json_decode(json_encode($soap->queryShipment($param)),true);
            $data = $data['ShippingDeliveryVO'] ? : '';
            return $data;
            //请求结果处理
            switch (isset($data['outFlag']) ? $data['outFlag'] : '2') {
                case '0':
                    $history1 = $data['shippingDeliveryDetailVO']['shippingDeliveryItemDetailVO'] ? : '';//当前轨迹细节
                    $history2 = $data['shippingDeliveryDetailVO']['ShippingDeliveryItemDetailVO']['invDocCargoVOArray'] ? : '';//历史轨迹细节
                    $result = [
                        'status' => 'success',
                        'data' => [
//                            $data['shippingDeliveryDetailVO']['cargoKey'] ? : 'unknown' => [
//                                'code' => $data['shippingDeliveryDetailVO']['operationCode'] ? : '',
                            'descripition' => $data['shippingDeliveryDetailVO']['operationMessage'] ? : '',//Shipment status description
                            'history1' => [
                                'transCenter' => $history1['arrivalTrCenterName'] ? : '',//位置: 转运中心名
//                                'UnitName' => $history1['arrivalUnitName'] ? : '',//位置: 转运中心名 (缩写)
//                                'ShipmentStatus' => $history1['cargoEventExplanation'] ? : '',//事件: 该转运中心事件描述(缩写)
                                'CargoDescription' => $history1['cargoReasonExplanation'] ? : '',//事件: 该转运中心事件描述

                                'delveryName' => $history1['delEmpName'] ? : '',//送货人员名称
                                'delDate' => $history1['deliveryDate'] ? : '',//送货日期
                                'delTime' => $history1['deliveryTime'] ? : '',//送货时间
                                'delDes' =>  $history1['deliveryTypeExplanation'] ? : '',//事件: 送货描述
                                'deliveryUnitName' => $history1['deliveryUnitName'] ? : '',//位置: 送货单位名
                                'deliveryUnitTypeExplanation' => $history1['deliveryUnitTypeExplanation'] ? : '',//事件: 送货单位状态描述
                                'departureTrCenterName' => $history1['departureTrCenterName'] ? : '',//位置: 出发转机中心名称
                                'departureUnitName' => $history1['departureUnitName'] ? : '',
                                //退货相关
//                                'rejectStatusDes' => $history1['rejectStatusExplanation'] ? : '',///拒绝收货状态解释
//                                'rejectDes' =>  $history1['rejectDescription'] ? : '',//拒绝收货描述
//                                'rejectReasonExplanation' =>  $history1['rejectReasonExplanation'] ? : '',//拒绝收货原因
//                                'returnDeliveryDate' => $history1['returnDeliveryDate'] ? : '',//退货日期
//                                'returnDeliveryDate' => $history1['returnStatusExplanation'] ? : '',//返回状态解释
                            ],
                            'history2' => [

                            ],
//                            ]
                        ],
                    ];
                    if ($history2) {
                        foreach ($history2 as $k => $v) {
                            //时间格式 yyyy-mm-dd HH:ii:ss
                            $result['data']['history2'][$k]['time'] =  self::dateFormat($v['eventDate']).' '.self::timeFormat($v['eventTime']);
                            //事件+地点
                            $result['data']['history2'][$k]['descripition'] = $v['eventName'].' in '.$v['unitName'];
                        }
                    }
                    break;
                case '1'://对方接口报错
                    $result = [
                        'status' => 'failed',
                        'data' => [
                            'errCode' => $data['shippingOrderDetailVO']['errCode'] ? : 'unknown error',
                            'errMsg' => $data['shippingOrderDetailVO']['errMessage'] ? : 'unknown error',
                        ]
                    ];
                    break;
                case '2'://对方接口出问题了
                    $result = [
                        'status' => 'failed',
                        'data' => [
                            'errCode' => '2',
                            'errMsg' => 'ykcargo server exception',
                        ]
                    ];
                    break;
            }
        } catch (\SoapFault $e) {
            $result = [
                'status' => 'failed',
                'data' => [
                    'errCode' => $e->getCode(),
                    'errMsg' => $e->getMessage()
                ]
            ];
        }
        return $result;
    }
    //查询订单参数设置和说明
    private function setTrackOrderParam(array &$param, &$url,&$env){
        $env = !$env ? 'test' : 'product';
        $url = self::$_env[$env]['url'];

        $param =  [
            //环境+token
            'wsLanguage' => 'EN',
            'wsUserName' => self::$_env[$env]['user'],
            'wsPassword' => self::$_env[$env]['pass'],
            //必填参数
            'keys' => $param['key'] ? : ['55000281'],//查询码
            'keyType' => $param['keyType'] ? : (int)'0',//查询类型 int  0: cargo key, 1: Invoice key
            //选填参数
            'addHistoricalData' => $param['addHistoricalData'] ? :  false,//是否展示历史运输记录 默认false,建议false(非必要情况下)
            'onlyTracking' => $param['onlyTracking'] ? :false,//是否只返回tracking url,默认false
        ];
    }


    //日期格式转化 $date YYmmdd => YY-mm-dd
    private function dateFormat(string $date){
        return substr($date,0,4).'-'.substr($date,4,2).'-'.substr($date,6,2);
    }
    //时间格式转化 $time HHiiss => HH:ii:ss
    private function timeFormat(string $time){
        return substr($time,0,2).':'.substr($time,2,2).':'.substr($time,4,2);
    }
}
if (isset($_GET['m'])) {
    $method = $_GET['m'];
    $a = new yurticikargo();
    header('content-type:application/json');
    try {
        $result = $a->$method();
        var_dump($result);
    } catch (\Error $e) {
        var_dump($e->getMessage());
    }


}

