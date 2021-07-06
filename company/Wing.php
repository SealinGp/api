<?php
namespace API\company;
use API\redis\redis;
use API\RequestMethod\curl\curl;


/*支持查询方式 阿联酋
 2.waybill (Delhivery api 请求参数)    => service_no(alljoy api 数据库中字段名)
*/
class Wing
{
    private static $_className = 'Wing';
    private static $_env = [
        'test' => [
            'url' => [
                'getToken'    => 'https://stagingapi.wing.ae/api/v1/customer/authenticate',
                'orderCreate' => 'https://stagingapi.wing.ae/api/v1/customer/order',
                'orderQuery'  => 'https://stagingapi.wing.ae/api/v1/customer/order/',
                'printLabel'  => 'https://stagingapi.wing.ae//api/v1/customer/order/download_manifests'
            ],
            'username' => 'senlin@alljoylogistics.com',
            'password' => '@!pass1234'
        ],
        'product' => [
            'url' => [
                'getToken'    => 'https://liveapi.wing.ae/api/v1/customer/authenticate',
                'orderCreate' => 'https://liveapi.wing.ae/api/v1/customer/order',
                'orderQuery'  => 'https://liveapi.wing.ae/api/v1/customer/order/',
                'printLabel'  => 'https://liveapi.wing.ae//api/v1/customer/order/download_manifests'
            ],
            'username' => 'senlin@alljoylogistics.com',
            'password' => '!@alljoy#$%'
        ]
    ];
    //redis配置
    private static $_redisConfig = [
        'host'     => '127.0.0.1',
        'port'     => '6379',
        'password' => '',
    ];
    //wing 迪拜海外仓地址(拣货地点)
    private static $_warehouse   = [
        'shipperCompany'  => 'Wing',                         //公司
        'shipperName'     => 'alljoy',                       //名
        'shipperEmail'    => 'senlin@alljoylogistics.com',   //邮件
        'shipperPhone'    => '+8675582311388',               //电话
        //仓库位置
        'shipperProvince' => 'United Arab Emirates ',        //省份|州
        'shipperCity'     => 'Dubai',                        //城市
        'shipperStreet'   => 'Dubai investments park 2, ',   //街道
        'shipperStreetNo' => 'block 7, warehouse 17,'.       //街道号码
            'in front of souq DXB1 warehouse',
        'shipperPostCode' => '',                             //邮编
    ];
    /**创建订单
     * @param array $param
     * @param bool $env
     * @return array
     */
    public function orderCreate(array $param,bool $env):array
    {
        //环境配置
        $env = !$env ? 'test' : 'product';
        
        //返回结果格式
        $result = [
            'status' => 'failed',
            'data'   => ''
        ];
        try {
            
            //开始请求
            $curl = new curl();
            //获取token api(token 24小时有效期,请求1次/24小时)
            redis::connect(self::$_redisConfig);
            if (!$token = redis::getA(self::$_className.'_token')) {
                $url = self::$_env[$env]['url']['getToken'];
                $requestData = [
                    'username'    => self::$_env[$env]['username'],
                    'password'    => self::$_env[$env]['password'],
                    'remember_me' => true
                ];
                $token = $curl->http('post',$url,json_encode($requestData),'json');
                $responseData = $token['data'];
                $token = $token['data'];
                $token = json_decode($token,true) ? : $token;
                if (isset($token['data']['id_token'])) {
                    redis::setA(self::$_className.'_token',json_decode($responseData,true), 86400);
                } else {
                    Throw new \Exception($responseData);
                }
            }
            $token = $token['data']['id_token'];
            
            
            //下单api 参数环境设置
            $url    = self::$_env[$env]['url']['orderCreate'];
            $header = [
                'Authorization: Bearer '.$token
            ];
            $requestData = [];
            $requestData  = [
                'locations' => [
                    [//拣货地点
                        'city'         => self::$_warehouse['shipperCity'],
                        'pickup'       => false,
                        'address'      =>
                            self::$_warehouse['shipperProvince'].self::$_warehouse['shipperCity'].
                            self::$_warehouse['shipperStreet'] .self::$_warehouse['shipperStreetNo'].
                            self::$_warehouse['shipperPostCode'],
                        'contact_name' => self::$_warehouse['shipperName'],
                        'email'        => self::$_warehouse['shipperEmail'],
                        'phone'        => self::$_warehouse['shipperPhone'],
                        'company_name' => self::$_warehouse['shipperCompany'],
                        'address_type' => 'business'
                    ],
                    [//收件人地址
                        'city'         => $param['recipientCity'],
                        'pickup'       => true,
                        'address'      =>
                            $param['recipientProvince'].' '.$param['recipientCity'].' '.
                            $param['recipientStreet'].' '.$param['recipientStreetNo'].' '.
                            $param['recipientPostCode'],
                        'contact_name' => $param['recipientName'],
                        'email'        => $param['recipientEmail'],
                        'phone'        => $param['recipientPhone'],
                        'company_name' => $param['recipientCompany'],
                        'address_type' => 'business'                                         //地址类型business(商业)residential(住宅)
                    ]
                ],
                'package'  => [
                    'courier_type'   => 'in_5_days',                                         //in_5_days|next_day|same_day|bullet|next_day
                    'direction_data' => [
                        'pickup_address' => 'Khasra NO 72 & 73 Extended'.
                            'Laldoora Of Village Bamnoli Sector 28 Dwarka New Delhi 110077'
                    ]
                ],
                'payment_type'    => 'cash',                                                 //付款方式
                'payer'           =>                                                         //付款方
                    'D' == $param['payType'] ? 'recipient' : 'client',
                'recipient_not_available' => 'do_not_deliver',                               //COD订单时,若收件人不在,是否交货
                'charge_items'    => &$declareItems,                                         //订单商品信息
                'note'            => '',                                                     //备注
                'force_create'    => true,
                'pickup_time_now' => true,
                'fragile'         => false,                                                  //是否是易碎产品
                'parcel_value'    => $param['declareItems'][0]['declarePrice'],
                'reference_id'    => $param['order_no']
            ];
            $payType = 'D' == $param['payType'];
            foreach ($param['declareItems'] as $k => $v) {
                $declareItems[$k]['charge_type'] = $payType ? 'cod' : 'service_custom';
                $declareItems[$k]['charge']      = $payType ? (int)$param['salesMoney'] : 0;
                $declareItems[$k]['payer']       = $payType ? 'recipient' : 'client';
            }
            $requestData  = json_encode($requestData,JSON_PRETTY_PRINT);
            $result       = $curl->http('post',$url,$a,'json',$header);
            $responseData = $result['data'];
            
            //请求结果解析
            $result['data'] = json_decode($result['data'],true) ?: $result['data'];
            if (isset($result['data']['status'])       &&
                'success' == $result['data']['status'] &&
                isset($result['data']['data']['order_number'])) {
                $result['status'] = 'success';
                $result['data']   = $result['data']['data']['order_number'];
            } else {
                $result['status'] = 'failed';
                $result['data']   = $result['data']['error'] ??
                    ($result['data']['message'] ?? $responseData);
            }
        } catch (\Throwable $e) {
            $result['data'] = $e->getMessage();
            $responseData   = $e->getMessage();
        }
        
        
        //日志记录 成功/失败
        'failed' == $result['status'] ?
            LogServer::error('1',self::$_className,$requestData,$responseData) :
            LogServer::success('1',self::$_className,$requestData,$responseData);
        
        return $result;
    }
    
    
    /**查询轨迹
     * @param string $service_no 999572675263
     * @param bool $env
     * @return array
     */
    public function orderQuery(string $service_no,bool $env):array
    {
        //环境配置
        $env = !$env ? 'test' : 'product';
        
        //返回结果格式
        $result = [
            'status' => 'failed',
            'data'   => ''
        ];
        try {
            //获取token api(token 24小时有效期,请求1次/24小时)
            $curl = new curl();
            redis::connect(self::$_redisConfig);
            if (!$token = redis::getA(self::$_className.'_token')) {
                $url          = self::$_env[$env]['url']['getToken'];
                $requestData  = [
                    'username'    => self::$_env[$env]['username'],
                    'password'    => self::$_env[$env]['password'],
                    'remember_me' => true
                ];
                $token        = $curl->http('post',$url,json_encode($requestData),'json');
                $responseData = $token['data'];
                $token        = $token['data'];
                $token        = json_decode($token,true) ? : $token;
                if (!isset($token['data']['id_token'])) {
                    Throw new \Exception($responseData);
                }
                redis::setA(self::$_className.'_token',json_decode($responseData,true), 86400);
            }
            $token = $token['data']['id_token'] ?? '';
            
            //开始请求
            $url            = self::$_env[$env]['url']['orderQuery'] .$service_no .'/history_items';
            $requestData    = $url;
            $header         = [
                'Authorization: Bearer '.$token,
                'Accept: application/json'
            ];
            $result         = $curl->http('get',$url,'','',$header);
            $responseData   = $result['data'];
            
            //请求结果解析
            $result['data'] = json_decode($result['data'],true) ? : $result['data'];
            if (isset($result['data']['data']['list']) ) {
                $trackingData = [];
                foreach ($result['data']['data']['list'] as $k => $v) {
                    $trackingData[$k]['time']        = date('Y-m-d H:i:s',strtotime($v['date']));
                    $trackingData[$k]['description'] = $v['status_desc'] ?? ($v['status']);
                    $trackingData[$k]['description'] = $trackingData[$k]['description'] .' - UAE';
                }
                $result['status'] = 'success';
                unset($result['data']);
                $result['data'][0][$service_no] = $trackingData;
            } else {
                $result['status'] = 'failed';
                $result['data']   = $result['data']['message'] ??
                    ($result['data']['code'] ??
                        ($result['data']['msg'] ?? $result['data']) );
            }
        } catch (\Throwable $e) {
            $result['data'] = $e->getMessage();
        }
        
        /*//日志记录 成功/失败
        'failed' == $result['status'] ?
            LogServer::error('2',self::$_className,$requestData,$responseData) :
            LogServer::success('2',self::$_className,$requestData,$responseData);*/
        return $result;
    }
    
