<?php
namespace API\company;
//换号模式(尾程会返回服务商单号)
class Imile
{
    protected $port = '8713';
    protected $url = 'http://47.52.205.72:8713/IMILEDianShangInterface/K9/recivevOnLineOrder.do';
    protected $query_url = 'http://47.52.205.72:8710/IMILEDianShangInterface/K9/getTrackingData.do';
    protected $token = 'gK5Ji6hy76mX';

    /**
     * 创建订单
     * @param array $param
     * @return bool|mixed
     */
    public function orderCreate($param = [])
    {
        //物品信息
        $number= 0;
        foreach ($param['declareItems'] as $k => $v) {
            $declareEnName[$k] = $v['declareEnName'];
            $number = $number+$v['declarePieces'];//todo 获取件数
        }
        $orderPieces = $number;
        $goodsName = implode('/', $declareEnName);
        $token = $this->token;
        $requestData = [
            [
                'logisticsId' => $param['order_no'],                                                    //必填，客户订单号
                'dicCode' => time(),                                                                    //必填，批次号
                'orderCreateTime' => date('Y-m-d H:i:s', time()),                                       //必填，录单时间
                'senderName' => $param['shipperName'],                                                  //必填，寄件人
                'senderCompany' => $param['shipperCompany'],                                            //必填，寄件公司
                'senderAddress' => $param['shipperStreet'].$param['shipperStreetNo'],                   //必填, 寄件地址
                'senderMobile' => $param['shipperPhone'],                                               //必填，寄件电话
                'senderCounty' => $param['shipperCountryCode'],                                         //选填，寄件区/县
                'senderCity' => $param['shipperCity'],                                                  //选填，寄件城市
                'senderProvince' => $param['shipperProvince'],                                          //选填，寄件省份
                'receiverName' => $param['recipientName'],                                              //必填，收件人
                'receiverCompany' => $param['recipientCompany'],                                        //必填，收件人公司
                'receiverProvince' => $param['recipientProvince'],                                      //必填，收件人省份
                'receiverCounty' => $param['recipientCountryCode'],                                     //必填，收件人区/县
                'receiverCity' => $param['recipientCity'],                                              //必填，收件人城市
                'receiverAddress' =>  $param['recipientStreet'].$param['recipientStreetNo'],            //必填，收件人地址
                'receiverMobile' => $param['recipientPhone'],                                           //必填，收件人电话
                'pieceNumber' => $orderPieces,                                                          //必填，件数
                'packageWeight' => $param['orderWeight'],                                               //必填，录单重量，单位kg
                'paymentType' => '月结',                                                                //必填，支付方式
                'goodsPayment' => $param['salesMoney'],                    				                //选填，代收货款
                'remark' => '全和悦订单',                                                                //选填，备注
                'goodsName' => $goodsName,                                                              //必填，物品名称
                'orderSource' => 'ALLJOY',                                                              //必填，数据来源
                'receiverCountry' => $param['recipientCountryCode']                                     //必填，收件国家
            ]
        ];
        //国家
        //$requestData[0]['receiverCountry'] = $param['recipientCountryCode'] == 'SA' ? 'Saudi Arabia' : 'UAE';

        $logistics_interface = json_encode($requestData,JSON_UNESCAPED_UNICODE);
        $token = md5($logistics_interface.$token);
        $logistics_interface = urlencode($logistics_interface);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_PORT => $this->port,
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "logistics_interface={$logistics_interface}&data_digest={$token}",
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Content-Type: application/x-www-form-urlencoded"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return $data = ['status'=>'failed','msg'=>'Request failed'];
        }
        $result = json_decode($response, true);
        if($result['stauts'] == '4'){
            LogServer::success('1', 'Imile', json_encode($requestData,JSON_UNESCAPED_UNICODE), json_encode($result,JSON_UNESCAPED_UNICODE));
            $data = ['status'=>'success', 'trackingNo'=>$result['data']['shipping_number']];
        }else{
            LogServer::error('1', 'Imile', json_encode($requestData,JSON_UNESCAPED_UNICODE), json_encode($result['msg'],JSON_UNESCAPED_UNICODE));
            $data = ['status'=>'failed','msg'=>$result['msg']];
        }
        return $data;
    }
    /**
     * 查询轨迹
     * @param string $orderId
     * @param string $type
     * @return array
     */
    //type为0或者1，当type为0时，code为运单编号例如：100000000082，多个运单号用逗号分割.当type为1时，code为订单编号，例如：xxxxxxxxx
    public function orderQuery($orderId = '', $type = '')
    {
        $type = 0;
        $allTrackingNumber = urlencode($orderId);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_PORT => $this->port,
            CURLOPT_URL => $this->query_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "code={$allTrackingNumber}&type={$type}",
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Content-Type: application/x-www-form-urlencoded",
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return $data = ['status'=>'failed','msg'=>'Request failed'];
        }
        $result = json_decode($response, true);
        if ($result['stauts'] == 4) {
            LogServer::success('2', 'Imile', json_encode($orderId), json_encode($result,JSON_UNESCAPED_UNICODE));
            $data = ['status'=>'success', 'data'=>$result['data']];
        } else {
            LogServer::error('2', 'Imile', json_encode($orderId), json_encode($result['msg'],JSON_UNESCAPED_UNICODE));
            $data = ['status'=>'failed','msg'=>$result['msg']];
        }
        return $data;

    }
}
