<?php
namespace API\company;

/* 支持查询方式
    1.order number(alljoy api提供)  => order_no(alljoy api 数据库中字段名)
    2.awb number (Ecom api 提供)    => service_no(alljoy api 数据库中字段名)
现使用换号模式:2方式返回awb number => tracking number 并查询轨迹,
 * */
class Tms
{
    //环境参数
    private static $_env = [
        'test' => [//测试环境(暂时没有s)
            'appToken'    => '7437b833541fcd757361e590a4548f4a',
            'appKey'      => '7437b833541fcd757361e590a4548f4a',
            'createOrder' => 'http://tms.alljoylogistics.com/default/svc/wsdl',       //创建订单(COD/PPD订单)
            'trackOrder'  => 'http://tms.alljoylogistics.com/default/svc/wsdl',         //Status Pull
        ],
        'product' => [//测试环境(暂时没有s)
            'appToken'    => '7437b833541fcd757361e590a4548f4a',
            'appKey'      => '7437b833541fcd757361e590a4548f4a',
            'createOrder' => 'http://tms.alljoylogistics.com/default/svc/wsdl',       //创建订单(COD/PPD订单)
            'trackOrder'  => 'http://tms.alljoylogistics.com/default/svc/wsdl',         //Status Pull
        ]
    ];
    
