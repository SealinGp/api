<?php
namespace API\company;
use API\redis\redis;
use API\RequestMethod\curl\curl;
/*支持查询方式
 2.waybill (Delhivery api 请求参数)    => service_no(alljoy api 数据库中字段名)
*/

class Delhivery
{
    private static $_className = 'Delhivery';
    private static $_env = [
        'test' => [
            'url' => [
                'orderCreate' => 'https://test.delhivery.com/cmu/push/json/',
                'orderQuery' => 'https://test.delhivery.com/api/status/packages/json/',
                'getPinCode' => 'https://test.delhivery.com/c/api/pin-codes/json/'
            ],
            'token' => '04cab39d276966e3f46ea425d2c1190b03e90b5c',
            'client' => 'ALLJOYLOGISTICS EXPRESS',
        ],
        'product' => [
            'url' => [
                'orderCreate' => 'https://track.delhivery.com/cmu/push/json/',
                'orderQuery' => 'https://track.delhivery.com/api/status/packages/json/',
                'getPinCode' => 'https://track.delhivery.com/c/api/pin-codes/json/'
            ],
            'token' => 'd09ad964a7e013826bfff9d4ff7e1f028c70b43a',
            'client' => 'ALLJOYLOGISTICS EXPRESS',
        ]
    ];
    //redis配置
    private static $_redisConfig = [
        'host' => '127.0.0.1',
        'port' => '6379',
        'password' => '',
    ];
    
