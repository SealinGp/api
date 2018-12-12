<?php
include "../autoload.php";
header('content-type:application/json;');
$alljoy = [
//必填参数:
    'customerTmsId' => '1',
    'shippingMethod' => 'DLVBTB',
    'orderWeight' => '2',
    'orderPieces' => '1',
    'payType' => 'D',//Y:预付 D:COD
    'salesMoney' => (float)'800',//到付订单这个必填
    'parcelType' => 'Other',
    'awbNumber' => 'SF16436807AJL',
    //收件人
    'recipientName' => 'javed',
    'recipientPhone' => '909090990',
    'recipientCountryCode' => 'IN',
    'recipientProvince' => 'New Delhi',
    'recipientCity' => 'Okhla',
    'recipientStreet' => '',
    'recipientPostCode' => '110009',
    'recipientTel' => '1111111111',//座机
    //发件人
    'shipperName' => 'Archita',
    'shipperPhone' => '9667030414',
    'shipperCountryCode' => 'IN',
    'shipperProvince' => 'Karnataka',
    'shipperCity' => '',
    'shipperStreet' => '',
    'shipperPostCode' => '110077',
//选填参数:
    'referneceId' => '',//客户订单号
    'orderNo' => '',    //tms订单号
    'length' => '',
    'width' => '',
    'height' => '',
    //收件人
    'recipientCompany' => 'Alljoy company',
    'recipientEmail' => '',
    'recipientCertificateType' => '',//证件类型
    'recipientCertificateCode' => '',//证件码
    'recipientCertificateTime' => '',//过期时间
    'recipientTaxNo' => '',//税号
    'recipientStreetNo' => '5811 Jones Street Londonderry, NH 03053',//门牌号
    //发件人
    'shipperCompany' => 'ALLJOY SUPPLY CHAIN (INDIA) PVT LTD',
    'shipperTel' => '',
    'shipperEmail' => '',
    'shipperFax' => '',
    'shipperStreetNo' => 'GST Regd address',
    'is_battery' => '0',//带电
    //物品必填
    'declareItems' => [
        [
            'declareEnName' => 'toy1',
            'declareCnName' => 'toy_cn1',//可选
            'declareWeight' => '1',
            'declarePieces' => '1',
            'declarePrice'  => '900',
            'customsNo'     => '83294089',//海关编码 可选]
        ],
        [
            'declareEnName' => 'toy2',
            'declareCnName' => 'toy_cn2',//可选
            'declareWeight' => '1',
            'declarePieces' => '1',
            'declarePrice'  => '12',
            'customsNo'     => '456',//海关编码 可选]
        ]
    ]
];
$alljoy['order_no'] = $alljoy['shippingMethod'] .
    date('YmdHis', time());
try {
    $company = new API\company\Delhiverybtb();
//    $company = new API\company\Shadowfax();
//    $company = new API\company\Wing();
//    $company = new API\company\Mng();
      $result = $company->orderCreate($alljoy,false);
//    $result = $company->getPinCodeApi(false);
//    $result = $company->printLabel('999572675263',1,false);
//    $result = $company->orderQuery('DLVBTB20180810180536',false);
    var_dump($result);
} catch (\Error $e) {
    var_dump($e->getMessage());
}