    //redis配置
    private static $redisConfig = [
        'host' => '127.0.0.1',
        'port' => '6379',
        'password' => '',
    ];
    
    

    
    public function orderCreate(array $param, bool $env = false) {
        $env         = !$env ? 'test' : 'product';
        $url         = self::$_env[$env]['createOrder'];
        $paramJson   = [];
        $requestData = [
            //token
            'appToken'   => self::$_env[$env]['appToken'],
            'appKey'     => self::$_env[$env]['appKey'],
            'service'    => 'createOrder',
            'paramsJson' => &$paramJson
        ];
        $paramJson['reference_no']    = $param['order_no'];
        $paramJson['shipping_method'] = $param['shippingMethod'];
        $paramJson['country_code']    = $param['shipperCountryCode'];
        $paramJson['order_weight']    = $param['orderWeight'];
        $paramJson['order_pieces']    = $param['orderPieces'];
        $paramJson['length']          = $param['length'];
        $paramJson['width']           = $param['width'];
        $paramJson['height']          = $param['height'];
        $paramJson['is_return']       = '1';
//        $paramJson['extra_service']   = 'A5,';
        $paramJson['Consignee']       = [
            'consignee_city'      => $param['recipientCity'],
            'consignee_street'    => $param['recipientStreet'].$param['recipientStreetNo'],
            'consignee_province'  => $param['recipientProvince'],
            'consignee_telephone' => $param['recipientPhone'],
            'consignee_postcode'  => $param['recipientPostCode'],
            'consignee_name'      => $param['recipientName']
        ];
        $paramJson['Shipper']         = [
            'shipper_city'      => $param['shipperCity'],
            'shipper_street'    => $param['shipperStreet'].$param['shipperStreetNo'],
            'shipper_province'  => $param['shipperProvince'],
            'shipper_telephone' => $param['shipperPhone'],
            'shipper_postcode'  => $param['shipperPostCode'],
            'shipper_name'      => $param['shipperName']
        ];
        foreach ($param['declareItems'] as $k => $v) {
            $paramJson['ItemArr'][$k]['invoice_enname']       = $v['declareEnName'];
            $paramJson['ItemArr'][$k]['invoice_weight']       = $v['declareWeight'];
            $paramJson['ItemArr'][$k]['invoice_quantity']     = $v['declarePieces'];
            $paramJson['ItemArr'][$k]['invoice_unitcharge']   = $v['declarePrice'];
            $paramJson['ItemArr'][$k]['invoice_cnname']       = $v['declareCnName'];
            $paramJson['ItemArr'][$k]['invoice_currencycode'] = $v['declareEnName'];
        }
        
        $result = [];
        //参数赋值 单个箱子里面的参数
        for ($i = 0;$i<1;$i++) {
            //必填
            //awb获取并赋值
            $awb = self::getAwb('D' == $param['payType'] ? 'COD' : 'PPD','test' == $env ? false : true);
            if ('failed' === $awb['status'] ) {
                $result = [
                    'status' => 'failed',
                    'msg' => $awb['data'],
                ];
                return $result;
            }
            $requestData['json_input'][$i]['AWB_NUMBER'] = (string)$awb['data']['awb'][0];              //服务商单号

            //pincode获取并赋值
            $requestData['json_input'][$i]['PINCODE'] = '';                                             //PinCode(邮编)
            self::pinCode($requestData,$i,$result,$env,$param);
            if (isset($result['status']) && 'failed' == $result['status']) {
                return $result;
            }
            $requestData['json_input'][$i]['ORDER_NUMBER'] =                                            //订单号
                substr($param['order_no'],0,strlen($param['order_no']) - 2);
            $requestData['json_input'][$i]['PRODUCT'] = 'D' == $param['payType'] ?                      //订单类型
                'COD' : 'PPD';
            $requestData['json_input'][$i]['COLLECTABLE_VALUE'] = 'D' == $param['payType'] ?            //到付金额
                (string)$param['salesMoney'] :'0';
            $requestData['json_input'][$i]['DECLARED_VALUE'] = 0;                                       //箱子货物总申报价
            $requestData['json_input'][$i]['ITEM_DESCRIPTION'] = '';                                    //箱子货物总简述
            foreach ($param['declareItems'] as $v) {
                $requestData['json_input'][$i]['DECLARED_VALUE']+=(int)$v['declarePrice'];
                $requestData['json_input'][$i]['ITEM_DESCRIPTION'].= $v['declareEnName'].' ';
            }
            $requestData['json_input'][$i]['CONSIGNEE'] = $param['recipientName'];                      //收件人
            $requestData['json_input'][$i]['CONSIGNEE_ADDRESS1'] =                                      //收件人地址
                $param['recipientCountryCode'].' '.$param['recipientProvince'].' '.
                $param['recipientCity'].' '.$param['recipientStreet'].' '.$param['recipientStreetNo'];
            $requestData['json_input'][$i]['STATE'] = $param['recipientProvince'];                      //收件人州/省
            $requestData['json_input'][$i]['MOBILE'] = $param['recipientPhone'];                        //收件人手机
            $requestData['json_input'][$i]['PIECES'] = 1;                                               //箱子数量(1)
            $requestData['json_input'][$i]['ACTUAL_WEIGHT'] = (double)$param['orderWeight'];            //箱子重量
            $requestData['json_input'][$i]['LENGTH'] = (int)$param['length'];                           //箱子长
            $requestData['json_input'][$i]['BREADTH'] = (int)$param['width'];                           //宽
            $requestData['json_input'][$i]['HEIGHT'] = (int)$param['height'];                           //高

            //Ecom海外 在alljoy 拣货地点(alljoy仓库)
            foreach (self::$_warehouse as $k => $v) {
                $requestData['json_input'][$i]['PICKUP_'.strtoupper($k)] = $v;
            }
            foreach (self::$_warehouse as $k => $v) {
                $requestData['json_input'][$i]['RETURN_'.strtoupper($k)] = $v;
            }

            $requestData['json_input'][$i]['DG_SHIPMENT'] = (string)'false';                            //是否有空运限制物品
            //选填(必须定义否则api请求失败)
            $requestData['json_input'][$i]['CONSIGNEE_ADDRESS2'] = '';                                  //收件人地址2
            $requestData['json_input'][$i]['CONSIGNEE_ADDRESS3'] = '';                                  //收件人地址3
            $requestData['json_input'][$i]['DESTINATION_CITY'] = $param['recipientCity'];               //收件人城市
            $requestData['json_input'][$i]['TELEPHONE'] = $param['recipientTel'];                       //收件人电话
            $requestData['json_input'][$i]['VOLUMETRIC_WEIGHT'] = (int)'';                                   //体积比
            $requestData['json_input'][$i]['PICKUP_ADDRESS_LINE2'] = '';                                //拣货点2位置
            $requestData['json_input'][$i]['RETURN_ADDRESS_LINE2'] = '';                                //退货点2位置
//            $requestData['json_input'][$i]['ADDONSERVICE'] = [''];                                      //额外服务(NDD:次日交货)需交(额外)费用

            //必填(额外参数)
            $requestData['json_input'][$i]['ADDITIONAL_INFORMATION'] = [
                'ITEM_CATEGORY' => '',                                                                  //入境物品类别(用于税率计算Octroi/GST/Entry tax)
                'INVOICE_NUMBER' => $requestData['json_input'][$i]['ORDER_NUMBER'],                     //发票号码
                'INVOICE_DATE' => date('d-M-Y',time()),                                                 //发票日期 d-M-Y
                'SELLER_GSTIN' => self::$_GST['SELLER_GSTIN'],                                          //卖家GST number
                'GST_TAX_NAME' => self::$_GST['GST_TAX_NAME'],                                          //已征收的GST税费名称例如: Delhi GST,Maharashtra GST 等
                'GST_TAX_TOTAL' => (double)'0',                                                         //所收商品税总额
//                'GST_HSN' => '',                                                                      //物品hsn code
//                'GST_TAX_BASE' => (double)'4000.0',//应交总税费(已扣除GST(消费税)后的)
//                'GST_TAX_RATE_CGSTN' => (double)'9.0',//CGSTN收藏税率(Rate of tax)<1 [document] ? 税费 (expense of tax) [ej]
//                'GST_TAX_RATE_SGSTN' => (double)'9.0',//SGSTN收藏税率
//                'GST_TAX_RATE_IGSTN' => (double)'0.0',//IGSTN收藏税率

//                'GST_TAX_CGSTN' => (double)'360.00',//中心GST 占总税费(除开中心GST的)的比例 Central GST share out of total tax deducted .
//                'GST_TAX_SGSTN' => (double)'360.00',//州GST 占总税费(除开州GST的)的比例 State GST share out of total tax deducted.
//                'GST_TAX_IGSTN' => (double)'0.0',//国际州GST 占总税费(除开国际州GST的)的比例 Interstate GST share out of total tax
//                //选填
//                'SELLER_TIN' => 'alljoy_test',//卖家的 TIN number
                'ESUGAM_NUMBER' => '',//? eSugam number (合法运输号)
                'PACKING_TYPE' => 'Box',//打包类型 Box: 盒子 Filer: 文件
                'PICKUP_TYPE' => 'WH',                                                                  //拣货地址类型WH:仓库 SL:市场(卖家) RH:中转站
                'RETURN_TYPE' => 'WH',                                                                  //返件地址的类型 WH:仓库 SL:市场(卖家) RH:中转站
//                'PICKUP_LOCATION_CODE' => 'alljoy_test',//取货地点唯一识别码
//                'GST_ERN' => 'alljoy_test',//GST E-Waybill numbe
//                'DISCOUNT ' => (double)'0.0',//折扣 例如:0.6 现价=原价 * 60%
            ];
            /*
             //必填(在一个箱子多个物品时)
            $mutiAdd = [];
            $mutiAdd['MULTI_SELLER_INFORMATION'][$i] = $requestData['json_input'][$i]['ADDITIONAL_INFORMATION'];
            $mutiAdd['MULTI_SELLER_INFORMATION'][$i]['ITEM_DESCRIPTION'] = '';
            $mutiAdd['MULTI_SELLER_INFORMATION'][$i]['ITEM_VALUE'] = '';
            $mutiAdd['MULTI_SELLER_INFORMATION'][$i]['SELLER_NAME'] = '';
            $mutiAdd['MULTI_SELLER_INFORMATION'][$i]['SELLER_ADDRESS'] = '';
            $mutiAdd['MULTI_SELLER_INFORMATION'][$i]['SELLER_STATE'] = '';
            $mutiAdd['MULTI_SELLER_INFORMATION'][$i]['SELLER_PINCODE'] = '';
            //选填
            $mutiAdd['MULTI_SELLER_INFORMATION'][$i]['SELLER_TIN'] = '';//卖家 TIN code
            $mutiAdd['MULTI_SELLER_INFORMATION'][$i]['ESUGAM_NUMBER'] = '';//?eSugam number for eligible shipments
            $mutiAdd['MULTI_SELLER_INFORMATION'][$i]['GST_ERN'] = '';//GST E-Waybill number
            unset($requestData['json_input'][$i]['ADDITIONAL_INFORMATION']);
            $requestData['json_input'][$i]['ADDITIONAL_INFORMATION'] =  $mutiAdd;
             * */
        }
        $requestData['json_input'] = strtr(json_encode($requestData['json_input'],JSON_UNESCAPED_UNICODE),['\\'=>'']);
        //开始请求
        $result = self::curlForm($url,$requestData);
        $requestData = strtr(json_encode($requestData,JSON_PRETTY_PRINT),['\\'=>'']);
        $responseData = strtr(json_encode($result['data'],JSON_PRETTY_PRINT),['\\'=>'']);
        //请求失败
        if (isset($result['data']['shipments'][0]['success']) && false === $result['data']['shipments'][0]['success']) {
            $result['status'] = 'failed';
            $result['msg'] = isset($result['data']['shipments'][0]['reason']) ?
                $result['data']['shipments'][0]['reason'] : 'Ecom api error,check errorlog,time:'.date('YmdHis',time());
            LogServer::error('1','Ecom','orderCreate:'.$requestData,$responseData);
            unset($result['data']);
            return $result;
        }
        $result['trackingNo'] = (string)$awb['data']['awb'][0];
        LogServer::success('1','Ecom',$requestData,$responseData);
        unset($result['data']);
        return $result;
    }