    /**创建订单
     * @param array $param
     * @param bool $env
     * @return array $result
     */
    public function orderCreate(array $param,bool $env):array
    {
        //环境参数设置
        $env = !$env ? 'test' : 'product';
        $shipments = [];
        $requestData = [
            'format' => 'json',
            'data'   => [
                'pickup_location' => [
                    'add'     => $param['shipperStreet'].' '.$param['shipperStreetNo'],//发件地址
                    'city'    => $param['shipperProvince'].' '.$param['shipperCity'],  //发件城市
                    'country' => $param['shipperCountryCode'],                         //发件国家
                    'name'    => self::$_env[$env]['client'],                          //发件人名字
                    'pin'     => $param['shipperPostCode'],                            //发件邮编
                    'phone'   => $param['shipperPhone'],                               //发件电话
                ],
                'shipments' => &$shipments
            ]
        ];
        for ($i = 0;$i<$param['orderPieces'];$i++) {
            $shipments[$i]['waybill']       = '';                           //轨迹单号
            $shipments[$i]['client']        = self::$_env[$env]['client'];
            $shipments[$i]['name']          = $param['recipientName'];      //收件人名字
            $shipments[$i]['order']         = $param['order_no'];           //订单号
            $shipments[$i]['products_desc'] =                               //描述
                $param['declareItems'][$i]['declareEnName'];
            $shipments[$i]['order_date']    = date('c',time());             //创建时间
            
            $shipments[$i]['payment_mode']  =                               //Prepaid COD Pickup REPL or Cash
                'D' == $param['payType'] ? 'COD' : 'Prepaid';
            $shipments[$i]['total_amount']  =                               //申报价
                $param['declareItems'][$i]['declarePieces'];
            $shipments[$i]['cod_amount']    =                               //cod金额
                'D' == $param['payType'] ? $param['salesMoney'] : '0';
            $shipments[$i]['add']           =                               //收件人地址
                $param['recipientStreet'].' '.$param['recipientStreetNo'];
            $shipments[$i]['city']          = $param['recipientCity'];      //收件人城市
            $shipments[$i]['state']         = $param['recipientProvince'];  //收件人州
            $shipments[$i]['country']       = $param['shipperCountryCode'];//收件人国家
            $shipments[$i]['phone']         = $param['recipientPhone'];     //收件人电话
            $shipments[$i]['pin']           = $param['recipientPostCode'];  //收件人邮编
            $shipments[$i]['shipping_mode'] = 'Surface';                    //Surface/Express
            $shipments[$i]['return_add']    = 'Dwarka';                     //退件地址
            $shipments[$i]['return_city']   = 'Dwarka';                     //退件城市
            $shipments[$i]['return_country']=  $param['shipperCountryCode'];//退件国家
            $shipments[$i]['return_name']   = 'Archita';                    //退件人名
            $shipments[$i]['return_phone']  = '9667030414';                 //退件电话
            $shipments[$i]['return_pin']    = '110077';                     //退件邮编
            $shipments[$i]['return_state']  = 'Dwarka';                     //退件州
            //可选
            $shipments[$i]['extra_parameters'] =                            //备注
                '';
            $shipments[$i]['shipment_width']   =                            //宽
                $param['width'];
            $shipments[$i]['shipment_height']  =                            //高
                $param['height'];
            $shipments[$i]['weight']           =                            //重
                $param['declareItems'][$i]['declareWeight'];
            $shipments[$i]['quantity']         =                            //每个箱子中数量
                $param['declareItems'][$i]['declarePieces'];
            //可选
            $shipments[$i]['hsn_code'] =                                    //海关编码
                $param['declareItems'][$i]['customsNo'];
            /* $shipments[$i]['seller_inv'] =                               //发票号
                 '';
             $shipments[$i]['seller_inv_date'] =                            //发票日期
                 date('c',time());
             $shipments[$i]['seller_name'] =                                //发票日期
                 '';
             $shipments[$i]['seller_add'] =                                 //发票地址
                 '';
             $shipments[$i]['seller_cst'] =                                 //cst number
                 '';
             $shipments[$i]['seller_tin'] =                                 //tin number
                 '';
             $shipments[$i]['consignee_tin'] =                              //tin number
                 '';
             $shipments[$i]['commodity_value'] =                  //commodity value
                 '';
             $shipments[$i]['tax_value'] =                        //tax value
                 '';
             $shipments[$i]['sales_tax_form_ack_no'] =            //Sale Tax Form Acknowledge No.
                 
             $shipments[$i]['category_of_goods'] =                //category of goods
                 '';
             $shipments[$i]['seller_gst_tin'] =                   //seller gst tin
                 '';
             $shipments[$i]['client_gst_tin'] =                   //gst tin
                 '';
             $shipments[$i]['consignee_gst_tin'] =                //gst tin
                 '';
             $shipments[$i]['invoice_reference'] =                //发票参考号
                 '';*/
        }
        $url = self::$_env[$env]['url']['orderCreate'].'?'.http_build_query([ 'token' => self::$_env[$env]['token']]);
        $requestData['data'] = rawurlencode(json_encode($requestData['data']));
        $requestData = urldecode(http_build_query($requestData));
        
        //返回结果格式
        $result = [
            'status' => 'failed',
            'data' => ''
        ];
        try {
            //开始请求
            $curl = new curl();
            $curl->setConf('timeout',60);
            $result = $curl->http('post',$url,$requestData,'',['Content-Type: application/x-www-form-urlencoded']);
            
            //请求结果解析/判断
            $result['data'] = json_decode($result['data'],true) ? : $result['data'];
            if (isset($result['data']['success']) &&
                $result['data']['success'] &&
                isset($result['data']['packages'][0]['waybill'])) {
                $result['status'] = 'success';
                $result['data']   = $result['data']['packages'][0]['waybill'];
            } else {
                $result['status'] = 'failed';
            }
        } catch (\Throwable $e) {
            $result = [
                'status' => 'failed',
                'data'   => $e->getMessage(),
            ];
        }
        
        /*//日志记录 成功/失败
        'failed' == $result['status'] ?
            LogServer::error('1',self::$_className,$requestData,json_encode($result['data'],JSON_PRETTY_PRINT)) :
            LogServer::success('1',self::$_className,$requestData,json_encode($result['data'],JSON_PRETTY_PRINT));*/
        return $result;
    }
    
