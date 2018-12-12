<?php
namespace API\company;
use API\redis\redis;
use API\RequestMethod\curl\curl;

/*支持查询方式
 2.waybill (Delhivery api 请求参数)    => service_no(alljoy api 数据库中字段名)
*/

class Delhiverybtb
{
    private static $_className = 'Delhiverybtb';
    private static $_env = [
        'test' => [
            'orderCreate' => 'https://btob-api-dev.delhivery.com/manifest',
            'orderQuery'  => 'https://btob-api-dev.delhivery.com/track/',
            'getToken'    => 'https://api-stage-ums.delhivery.com/v2/login/',
            'username'    => 'OHANNACREATIONB2B',
            'password'    => 'ohanna527cre'
            
        ],
        'product' => [
            'orderCreate' => 'https://btob.api.delhivery.com/v2/manifest',
            'orderCreate' => 'https://btob.api.delhivery.com/v2/manifest',
            'getToken'    => 'https://api-ums.delhivery.com/v2/login/',
            'username'    => '',
            'password'    => ''
            
        ]
    ];
    //redis配置
    private static $_redisConfig = [
        'host'     => '127.0.0.1',
        'port'     => '6379',
        'password' => '',
    ];
    
    private static $_warehouse = [
        'test' => [
            'pickup_location'  => 'OHANNA_CREATION_B2B_TEST_WAREHOUSE',
            'dropoff_location' => 'ohanna_test',
        ],
        'product' => [
            'pickup_location'  => '',
            'dropoff_location' => '',
        ]
    ];


//array(3) {
//["status"]=>
//string(7) "success"
//["data"]=>
//string(20) "DLVBTB20180813165913"
//["origin"]=>
//string(50) "{"job_id": "2aacb92f-9ed7-11e8-bc45-fb9c6ee930fd"}"
//}
    
    /**创建订单
     * @param array $param
     * @param bool $env
     * @return array $result
     */
    public function orderCreate(array $param,bool $env):array
    {
        //环境参数设置
        $env = !$env ? 'test' : 'product';
        
        //返回结果格式
        $result = [
            'status' => 'failed',
            'data'   => ''
        ];
        try {
            $curl = new curl();
            //获取token api(token 24小时有效期,请求1次/24小时)
            redis::connect(self::$_redisConfig);
            if (!$token = redis::getA(self::$_className.'_token')) {
                $url         = self::$_env[$env]['getToken'];
                $requestData = [
                    'username' => self::$_env[$env]['username'],
                    'password' => self::$_env[$env]['password']
                ];
                $requestData = json_encode($requestData);
                $token       = $curl->http('post',$url,$requestData,'json');
                $token       = $token['data'];
                $token       = json_decode($token,true) ? : $token;
                if (isset($token['jwt'])) {
                    redis::setA(self::$_className.'_token',$token, 86400);
                } else {
                    Throw new \Exception($token);
                }
            }
            $token = $token['jwt'];
            
            //请求参数环境配置
            $url          = self::$_env[$env]['orderCreate'];
            $header       = [
                'Authorization: Bearer '.$token
            ];
            $requestData  = [
                'ident'            => $param['order_no'],                       //LR Number (Shipment Identifier)
                'pickup_location'  =>                                           //拿货地点
                    self::$_warehouse[$env]['pickup_location'],
                'dropoff_location' =>                                           //放货地点
                    self::$_warehouse[$env]['dropoff_location'],
                'd_mode'           => 'D' == $param['payType']?'CoD':'Prepaid', //订单类型Prepaid:预付|CoD:到付
                'amount'           =>                                           //到付金额
                    'D' == $param['payType']?(float)$param['salesMoney']:0,
                'invoices'         => [],
                'suborders'        => [],
                'weight'           => (float)$param['orderWeight'],
                'cb'               => [
                    'uri'              => 'https://sandbox.alljoylogistics.com/index/saveStatus',
                    'method'           => 'POST',
//                    'authorization'    => '',
                ]
            ];
            $amount = 0;
            foreach ($param['declareItems'] as $k => $v) {
                $amount+= (float)$v['declarePrice'];
                $requestData['invoices'][$k]['ident']          = $param['order_no']; //订单号
                $requestData['invoices'][$k]['n_value']        = 0;                  //每个箱子总价
                $requestData['invoices'][$k]['ewaybill']       = '';                 //
                $requestData['suborders'][$k]['ident']         = $param['order_no']; //订单号
                $requestData['suborders'][$k]['count']         = (int)$k+1;          //箱子序号
                $requestData['suborders'][$k]['description']   = $v['declareEnName'];//描述
            }
            $requestData['invoices'][0]['n_value'] = $amount;
            
            //开始请求 下单
            $log            = [];
            $requestData    = json_encode($requestData);
            $result         = $curl->http('post',$url,$requestData,'json',$header);
            $responseData   = $result['data'];
            $log[]          = $result['data'];
            $result['data'] = json_decode($responseData,true)?:$responseData;
            /*if (!isset($result['data']['job_id'])) {
                Throw new \Exception(($result['data']['Message'] ?? $responseData));
            }*/
            
            //查询下单状态
            /*$result['data'] = $result['data']['job_id'];
            $url            = $url.'?job_id='.$result['data'];
            $requestData    = $url;
            $result         = $curl->http('get',$url,'','',$header);
            $responseData   = $result['data'];*/
                        
            //请求结果解析
//            if (isset($result['data']['status']['type'])) {
            if (isset($result['data']['job_id'])) {
                $result['status'] = 'success';
                $result['data']   = $param['order_no'];
                $result['origin'] = $responseData;
            } else {
                $result['status'] = 'failed';
                $result['data']   = $result['data']['Message'] ?? $responseData;
            }
        } catch (\Throwable $e) {
            $result['data'] = $e->getMessage();
        }
        
        /*//日志记录 成功/失败
        'failed' == $result['status'] ?
            LogServer::error('1',self::$_className,$requestData,json_encode($result['data'],JSON_PRETTY_PRINT)) :
            LogServer::success('1',self::$_className,$requestData,json_encode($result['data'],JSON_PRETTY_PRINT));*/
        return $result;
    }
    