    /** 查询轨迹(支持批量查,查询参数以逗号分开)
     *  @param string $awb 服务商单号(service_no)
     * */
    public function orderQuery(string $awb, bool $env = false) {
        $env = !$env ? 'test' : 'product';
        $url = self::$_env[$env]['trackOrder'];
        $requestData = [
            'username' => self::$_env[$env]['username'],
            'password' => self::$_env[$env]['password'],
            //必填awb: Undelivered: 706592146 delivered:1000000714
            'awb' => $awb,                                  //awb number(Ecome提供的)
            //选填
//            'order' => '',                                //order id
        ];
        $result = self::curlForm($url,$requestData);
        try {
            //检测返回结果的正确性(应对Ecom api系统报错措施)
            $resultCheck = stripos($result['data'],'<object pk="1" model="awb">');
            if (false === $resultCheck) {
                unset($result['data']);
                $result = [
                    'status' => 'failed',
                    'msg' => 'your tracking number is not exists.time:'.date('YmdHis',time())
                ];
                return $result;
            }
            //解析源数据 返回的历史轨迹字段
            $fields = [
                'updated_on' => '',                         //时间
                'status' => '',                             //描述(可能为空,为空时找状态代码对应的状态)
                'reason_code_number' => '',                 //状态原因代码
                /*
                'scan_status' => '',                        //IN(迁入) OUT(迁出) HOLD(扫描中)
                //位置
                'location_city' => '',                      //城市
                'location' => '',                           //城市缩写
                'location_type' => '',                      //类型
                'city_name' => '',                          //城市
                'Employee' => '',                           //运输人
                'reason_code' => '',                        //状态原因代码+原因
                 * */
            ];
            self::paraseData($fields,$result,$awb);

            //解析成功
            unset($result['data']);
            $result = [
                'status' => 'success',
                'data' => [$fields]
            ];
            return $result;
        } catch (\Throwable $e) {
            $result = [
                'status' => 'failed',
                'msg' => 'an error occured,please contact us.time:'.date('Y-m-d H:i:s',time()),
            ];
           
            return $result;
        }

    }