    /**查询轨迹
     * @param string $service_no 777010000033
     * @param bool $env
     * @return array
     */
    public function orderQuery(string $service_no,bool $env):array
    {
        //环境参数设置
        $env = !$env ? 'test' : 'product';
        $url = self::$_env[$env]['url']['orderQuery'];
        $requestData = [
            'token' => self::$_env[$env]['token'],
            'waybill' => $service_no,
            'verbose' => '2',
            //0:最少信息 1:货物+扫描信息 2:详细信息
        ];
        $requestData = http_build_query($requestData);
        $url = $url. '?'. $requestData;
        
        //返回结果格式
        $result = [
            'status' => 'failed',
            'data' => '',
        ];
        try {
            //开始请求
            $curl = new curl();
            $result = $curl->http('get',$url,'','');
            
            //请求结果解析/判断
            $result['data'] = json_decode($result['data'],true) ? : $result['data'];
            if (isset($result['data']['ShipmentData'][0]['Shipment']['Scans'])) {
                $trackingData = [];
                foreach ($result['data']['ShipmentData'][0]['Shipment']['Scans'] as $k => $v ) {
                    $trackingData[$k]['time'] = date('Y-m-d H:i:s',strtotime($v['ScanDetail']['ScanDateTime']));
                    $trackingData[$k]['description'] = $v['ScanDetail']['Instructions']. ' - ' .
                        $v['ScanDetail']['ScannedLocation'];
                }
                $result['status'] = 'success';
                $result['data'] = $trackingData;
            } else {
                $result['status'] = 'failed';
            }
        } catch (\Throwable $e) {
            $result = [
                'status' => 'failed',
                'data' => $e->getMessage(),
            ];
        }
        
        /*//日志记录 成功/失败
        'failed' == $result['status'] ?
            LogServer::error('2',self::$_className,$requestData,json_encode($result['data'])) :
            LogServer::success('2',self::$_className,$requestData,json_encode($result['data']));*/
        return $result;
    }
    
    /**格式化通过尾程api 获取到的pincode 格式
     * 格式化后:[ ['state'=> '','city' => '','pincode' => ''] ]
     * @param bool $env
     * @return array $result
     */
    public function getPinCodeApi (bool $env):array
    {
        //返回结果格式
        $result = [
            'status' => 'failed',
            'data' => '',
        ];
        
        try {
            //验证是否过期(生命周期:7天),若过期重新获取
            redis::connect(self::$_redisConfig);
            $pinCode = redis::getA(self::$_className.'_pincode');
            if (!empty($pinCode)) {
                $result['status'] = 'success';
                $result['data'] = $pinCode;
            } else {
                $result = $this->getPinCode($env);
                $result['data'] = json_decode($result['data'],true) ? : $result['data'];
                isset($result['data']['delivery_codes'][0]['postal_code']) &&
                redis::setA('Delhivery_pincode',$result['data'],604800);
            }
            
            //源数据解析
            if (isset($result['data']['delivery_codes'][0]['postal_code'])) {
                $result['status'] = 'success';
                $pinCodes = [];
                foreach ($result['data']['delivery_codes'] as $k => $v) {
                    $pinCodes[$k]['state'] = $v['postal_code']['state_code'];
                    $pinCodes[$k]['city'] = $v['postal_code']['district'];
                    $pinCodes[$k]['pincode'] = $v['postal_code']['pin'];
                }
                $result['data'] = $pinCodes;
            } else {
                $result['status'] = 'failed';
            }
            
        } catch (\Throwable $e) {
            $result['data'] = $e->getMessage();
        }
        
        return $result;
    }
    
    /**获取pin code api
     * @param bool $env
     * @return array
     */
    private function getPinCode(bool $env):array
    {
        //环境参数设置
        $env = !$env ? 'test' : 'product';
        $requestData = [
            'token' => self::$_env[$env]['token']
        ];
        $requestData = http_build_query($requestData);
        $url = self::$_env[$env]['url']['getPinCode'].'?'.$requestData;
        
        //返回结果格式
        $result = [
            'status' => 'failed',
            'data' => ''
        ];
        try {
            //开始请求
            $curl = new curl();
            $curl->setConf('timeout',360);
            $result = $curl->http('get',$url,'','');
        } catch (\Throwable $e) {
            $result['data'] = $e->getMessage();
        }
        
        /*//日志记录 成功/失败
        'failed' == $result['status'] ?
            LogServer::error('3',self::$_className,$requestData,json_encode($result['data'])) :
            LogServer::success('3',self::$_className,$requestData,json_encode($result['data']));*/
        return $result;
    }
    
    
}