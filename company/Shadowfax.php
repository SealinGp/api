<?php
namespace API\company;
use API\redis\redis;
use API\RequestMethod\curl\curl;


/*支持查询方式
 2.awb (Shadowfax api 请求参数)    => service_no(alljoy api 数据库中字段名)
*/

class Shadowfax
{
    private static $_className = 'Shadowfax';
    private static $_env = [
        'test'    => [
            'orderCreate'   => 'http://saruman.staging.shadowfax.in/api/v1/clients/requests',
            'orderQuery'    => 'http://saruman.staging.shadowfax.in/api/v1/clients/requests/',
            'getPinCode'    => 'http://saruman.staging.shadowfax.in/api/v1/clients/requests/serviceable_pincodes',
            'token'         => 'Token 8df5dd3321d1a09e75f402e14c528315fe927981'
        ],
        'product' => [
            'orderCreate'   => 'http://saruman.shadowfax.in/api/v1/clients/requests',
            'orderQuery'    => 'http://saruman.shadowfax.in/api/v1/clients/requests/',
            'getPinCode'    => 'http://saruman.shadowfax.in/api/v1/clients/requests/serviceable_pincodes',
            'token'         => 'Token f23e37bf33554fa06323207e0dd619da515c42c8'
        ]
    ];
    //redis配置
    private static $_redisConfig = [
        'host'     => '127.0.0.1',
        'port'     => '6379',
        'password' => '',
    ];
    
    //alljoy 印度海外仓地址
    private static $_warehouse = [
        'street'   => 'Khasra NO 72 & 73 Extended Laldoora Of Village Bamnoli Sector 28',
        'city'     => 'Dwarka',
        'province' => 'New Delhi',
        'postCode' => '110077',
        'company'  => 'ALLJOY SUPPLY CHAIN (INDIA) PVT LTD.',
        'name'     => 'Archita',
        'phone'    => '9667030414',
        'tel'      => '9667030414',
//        'address_line' => 'Khasra NO 72 & 73 Extended Laldoora Of Village Bamnoli Sector 28 Dwarka New Delhi 110077'
    ];
    