    //获取pin code
    public static function getPinCode(bool $env) {
        $env  = !$env ? 'test' : 'product';
        $url = self::$_env[$env]['getPinCodes'];
        $requestData = [
            //必填参数
            'username' => self::$_env[$env]['username'],
            'password' => self::$_env[$env]['password'],
        ];
        $result = self::curlForm($url,$requestData);
        $result['status'] = !is_array($result['data']) || empty($result['data']) ? 'failed': 'success';
        if ( $result['status'] == 'failed' ||
            'Unauthorised Request' == $result['data']
        ) {
        }
        return $result;
        /* $response =   [//成功的响应
             [
               "pincode"=> 110037,          //邮编(6位)
               "dccode"=> "DSW",            //3位
               "city"=> "DELHI",            //邮编对应的城市
               "city_code"=> "DEL",         //邮编对应的城市代码
               "state"=> "New Delhi",       //邮编对应的州
               "state_code"=> "DL",         //邮编对应的州代码
               "route"=> "DL/DEL",          //路线
               "date_of_discontinuance"=> null,//Pin code 过期时间 YYYY-mm-dd
               "active" => true,            //pinCode是否有效
             ]
         ];
         */
    }

    //获取awb number
    public static function getAwb(string $type, bool $env) {
        $env = !$env ? 'test' : 'product';
        $url = self::$_env[$env]['getAwb'];
        $requestData =  [
            'username' => self::$_env[$env]['username'],
            'password' => self::$_env[$env]['password'],
            'count' => (int)1,                                          //获取的AWB数量,小于50000
            'type' => $type,                                            //订单类型 PPD(预付) COD(货到付款) REV()
        ];
        $result = self::curlForm($url,$requestData);
        //Ecome 的response 显示失败
        if (
            ( isset($result['data']['success']) && 'no' == $result['data']['success'] ) ||
            ( 'Unauthorised Request' == $result['data'] ) ||
            is_string($result['data'])
        ) {
            $result['status'] = 'failed';
            isset($result['data']['error']) && $result['data'] = json_encode($result['data']['error']);
        }
        return $result;
        /*成功的响应
         * $response = [
         *      "reference_id" => 331724,
         *      "success": "yes",
         *      "awb": [
         *          106349409,
         *          ...
         *      ],
         * ];
         * */
    }