    /**查询轨迹
     * @param string $service_no DLVBTB20180810180536
     * @param bool $env
     * @return array
     */
    public function orderQuery(string $service_no,bool $env):array
    {
        //环境参数设置
        $env = !$env ? 'test' : 'product';
        
        //返回结果格式
        $result = [
            'status' => 'failed',
            'data'   => '',
        ];
        try {
            //开始请求
            $curl = new curl();
            //获取token api(token 24小时有效期,请求1次/24小时)
            redis::connect(self::$_redisConfig);
            if (!$token = redis::getA(self::$_className.'_token')) {
                $url         = self::$_env[$env]['getToken'];
                $requestData = [
                    'username' => self::$_env[$env]['username'],
                    'password' => self::$_env[$env]['password']
                ];
                $requestData = json_encode($requestData);
                $token       = $curl->http('post',$url,$requestData,'json');
                $token       = $token['data'];
                $token       = json_decode($token,true) ? : $token;
                if (isset($token['jwt'])) {
                    redis::setA(self::$_className.'_token',$token, 86400);
                } else {
                    Throw new \Exception($token);
                }
            }
            $token          = $token['jwt'];
            
            $url            = self::$_env[$env]['orderQuery'];
            $header         = [
                'Authorization: Bearer '.$token
            ];
            $requestData    = [
                'job_id' => $service_no
            ];
            $requestData    = http_build_query($requestData);
            $url            = $url.$service_no;
            $requestData    = $url;
            
            $result         = $curl->http('get',$url,'','',$header);
            $responseData   = $result['data'];
            $result['data'] = json_decode($responseData,true) ? : $responseData;
            
            var_dump($result,$token);exit;
            
            //请求结果解析/判断
            if (isset($result['data']['ShipmentData'][0]['Shipment']['Scans'])) {
                $trackingData = [];
                
                $result['status'] = 'success';
                $result['data']   = $trackingData;
            } else {
                $result['status'] = 'failed';
                $result['data']   = $result['data']['status']['reason'] ?? $responseData;
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
        $url = self::$_env[$env]['getPinCode'].'?'.$requestData;
        
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