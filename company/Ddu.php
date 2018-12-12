<?php
namespace API\company;
use API\redis\redis;
use API\RequestMethod\curl\curl;


/*支持查询方式
 2.waybill (Delhivery api 请求参数)    => service_no(alljoy api 数据库中字段名)
*/

class Ddu
{
    private static $_className = 'Ddu';
    private static $_env = [
        'test' => [
            'url' => 'http://courier.ddu-express.com/api/webservice.php?wsdl'
        ],
        'product' => [
            'url' => 'http://courier.ddu-express.com/api/webservice.php?wsdl'
        ]
    ];
    //redis配置
    private static $_redisConfig = [
        'host'     => '127.0.0.1',
        'port'     => '6379',
        'password' => '',
    ];
    /**创建订单
     * @param array $param
     * @param bool $env
     * @return array
     */
    public function orderCreate(array $param,bool $env):array
    {
        //环境参数配置
        $env = !$env ? 'test' : 'product';
        $url = self::$_env[$env]['url'];
        $requestData = [
            'ToCompany'          => $param['recipientCompany'],
            'ToAddress'          =>
                $param['recipientProvince'].' '.$param['recipientCity'].' '.
                $param['recipientStreet'].' '.$param['recipientStreetNo'],
            'ToLocation'         => $param['recipientCity'],
            'ToCountry'          => $param['recipientCountryCode'],
            'ToCperson'          => $param['recipientName'],
            'ToContactno'        => $param['recipientTel'],
            'ToMobileno'         => $param['recipientPhone'],
            'ReferenceNumber'    => $param['order_no'],
            'CompanyCode'        => 'A062',
            'Weight'             => $param['orderWeight'],
            'Pieces'             => $param['orderPieces'],
            'PackageType'        => 'Parcel',
            'CurrencyCode'       => 'AED',
            'NcndAmount'         => '200',
            'ItemDescription'    => $param['declareItems'][0]['declareEnName'],
            'SpecialInstruction' => ''
        ];
        
        //返回结果格式
        $result = [
            'status' => 'failed',
            'data'   => ''
        ];
        try {
            //开始请求
            $soap           = new \SoapClient($url);
            $result['data'] = $soap->CustomerBooking($requestData);
            $responseData   = json_encode($result['data']);
            
            //请求结果解析
            if (isset($result['data']['responseCode'])      &&
                1 == (int)$result['data']['responseCode']   &&
            isset($result['data']['responseArray'][0]['AWBNumber'])) {
                $result['status'] = 'success';
                $result['data']   = $result['data']['responseArray'][0]['AWBNumber'];
            } else {
                $result['status'] = 'failed';
                $result['data']   = '';
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
     * @param string $service_no DDU10000539
     * @param bool $env
     * @return array
     */
    public function orderQuery(string $service_no,bool $env):array
    {
        //环境参数配置
        $env         = !$env ? 'test' : 'product';
        $url         = self::$_env[$env]['url'];
        $requestData = [
            'BookingNumber' => $service_no,
            'CompanyCode'   => 'A062'
        ];
        
        //返回结果格式
        $result = [
            'status' => 'failed',
            'data'   => ''
        ];
        try {
            //开始请求
            $soap           = new \SoapClient($url);
            $result['data'] = $soap->GetStatusDetails($requestData);
            $responseData   = json_encode($result['data']);
            $result['data'] = json_decode($responseData,true) ? : $responseData;
            
            //请求结果解析
            if (isset($result['data']['responseCode'])      &&
                1 == (int)$result['data']['responseCode']   &&
                isset($result['data']['responseArray'])) {
                $trackingData = [];
                foreach ($result['data']['responseArray'] as $k => $v ) {
                    $trackingData[$k]['time']        =
                        date('Y-m-d H:i:s',strtotime($v['SDate'] .' '. $v['STime']));
                    $trackingData[$k]['description'] =
                        $v['CStatus'].' '.$v['SDetails'] .' - '.$v['SLocation'];
                }
                $result['status']               = 'success';
                unset($result['data']);
                $result['data'][0][$service_no] = $trackingData;
            } else {
                $result['status'] = 'failed';
                $result['data']   = $result['data']['responseMessage'] ?? 'internet error';
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
    
    public function printLabel():array
    {
        
    }
    
    /**获取Pin code for tms/wms/客户erp
     * @param bool $env
     * @return array $result
     */
    public function getPinCodeApi (bool $env):array
    {
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
          'data' => [
              [
                  'state' => '',
                  'city' => '',
                  'pincode' => '',
              ]
          ],
        ];
        return $result;
    }
    
    
    
}