    //Ecom面单必显示标签字段
    private static $labelData = [
        'dccode',//在生成Pin code API里面有
        'state_code',//在生成Pin code API里面有
        'ColllectableValue',//收取 到货金额(COD强制要求此参数)
        'Product type',//产品类型 PPD(预付订单强制要求此参数),
    ];

    //pincode获取并赋值
    private static function pinCode(&$requestData,&$i,&$result,&$env,&$param) {
        //连接Redis
        if (!Redis::connect(self::$redisConfig)) {
            $result = ['status' => 'failed','msg' => 'redis connect error'];
            return;
        }

        //从redis中获取pincode
        $get = Redis::getA('Ecom_pincode');

        //redis中pincode不存在
        if (!$get) {
            //获取pincode
            $result = self::getPinCode('test' == $env ? false : true);

            //从Ecom获取pincode失败
            if ($result['status'] == 'failed') {
                unset($result['data']);
                $result['msg'] = 'Ecom get pin code Error';
                return ;
            }

            //从Ecom获取pincode成功
            Redis::setA('Ecom_pincode',$result['data']);
            $get = Redis::getA('Ecom_pincode');
        }

        //找到对应输入参数的Pincode(可用的)
        foreach ($get as $k => $v) {
            //pincode失效
            if (!$v['active']) {
                unset($get[$k]);
                continue;
            }
            if ((int)$v['pincode'] == (int)$param['recipientPostCode']) {
                $requestData['json_input'][$i]['PINCODE'] = (string)$v['pincode'];
                break;
            }
        }

        //pincode全部失效 记录并退出
        if (empty($get)) {
            Redis::del('Ecom_pincode');
            unset($result['data']);
            $result['status'] = 'failed';
            $result['msg'] = 'Ecom\'s all pincode unvaild';
            return;
        }

        //未找到支持的pincode 记录并退出
        if ('' == $requestData['json_input'][$i]['PINCODE']) {
            $requestData['json_input'][$i]['PINCODE'] = $param['recipientPostCode'];
            unset($result['data']);
            $result['status'] = 'failed';
            $result['msg'] = 'unspport recipientPostCode:'.$param['recipientPostCode'];return;
        }

    }