    /**创建订单
     * @param array $param
     * @param bool $env
     * @return array
     */
    public function orderCreate(array $param,bool $env):array
    {
        //环境参数配置
        $env         = !$env ? 'test' : 'product';
        $url         = self::$_env[$env]['orderCreate'];
        $header      = [
            'Authorization: '.self::$_env[$env]['token']
        ];
        //检查收件人邮编是否在Ecom派送邮编范围内
        $pinCode  = $this->getPinCodeApi('test' == $env ? false : true);
        if ('failed' == $pinCode['status'] ) return $pinCode;
        $pincodes = [];
        foreach ($pinCode['data'] as $k => $v) {
            $pincodes[] =  (string)$v['pincode'];
        }
        if (!in_array($param['recipientPostCode'],$pincodes)) {
            LogServer::error(1,self::$_className,
                'recipientPostCode:'.$param['recipientPostCode'],
                self::$_className.' pin codes:'.json_encode($pincodes));
            $result = [
                'status' => 'failed',
                'data'   => 'unsupport recipientPostCode:'.$param['recipientPostCode']
            ];
            return $result;
        }
        $declareValues = 0;
        $declareItems = [];
        $requestData = [
         //必填参数
         'client_order_id'           => $param['order_no'],           //订单号
         'awb_number'                => $param['awbNumber'] ?? '',    //轨迹单号
         'pincode'                   => $param['recipientPostCode'],  //邮编
         'customer_name'             => $param['recipientName'],      //客户名
         'customer_phone'            => $param['recipientPhone'],     //客户电话
         'customer_address'          =>                               //客户地址
             $param['recipientProvince'].' '.$param['recipientCity'].' '.
             $param['recipientStreet'].' '.$param['recipientStreetNo'],
         'c_city'                    => $param['recipientCity'],      //客户城市
         'c_state'                   => $param['recipientProvince'],  //客户州
         'deliver_type'              =>                               //订单类型COD|Prepaid
            'D' == $param['payType'] ? 'COD':'Prepaid',
         'declared_value'            => &$declareValues,              //申报价值
         'total_amount'              => &$declareValues,              //申报总价
         'eway_bill'                 => '',                           //12位的E-way bill,总价>50000时必填
         'cod_amount'                =>                               //到付金额
             'D' == $param['payType'] ? $param['salesMoney']:'0',
         'pickup_address_attributes' => [                             //pick up address
             'address'    => self::$_warehouse['city'].
                 self::$_warehouse['province'].self::$_warehouse['postCode'],
             'pincode'    => self::$_warehouse['postCode']
         ],
         'rto_attributes'            => [                             //退货地址
             'address'    => self::$_warehouse['city'].
                 self::$_warehouse['province'].self::$_warehouse['postCode'],
             'city'       => self::$_warehouse['city'],
             'state'      => self::$_warehouse['province'],
             'pincode'    => self::$_warehouse['postCode'],
             'name'       => self::$_warehouse['name'],
             'contact_no' => self::$_warehouse['phone'],
         ],
         'skus_attributes'          => &$declareItems
        ];
        foreach ($param['declareItems'] as $k => $v) {
          $declareItems[$k]['seller_details']                   = [];
          $declareItems[$k]['taxes']                            = [];
          $declareItems[$k]['product_name']                     = $v['declareEnName'];
          $declareItems[$k]['volumetric_weight']                = $v['declareWeight'];
          $declareItems[$k]['product_category']                 = $v['declareEnName'];
          $declareItems[$k]['hsn_code']                         = $v['customsNo'];
          $declareItems[$k]['client_sku_id']                    = '';//sku
            /*'price' => '',//sku
            'product_subcategory' => '',//二级分类
            'brand_name' => '',//品牌名
            'length' => '',//长
            'breadth' => '',//
            'height' => '',//高
            'box_type' => '',//箱类型
            'product_sale_value' => '',//价格
            'additional_details' => '',//额外信息,如:{"color": "blue", "size": 4}*/
          $declareValues                                       += (float)$v['declarePrice'];
          $declareItems[$k]['seller_details']['seller_name']    = $param['shipperName'];
          $declareItems[$k]['seller_details']['seller_state']   = $param['shipperProvince'];
          $declareItems[$k]['invoice_no']                       = '';
          $declareItems[$k]['taxes']['cgst']                    = '0';
          $declareItems[$k]['taxes']['sgst']                    = '0';
          $declareItems[$k]['taxes']['igst']                    = '18';
          $declareItems[$k]['seller_details']['gstin_number']   = '36AAVCS6697K1Z4';
          $declareItems[$k]['seller_details']['seller_address'] =                           //GST Regd address
                $param['shipperProvince'].' '.$param['shipperCity'].' '.
                $param['shipperStreet'].' '.$param['shipperStreetNo'];
          $declareItems[$k]['taxes']['total_tax'] =
              (float)$declareItems[$k]['taxes']['cgst']+(float)$declareItems[$k]['taxes']['sgst']+
              (float)$declareItems[$k]['taxes']['igst'];
        }
        
        //返回结果格式
        $result = [
            'status' => 'failed',
            'data'   => ''
        ];
        try {
            //开始请求
            $curl           = new curl();
            $requestData    = json_encode($requestData);
            $result         = $curl->http('post',$url,$requestData,'json',$header);
            $responseData   = $result['data'];
            $result['data'] = json_decode($responseData,true) ?: $responseData;
            
            
            //请求结果解析
            if (isset($result['data']['message'])       &&
                'Success' == $result['data']['message'] &&
            isset($result['data']['client_request']['awb_number'])) {
                $result['status'] = 'success';
                $result['data']   = $result['data']['client_request']['awb_number'];
            } else {
                $result['status'] = 'failed';
                $result['data']   = $result['data']['errors'] ??
                    ((isset($result['data']['status']) && isset($result['data']['message'])) ?
                        ($result['data']['status'].' '.$result['data']['message']):$responseData);
            }
        } catch (\Throwable $e) {
            $result['data'] = $e->getMessage();
            $responseData   = $e->getMessage();
        }
    
    
        //日志记录 成功/失败
        /*'failed' == $result['status'] ?
            LogServer::error('1',self::$_className,$requestData,$responseData) :
            LogServer::success('1',self::$_className,$requestData,$responseData);*/
        
        return $result;
    }
    
    
    /**查询轨迹
     * @param string $service_no SF16436807AJL
     * @param bool $env
     * @return array
     */
    public function orderQuery(string $service_no,bool $env):array
    {
        //环境参数配置
        $env         = !$env ? 'test' : 'product';
        $url         = self::$_env[$env]['orderQuery'];
        $url         = $url.$service_no;
        $header      = [
            'Authorization: '.self::$_env[$env]['token']
        ];
        
        //返回结果格式
        $result = [
            'status' => 'failed',
            'data'   => ''
        ];
        try {
            //开始请求
            $curl           = new curl();
            $result         = $curl->http('get',$url,'','',$header);
            $responseData   = $result['data'];
            $result['data'] = json_decode($responseData,true)?:$responseData;
            
            //请求结果解析
            if (isset($result['data']['message'])       &&
                'Success' == $result['data']['message'] &&
            isset($result['data']['client_request']['delivery_request_state_histories'])) {
                $trackingData = [];
                foreach ($result['data']['client_request']['delivery_request_state_histories'] as $k => $v) {
                    $trackingData[$k]['time']        = date('Y-m-d H:i:s',strtotime($v['created_at']));
                    $trackingData[$k]['description'] = $v['scan'] .'-'.$v['state'];
                }
                $result['status']               = 'success';
                unset($result['data']);
                $result['data'][0][$service_no] = $trackingData;
            } else {
                $result['status'] = 'failed';
                $result['data']   = $result['data']['responseMsg'] ?? $responseData;
            }
        } catch (\Throwable $e) {
            $result['data'] = $e->getMessage();
            $responseData   = $e->getMessage();
        }
    
        /*//日志记录 成功/失败
        'failed' == $result['status'] ?
            LogServer::error('2',self::$_className,$requestData,$responseData) :
            LogServer::success('2',self::$_className,$requestData,$responseData);*/
        return $result;
    }
    
