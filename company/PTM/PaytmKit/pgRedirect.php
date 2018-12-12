<?php
header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("Expires: 0");
// following files need to be included
require_once("./lib/config_paytm.php");
require_once("./lib/encdec_paytm.php");
include '../../../redis/redis.php';

$checkSum = "";
$paramList = array();


// Create an array having all required parameters for creating checksum.
//必填参数
//$paramList["ORDER_ID"] = $_POST["ORDER_ID"];
//$paramList["CUST_ID"] = $_POST["CUST_ID"];
//$paramList["INDUSTRY_TYPE_ID"] = $_POST["INDUSTRY_TYPE_ID"];
//$paramList["CHANNEL_ID"] = $_POST["CHANNEL_ID"];
//$paramList["TXN_AMOUNT"] = $_POST["TXN_AMOUNT"];
//$paramList["MID"] = conf['mid'];
//$paramList["WEBSITE"] = conf[$_POST['channel']]['website'];
//$paramList["CALLBACK_URL"] = "http://localhost/API/company/PTM/PaytmKit/pgResponse.php";
/*选填参数
$paramList["MSISDN"] = $MSISDN; //Mobile number of customer
$paramList["EMAIL"] = $EMAIL; //Email ID of customer
$paramList["VERIFIED_BY"] = "EMAIL"; //
$paramList["IS_USER_VERIFIED"] = "YES"; //
*/

$test = [
        'ORDER_ID'         => time(),                                    //交易id(唯一)
        'INDUSTRY_TYPE_ID' => conf['industry_type_id'],                  //ptm提供给收款人的(测试:Retail)
        'MID'              => conf['mid'],                               //ptm提供给收款人的
        'WEBSITE'          => conf[$_POST['channel']]['website'],        //测试:WEBSTAGING/APPSTAGING 正式:ptm提供
        //收款人信息
         'CHANNEL_ID'      => conf[$_POST['channel']]['channel_id'],     //渠道 WEB:网页 WAP:APP
         'TXN_AMOUNT'      => '6.45',                                    //付款数
        //付款人信息
        'EMAIL'            => 'sealingp@163.com',                                   //付款人邮箱
        'CUST_ID'          => 'test@email.com',                          //付款人mid
        'MOBILE_NO'        => '18957169851',                             //付款人在ptm账号
//        'MOBILE_NO' => '854564--',                                     //付款人在ptm账号
        'CALLBACK_URL'     => 'http://localhost/API/company/PTM/PaytmKit/pgResponse.php'
];

$test['CHECKSUMHASH'] = getChecksumFromArray($test,conf['merchant_key']);
//var_dump(json_encode($test,JSON_PRETTY_PRINT));exit;
//\API\redis\redis::setA('PTM_test',$test);






//transaction status API
/*$trans_status = [
    'MID' => conf['mid'],
    'ORDERID' => "unpay_156472530",
];
header('content-type:application/json;');
$trans_status['CHECKSUMHASH'] = getChecksumFromArray($trans_status,$conf['merchant_key']);

$requestData = 'JsonData='.urlencode(json_encode($trans_status));//查询状态的请求数据格式
var_dump(json_encode($trans_status,JSON_PRETTY_PRINT),$requestData);
exit;*/

//refund parameters
/*$process_success = \API\redis\RedisZq::getA('PTM_process201806011013');
$refund = [
    'ORDERID'=> $process_success['ORDERID'],
    'MID'=> $process_success['MID'],
    'TXNID'=> $process_success['TXNID'],
    'TXNTYPE'=>"REFUND",                        //退款固定值
    'REFUNDAMOUNT'=>"1",                        //退款金额(可小于交易金额)
    'REFID'=>time(),                     //退款号(唯一:发起退款交易时填写)
    'CHECKSUM'=>$process_success['CHECKSUMHASH'],                     //退款号(唯一:发起退款交易时填写)
];*/
/*$refund = [
    'MID'=>conf['mid'],              //退款的收款人MID
    'ORDERID'=> "1528771042",                   //订单号(唯一:发起付款交易时提供)
    'TXNID'=>"70000938257",                     //交易号(唯一:付款交易完成后提供)
    'TXNTYPE'=>"REFUND",                        //退款固定值
    'REFUNDAMOUNT'=>"1.0",                      //退款金额(可小于交易金额)
    'REFID'=>time(),                            //退款号(唯一:发起退款交易时填写)
];
//\API\redis\RedisZq::setA('PTM_process_refund'.date('YmdHi',time()),$refund);
$refund['CHECKSUM'] = urlencode(getRefundChecksumFromArray($refund,conf['merchant_key'],1));
$requestData = 'JsonData='.json_encode($refund);//请求退款的请求数据格式
header('content-type:application/json;');
var_dump(json_encode($refund,JSON_PRETTY_PRINT),$requestData);
exit;*/

//refund status API
/*$refund_status = [
    'ORDERID'=> "unpay_50263549",
    'MID'=>conf['mid'],
    'REFID'=>'1528773317132',
];
$refund_status['CHECKSUMHASH'] = urlencode(getRefundChecksumFromArray($refund_status,conf['merchant_key'],1));
var_dump(json_encode($refund_status));
exit;*/






?>
<html>
    <head>
    <title>Merchant Check Out Page</title>
    </head>
    <body>
        <center><h1>Please do not refresh this page...</h1></center>
            <form method="post" action="<?php echo conf[$_POST['url']]['transaction'] ?>" name="f1">
            <table border="1">
                <tbody>
                <?php
                foreach($test as $name => $value) {
                    echo '<input type="hidden" name="' . $name .'" value="' . $value . '">';
                }
                ?>

                </tbody>
            </table>
            <script type="text/javascript">
                document.f1.submit();
            </script>
        </form>
    </body>
</html>