    //解析调整源数据
    private static function paraseData(array &$fields,array &$result,$awb) {
        //提取源数据中的轨迹字段的值 存储在$fields中
//        var_dump($fields);exit;
        self::xmlParase($fields,$result['data'],'levelSearch',5);

        if (count($fields) == count($fields,1)) {
            $fields['result'] = 'failed';
            return;
        }

        //调整提取数据,存储到$fields['data']中,并清空提取数据
        foreach ($fields as $k => $v) {
            foreach ($v as $k1 => $v1) {
                $fields['data'][$k1][$k] = $v1;
            }
        }
        unset($fields['updated_on'],$fields['status'],$fields['reason_code_number']);

        /*维护轨迹
        在$fields['data']中 查找 轨迹事件+时间并赋值*/
        $resonCode = array_keys(self::$reson_code_number);
        foreach ($fields['data'] as $k => $v) {
            $fields['data'][$k]['time'] = $fields['data'][$k]['updated_on'];
            $fields['data'][$k]['description'] =  $v['status'] ? $v['status'] :
                ( in_array($v['reason_code_number'],$resonCode) ? self::$reson_code_number[$v['reason_code_number']] : 'In transit' );
            unset($fields['data'][$k]['reason_code_number'],$fields['data'][$k]['updated_on'],$fields['data'][$k]['status']);
        }
        //调整事件顺序
        $data = array_reverse($fields['data'], true);
        unset($fields['data']);
        foreach ($data as $v) {
            $fields[$awb][] = $v;
        }
    }

    /* 根据xml字符中标签的等级 解析xml字符串
     * 找到xml字符串中 标签等级:$level,属性:name=field,的标签值(tagVal) ,<tag name=field> tagVal </tag>
     * @param array $fields 轨迹字段
     * @param string $data 返回结果源数据(xml)
     * @param string $method 对象 \ext\xml 中的方法名
     * @param int $level xml字符串中标签的等级(根标签为1,内联标签依次递增)
     * */
    private static function xmlParase(array &$fields,string $data,string $method,int $level) {
        foreach ($fields as $k => $v) {

            $param = [
                'xmlStr' => $data,
                'tagName' => 'field',
                'attr' => 'name',
                'attrVal' => $k,
                'level' => $level
            ];
            $value = Xml::hook($method,$param);
            $fields[$k] = $value;
        }
    }

