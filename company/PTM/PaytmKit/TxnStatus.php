<?php
	header("Pragma: no-cache");
	header("Cache-Control: no-cache");
	header("Expires: 0");
	// following files need to be included
	require_once("./lib/config_paytm.php");
	require_once("./lib/encdec_paytm.php");

	$param = array();
	$response = array();



    //状态查询
    $param = [
        "MID" => $conf['mid'] ,     //收款商户mid
        "ORDERID" => '1527817420'             //订单号
    ];
    $param['CHECKSUMHASH'] = getChecksumFromArray($param,$conf['merchant_key']);    //收款账户key

    /*//查询状态的数据格式
    $requestData = 'JsonData='.urlencode(json_encode($param));
    var_dump($requestData);
    exit;*/

    //开始请求
    $response = getTxnStatusNew($param);

    header('content-type:application/json;');
    var_dump($response);
    exit;
?>