    /**获取Pin code for tms/wms/客户erp
     * @param bool $env
     * @return array $result
     */
    public function getPinCodeApi (bool $env):array
    {
        //返回结果格式
        $result = [
            'status' => 'failed',
            'data'   => '',
        ];
    
        try {
            //验证是否过期(生命周期:7天),若过期重新获取
            redis::connect(self::$_redisConfig);
            $pinCode = redis::getA(self::$_className.'_pincode');
            if (!empty($pinCode)) {
                $result['status'] = 'success';
                $result['data']   = $pinCode;
            } else {
                $result           = $this->getPinCode($env);
                'success' == $result['status']    &&
                isset($result['data'][0]['code']) &&
                redis::setA(self::$_className.'_pincode',$result['data'],604800);
            }
        
            //源数据解析
            if ('success' == $result['status'] &&
                isset($result['data'][0]['code'])) {
                $pinCodes = [];
                foreach ($result['data']['data'] as $k => $v) {
                    $pinCodes[$k]['state']   = 'IN';
                    $pinCodes[$k]['city']    = 'IN';
                    $pinCodes[$k]['pincode'] = $v['code'];
                }
                $result['status'] = 'success';
                $result['data']   = $pinCodes;
            } else {
                $result['status'] = 'failed';
            }
        } catch (\Throwable $e) {
            $result['data'] = $e->getMessage();
//            LogServer::error('3',self::$_className,'getPinCodeApi:',$e->getMessage());
        }
        return $result;
    }
    
    /**获取pin code api
     * @param bool $env
     * @return array
     */
    private function getPinCode(bool $env) {
        //环境参数配置
        $env         = !$env ? 'test':'product';
        $url         = self::$_env[$env]['getPinCode'];
        $header      = [
            'Authorization: '.self::$_env[$env]['token']
        ];
        $requestData = [
            'url'    => $url,
            'header' => $header[0]
        ];
        $requestData = json_encode($requestData);
        
        //返回结果格式
        $result = [
            'status' => 'failed',
            'data'   => ''
        ];
        try {
            //开始请求
            $curl           = new curl();
            $result         = $curl->http('get',$url,'','',$header);
            $responseData   = $result['data'];
            $result['data'] = json_decode($responseData,true) ? : $requestData;
            
            //请求结果解析
            if (isset($result['data']['responseCode']) &&
                200 == $result['data']['responseCode'] &&
                isset($result['data']['requests'])) {
                $result['status'] = 'success';
                $result['data']   = $result['data']['requests'];
            } else {
                $result['status'] = 'failed';
                $result['data']   = $responseData;
            }
        } catch (\Throwable $e) {
            $result['data'] = $e->getMessage();
            $responseData   = $e->getMessage();
        }
        
        /*//日志记录 成功/失败
      'failed' == $result['status'] ?
          LogServer::error('3',self::$_className,$requestData,$responseData) :
          LogServer::success('3',self::$_className,$requestData,$responseData);*/
        return $result;
    }
    
    
    
}