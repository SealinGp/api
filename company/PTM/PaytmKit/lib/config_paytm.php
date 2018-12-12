	<?php
/*
- Use PAYTM_ENVIRONMENT as 'PROD' if you wanted to do transaction in production environment else 'TEST' for doing transaction in testing environment.
- Change the value of PAYTM_MERCHANT_KEY constant with details received from Paytm.
- Change the value of PAYTM_MERCHANT_MID constant with details received from Paytm.
- Change the value of PAYTM_MERCHANT_WEBSITE constant with details received from Paytm.
- Above details will be different for testing and production environment.
*/
$conf = [
    'env' => 'test',
];
$test = [
	//测试url
    'email' => [
        'transaction'		 => 'https://pguat.paytm.com/oltp-web/processTransaction',
        'transaction_status' => 'https://pguat.paytm.com/oltp/HANDLER_INTERNAL/getTxnStatus',
        'refund' 			 => 'https://pguat.paytm.com/oltp/HANDLER_INTERNAL/REFUND',
    ],
    'doc' => [
        'transaction' 		 => 'https://securegw-stage.paytm.in/theia/processTransaction',
        'transaction_status' => 'https://securegw-stage.paytm.in/merchant-status/getTxnStatus',
        'refund' 			 => 'https://securegw-stage.paytm.in/refund/HANDLER_INTERNAL/REFUND',
    ],
	
//测试参数
	'merchant_key' 	   => 'Mfpu1gmNDtn5t8Le',
	'mid' 			   => 'MSOHAN58655972036042',
    'industry_type_id' => 'Retail',
    'web'			   => [
        'website'    => 'WEBSTAGING',
        'channel_id' => 'WEB'
    ],
    'app' 			   => [
        'website'    => 'APPSTAGING',
        'channel_id' => 'WAP'
    ]
	
	
];
$product = [
	'email' => [
		//正式url
		'transaction' 		 => 'https://secure.paytm.in/oltp-web/processTransaction',
		'transaction_status' => 'https://secure.paytm.in/merchant-status/getTxnStatus',
		'refund' 			 => 'https://secure.paytm.in/refund/HANDLER_INTERNAL/REFUND'
    ],
    'doc' => [
        //正式url
        'transaction' 		 => 'https://securegw.paytm.in/theia/processTransaction',
        'transaction_status' => 'https://securegw.paytm.in/merchant-status/getTxnStatus',
        'refund' 			 => 'https://securegw.paytm.in/refund/HANDLER_INTERNAL/REFUND'
    ],
	//正式参数(老板的账号)
	'merchant_key' 			 => '2wP3Qf@j7pg90GrG',
	'mid' 				     => 'MSOHAN97055260182703',
	'industry_type_id' 		 => 'Retail92',
	'web' => [
		'website'    => 'WEBPROD',
		'channel_id' => 'WEB'
	],
	'app' => [
		'website'    => 'APPPROD',
		'channel_id' => 'WAP'
	]
	
];


//7777的账户
//$mid = 'Jgnn40854458553994';
/*//文档上面的测试url
$transaction = 'https://securegw-stage.paytm.in/merchant-status/getTxnStatus';
$status = 'https://securegw-stage.paytm.in/theia/processTransaction';*/

if ($conf['env'] == 'test') {
    $conf = $test;
} else {
    $conf = $product;
}
define('conf',$conf)



?>
