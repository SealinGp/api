<?php
namespace ext;
class Xms
{
    //Xms
    private static $_token = 'c89cc2e45556409aa41e83ddd99d9ad5';
    private static $_wsdlurl = 'http://tms.anserx.com:8086/xms/services/order?wsdl';
    private static $_client;

    /**创建物流订单
     * @param array $reqParam 必选参数
     * @param  array $optParam 可选参数
     * @return string $result 返回格式见最底部
     * */
    public function createOrder($reqParam = [],$optParam = [])
    {
        $reqParam = $reqParam ? : [//必填参数
            'transportWayCode' => '',
            'cargoCode' => 'W',   //W:包裹 D:文件
            'destinationCountryCode' => 'JP',//目的国
            'consigneeName' => 'alljoy_test',//收件人姓名
            'street' => 'alljoy_test_address',
            'city' => 'alljoy_test_city',
            'province' => 'alljoy_test_pro',
            'weight' => '0.683', //预报重量  0-1000kg
            'insured' => 'N', //买保险
            'goodsCategory' => 'G', //G:礼物 D:文件 S:商业样本 R:回货品    O:其他
            'pieces' => '1',//货物件数
            'declareItems' => [//货物明细$
                [
                    'name'      => 'phone',
                    'pieces'    => '1',
                    'netWeight' => '0.25',//净重
                    'unitPrice' => '2',//单价
                    'cnName' => '',  //中文名 选填
                    'productMemo' => '',  //配货备注 (库位:A010102  SKU:P03231 品名:帆布包 颜色:红色 数量:1) 选填
                    'customsNo' => '',//海关编码 8/10位数 选填
                ]
            ],
            'transportWayCode' => 'YDJP'//运输方式,通过getTransportList()获取
        ];
        $optParam = $optParam ? : [//选填参数
            'orderNo' => '',         //客户单号(长度<32)
            'trackingNo' => '',     //服务商跟踪号码.若填写,需符合运输方式中规定的编码规则(长度<32)
            'originCountryCode' => '',//起运国
            'shipperCompanyName' => '',//发件人信息
            'shipperName' => '',
            'shipperAddress' => '',
            'shipperTelephone' => '',
            'shipperMobile' => '',
            'shipperPostcode' => '',
            'consigneeCompanyName' => '',//收件人信息
            'consigneePostcode' => '',
            'consigneeTelephone' => '',
            'consigneeMobile' => '',
            'goodsDescription' => ''
        ];
        $ways = self::getTransportList();
        if (!$ways) {
            return json_encode(['status' => 'failed','data' => '']);
        }
        $reqParam = array_merge($reqParam,$optParam);
        $reqParam['transportWayCode'] = $ways[0]['code'];//运输方式
        $result = self::$_client->createAndAuditOrder(self::$_token,$reqParam);
//        $result->error->errorInfo;
        if(!$result->success){
            $result = [
                'status' => 'failed',
                'data' => ''
            ];
            return json_encode($result);
        }
        $result = [
            'status' => 'success',
            'data' => [
                'trackingNumber' => $result->trackingNo,//追踪号
//                'orderId' => $result->id                   //订单号
            ]
        ];
        return json_encode($result);
    }
    /**查询订单轨迹
     * @param string $trackNum 物流单号
     * @return string $result 返回格式见最底部
     * */
    public function trackOrder(string $trackNum = '')
    {
        self::$_token = 'fa8081fb3dd94a43b61292756f967298'; //真实token跟订单
        $trackNum = '1612210358105';
        $trackNum = $trackNum ? : '164853852586';
        self::$_client = new \SoapClient (self::$_wsdlurl, array ('encoding' => 'UTF-8' ));
        $result = self::$_client->getTrack(self::$_token, $trackNum);
        if (!$result || !$result->success) {
            $result = [ 'status' => 'failed','data' => ''];
            return json_encode($result);
        }
        $result = json_decode(json_encode($result,JSON_UNESCAPED_UNICODE), true);
        $endResult = [//数据返回格式
            'status' => 'success',
            'data' => []
        ];
        $track = isset($result['trace']['sPaths']) ? $result['trace']['sPaths'] : [];//sPaths 国内轨迹 rPath 国外轨迹
        if ($track) {
            foreach ($track as $k1 => $v1) {
//                $endResult['data'][0][$result['trace']['tno']][$k1] = [//多个
                $endResult['data'][$result['trace']['tno']][$k1] = [
                    'time' => $v1['pathTime'],
                    'description' => $v1['pathInfo'],
                ];
            }
            $endResult['cargo_status'] = $result['trace']['status'];
        }
        //结果货物的cargo_status字段解释
        $status = [
            '0' => 'no track',  //未查件
            '1' => 'no internet',//未上网
            '2' => 'in transport',//转运中
            '3' => 'delivery', //妥投
            '4' => 'exception', //异常
            '5' => 'lost', //丢失
            '6' => 'return',//退件
            '7' => 'destory' //销毁
        ];
        if (isset($endResult['cargo_status'])) {
            $endResult['cargo_status'] = $status[$endResult['cargo_status']];
        }
        $result = $endResult;
        $result['data'] = $result['data'] ? : [$trackNum=>[]];
        return json_encode($result,JSON_UNESCAPED_UNICODE);
    }
    private static function soap(string $method,$param = [])
    {
        self::$_client = new \SoapClient (self::$_wsdlurl, array ('encoding' => 'UTF-8' ));
        $result = self::$_client->__getFunctions();
        foreach ($result as $v) {
            if (false !== strripos($v, $method)) {
                $result = 1;
                break;
            }
        }
        $result = $result ? ( $param ? self::$_client->$method(self::$_token,$param) : self::$_client->$method(self::$_token) ) : 0;
        return $result;
    }
    public static function getTransportList()
    {
        $result = self::soap('getTransportWayList');
        if (!$result || !$result->success) {
            $result = ['status' => 'failed','data'=>''];
            return [];
        }
        foreach ($result->transportWays as $k1 => $v1) {
            $ways[$k1]['code'] = $v1->code;
            $ways[$k1]['name'] = $v1->name;
        }
        return $ways;
    }
    //打印标签
    public function printOrder()
    {
        $param = [
            'trackingNo' => '164853948766',
            'printSelect' => '1',//打印样式
            'pageSizeCode' => '1',
        ];
        $res = self::soap('printOrder',$param);
        return $res;
    }
    /*创建物流订单结果 json string
     *成功:
     * [
     *      'status' => 'success',
     *      'data' => ['trackNum' => '164853852586'],//物流单号
     * ]
     *查询订单轨迹结果 json string
     * 成功:
     * [
     *      'status' => 'success',
     *       'data' => [
     *          '物流单号' => [
     *              ['time' => '',
     *              'description' => '']
     *          ]
     *      'cargo_status' => 'lost'
     * ]
     *
     *调用接口失败统一返回值
     *  ['status' => 'failed','data' => '']
     *
     * */
}