    //curl模拟form表单请求
    private static function curlForm(string $url, array $param) {
        $paramStr = '';
        foreach ($param as $k => $v) {
            $paramStr .= "------data\r\nContent-Disposition: ";
            $paramStr .= "form-data; name=\"{$k}\"\r\n\r\n{$v}\r\n";
        }
        $paramStr .= "------data--";
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
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "content-type: multipart/form-data; boundary=----data"
            ),
        ));
        $data = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $result = [
            //curl请求失败/成功
            'status' => $err ? 'failed' : 'success' ,
            //源数据的转换
            'data' => '' !== $err ? $err :  (json_decode($data,true) ? : $data) ,
            //因大部分方法使用json string返回,所以先转换成array,若不是json,则返回源数据
        ];
        return $result;
    }

    //下单2 此方式适用于REV(reverse)订单 [反向取货,即从客户(取货)->公司],此方式的Awb不同于正向创建订单的Awb
    public static function orderCreate2(array $param = [],bool $env = false) {
        $env = !$env ? 'test' : 'product';
        $url = self::$_env[$env]['createOrder2'];
        $param = $param ? : [
            //token
            'username' => self::$_env[$env]['username'],
            'password' => self::$_env[$env]['password'],
            'json_input' => [
                //必填参数
                'AWB_NUMBER' => '107105314',
                'ORDER_NUMBER' => 'alljoy_test',
                'PRODUCT' => 'rev',
                'REVPICKUP_NAME' => 'alljoy_test',
                'REVPICKUP_ADDRESS1' => 'alljoy_test',
                'REVPICKUP_CITY' => 'alljoy_test',
                'REVPICKUP_PINCODE' => 'alljoy_test',
                'REVPICKUP_STATE' => 'alljoy_test',
                'REVPICKUP_MOBILE' => '1111111111',
                'PIECES' => '1',
                'COLLECTABLE_VALUE' => '0.0',
                'DECLARED_VALUE' => '0.0',
                'ITEM_DESCRIPTION' => '0.0',
                'ACTUAL_WEIGHT' => '0.0',
                'LENGTH' => '320',
                'BREADTH' => '200',
                'HEIGHT' => '120',
                'DROP_NAME' => 'alljoy_test',
                'DROP_ADDRESS_LINE1' => 'alljoy_test',
                'DROP_PINCODE' => '111111',
                'DROP_MOBILE' => '1111111111',
                'DROP_PHONE' => '1111111111',
                'DG_SHIPMENT' => 'false',
                //可选参数
//                'REVPICKUP_ADDRESS2' => '',
//                'REVPICKUP_ADDRESS3' => '',
//                'REVPICKUP_TELEPHONE' => '',
//                'VOLUMETRIC_WEIGHT' => '',
//                'VENDOR_ID' => '',
//                'DROP_ADDRESS_LINE2' => '',
//                'EXTRA_INFORMATION' => '',

                //额外参数
                'ADDITIONAL_INFORMATION' => [
                    //必选参数
                    'INVOICE_NUMBER' => '',
                    'INVOICE_DATE' => '',
                    'ITEM_CATEGORY' => '',
                    'SELLER_GSTIN' => '',
                    'GST_HSN' => '',
                    'GST_TAX_NAME' => '',
                    'GST_TAX_BASE' => '',
                    'GST_TAX_RATE_CGSTN' => '',
                    'GST_TAX_RATE_SGSTN' => '',
                    'GST_TAX_RATE_IGSTN' => '',
                    'GST_TAX_TOTAL' => '',
                    'GST_TAX_CGSTN' => '',
                    'GST_TAX_SGSTN' => '',
                    'GST_TAX_IGSTN' => '',
                    //可选参数
                    'SELLER_TIN' => '',
                    'ESUGAM_NUMBER' => '',
                    'PACKING_TYPE' => '',
                    'PICKUP_TYPE' => '',
                    'RETURN_TYPE' => '',
                    'PICKUP_LOCATION_CODE' => '',
                    'GST_ERN' => '',
                    'DISCOUNT' => '',
                ]
            ],
        ];
        $param['json_input'] = json_encode($param['json_input']);
        $param['json_input'];
        $result = self::curlForm($url,$param);
        return $result;
    }

    //trackOrder函数返回的reponse中的 'reason_code_number'字段的code => 描述
    private static $reson_code_number = [
        "001" => "Soft Data uploaded ",//(订单已创建)
        "1210" => "Pickup Unassigned",//(未分配拣货)
        "1220" => "Pickup Assigned",//(已分配拣货)
        "1230" => "Out for Pickup",//(外出取件中)
        "0011" => "Field pickup via Laptop",//(通过电脑已取件)
        "124" => "Field Pickup Completed",//(取件完成)
        "1260" => "Excess Shipment Pickedup",//(超额装运)
        "127" => "Shipment arrived at Ecom Facility ",//(货物已到达Ecome)
        "002" => "In-Scan at Ecom Facility",//(扫描中)
        "1310" => "Pickup Failed due to Misroute",//(拣货失败)
        "1320" => "Pickup Failed, Shipment picked up by other Courier",//(拣货失败,已交由其他快递)
        "1330" => "Pickup Cancelled By Customer",//(客户已取消)
        "1350" => "Pickup Failed, Shipment Not Ready",//(拣货失败,运输未准备好)
        "1360" => "Pickup Failed, Premise Closed",//(拣货失败,已提前关闭)
        "1370" => "Pickup Failed, Vendor Shifted",//(拣货失败,卖家转移)
        "1380" => "Pickup Failed, Road Blocked/Premise Inaccessible",//(拣货失败,道路受阻)
        "1390" => "Picked Failed, Pickup attempted Late",//(拣货失败)
        "1340" => "Pickup Failed, Shipment Not Handed Over",//(拣货失败,货物未上交)
        "1400" => "Picked Rescheduled, Request received after cutoff",//(重新收件,终止后收到请求)
        "1410" => "Pickup Rescheduled For Next Day",//(改天重新收件)
        "014" => "Out For Pickup",//(准备接货)
        "310" => "Soft Data uploaded - Shipment not picked up",//(数据已上传,货物未收件)
        "003" => "Bagging Completed 003",//(装货完成)
        "004" => "Assigned to Run Code 004",//(指定运行代码004)
        "005" => "Shipment at Delivery Centre 005",//(货物已到达交货中心)
        "006" => "Outscan 006 ( Out for Delivery )",//(交货中)
        "100" => "Delay in Delivery Expected",//(比预期延迟交货)
        "203" => "Re-attempt and Return",//(重新尝试交货)
        "205" => "Redirection on same Air Waybill",//(同一个空运单上重定向)
        "303" => "Shipment In Transit",//(货物转运中)
        "305" => "Shipment Off-loaded By Airline",//(航空卸货完成)
        "306" => "Flight Cancelled",//(航班取消)
        "304" => "Network Delay",//(网络延迟)
        "307" => "Held - Regulatory Paperwork Required",//(需要书面工作,流程进行中)
        "308" => "Held for Octroi/ Taxes",
        "309" => "Missed Connection",//(失去联系)
        "1224" => "TeleCalling - Shipment Delivery Scheduled",//(电话中,讨论交货计划)
        "200" => "Forcibly Taken By Consignee",//(收件人强行拿走)
        "201" => "Awaiting Consignee's Response for Delivery",//(等待收件人确认)
        "202" => "Correction Of Wrong POD Details",//(纠正错误到货订单信息)
        "206" => "Return to Origin on Same Air Waybill",//(同一航空返回值源地址)
        "207" => "Misrouted due to Shipper's fault",//(因发件人填写错误信息而找不到路线)
        "208" => "Contents Missing",//(内容丢失)
        "209" => "Consignee Refusing to Pay COD Amount",//(收件人拒绝付款)
        "210" => "COD Amount Not Ready",//(货到付款价格未说明)
        "212" => "Consignee Out Of Station",//(收件人不在)
        "213" => "Scheduled for Next Day Delivery",//(预计改天交货)
        "214" => "Need Department Name/ Extension Number",//(需要部门名称或分机号码)
        "215" => "Disturbance/Natural Disaster/Strike",//(骚扰/自然灾害/罢工)
        "216" => "Late Arrival/Scheduled for Next Working Day",//(延迟交货,预计工作日交货)
        "217" => "Delivery Area Not Accessible",//(无法进入交货区)
        "221" => "Consignee Refused To Accept",//(收件人拒绝收货)
        "222" => "Address Incorrect",//(地址错误)
        "223" => "Address Incomplete",//(地址不详细)
        "224" => "Address Unlocatable",//(地址不存在)
        "225" => "Shipment Manifested - Not Received by Destination",//(货物显示目标未收到)
        "226" => "Holiday/Weekly off - Delivery on Next Working Day",//(休假,工作日交货)
        "227" => "Residence/Office Closed",//(住所办公司关闭)
        "228" => "Out of Delivery Area",//(超出交货区域)
        "229" => "HOLD AT LOCATION",//(?位置保持)
        "231" => "Address Unlocatable - Consignee not contactable",//(地址不存在,无法联系收件人)
        "233" => "COD DENOMINATION NOT ACCEPTED",//(交货面额现金不接受)
        "331" => "Consignee requested for future delivery",//(收件人请求改天交货)
        "332" => "Shipment Missing-Under Investigation",//(货物失踪,调查中)
        "333" => "Shipment Lost",//(货物丢失)
        "666" => "SDL - Special delivery location",//(特定交货地址)
        "888" => "Shipment Destroyed/Abandoned",//(货物损坏/丢弃)
        "218" => "Consignee Shifted from the Given Address",//(收件人修改收件地址)
        "219" => "Consignee Not Available",//(收件人联系不到)
        "220" => "No Such Consignee At Given Address",//(收件地址无此收件人)
        "1225" => "TeleCalling - Consignee Refused to take Delivery",//(电话联系中,收件人拒绝收货)
        "999" => "Delivered",//(退货完成,货物已交付发件人)
        "204" => "Shipment Delivered/Consignee Complains of Damage",//(收件人抱怨损坏)
        "777" => "RTS - Return To Shipper",//(退货中)
        "77" => "RTO Lock",//(退货锁定)
        "80" => "RTO Lock Revoked",//(退货锁撤销)
        "82" => "OFD Lock",
        "83" => "OFD Lock Revoked",
    ];

}

?>