    /**获取标签url
     * @param string $service_no
     * @param string $labelSize
     * @param bool   $env
     * @return array $result
     */
    public function printLabel(string $service_no,string $labelSize,bool $env):array
    {
        $env    = !$env ? 'test' : 'product';
        $result = [
            'status' => 'failed',
            'data'   => ''
        ];
        try {
            $curl         = new curl();
            //获取token api(token 24小时有效期,请求1次/24小时)
            redis::connect(self::$_redisConfig);
            if (!$token = redis::getA(self::$_className.'_token')) {
                $url = self::$_env[$env]['url']['getToken'];
                $requestData = [
                    'username'    => self::$_env[$env]['username'],
                    'password'    => self::$_env[$env]['password'],
                    'remember_me' => true
                ];
                $token = $curl->http('post',$url,json_encode($requestData),'json');
                $responseData = $token['data'];
                $token = $token['data'];
                $token = json_decode($token,true) ? : $token;
                if (isset($token['data']['id_token'])) {
                    redis::setA(self::$_className.'_token',json_decode($responseData,true), 86400);
                } else {
                    Throw new \Exception($responseData);
                }
            }
            $token = $token['data']['id_token'];
            
            //参数环境设置
            $url         = self::$_env[$env]['url']['printLabel'];
            $header = [
                'Authorization: Bearer '.$token
            ];
            $requestData = [
                'compact'  => true,
                'customer' => true,
                'ids' => [
                    0
                ],
                'order_numbers' => [
                    $service_no
                ]
            ];
            $requestData  = json_encode($requestData);
            
            //开始请求
            $result       = $curl->http('post',$url,$requestData,'json',$header);
            $responseData = $result['data'];
            $result['data'] = json_decode($responseData,true)?:$responseData;
            
            //请求结果解析
            if (isset($result['data']['status'])   &&
            'success' == $result['data']['status'] &&
            isset($result['data']['data']['value'])) {
                $result['status'] = 'success';
                $result['data']   = $result['data']['data']['value'];
            } else {
                $result['status'] = 'failed';
                $result['data']   = $responseData;
            }
        } catch (\Throwable $e) {
            $result['data'] = $e->getMessage();
            $responseData   = $e->getMessage();
        }
        
        //日志记录 成功/失败
        'failed' == $result['status'] ?
            LogServer::error('1',self::$_className,$requestData,$responseData) :
            LogServer::success('1',self::$_className,$requestData,$responseData);
        
        return $result;
    }
    
    /**获取Pin code for tms/wms/客户erp
     * @param bool $env
     * @return array $result
     */
    public function getPinCodeApi (bool $env) {
        $result = $this->getPinCode($env);
        return $result;
    }
    
    /**获取pin code api
     * @param bool $env
     * @return array
     */
    private function getPinCode(bool $env) {
        $result = [
            'status' => 'success',
            'data'   => [
                [
                    'state'   => '',
                    'city'    => '',
                    'pincode' => '',
                ]
            ],
        ];
        return $result;
    }
    
    
    
}