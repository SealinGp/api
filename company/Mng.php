<?php
namespace API\company;
use API\ext\xml;


class Mng
{
    //此api需要Sea xml的拓展, 另外,测试必选参数OrderNum 的值唯一,重复提交会 下单失败(无论什么环境),因此,请测试时多次提交 改变此值
    private static $_env = [
        'test' => [//测试环境
            'username' => '298488995',
            'password' => 'TEEYGJOI',
            'create' => 'http://service.mngkargo.com.tr/musterikargosiparis/musterikargosiparis.asmx/SiparisGirisiDetayliV3',//订单创建
            'track' => 'http://service.mngkargo.com.tr/musterikargosiparis/musterikargosiparis.asmx/FaturaSiparisListesi'    //订单查询
        ],
        'product' => [//正式环境
            'username' => '',
            'password' => '',
            'create' => 'http://service.mngkargo.com.tr/musterikargosiparis/musterikargosiparis.asmx/SiparisGirisiDetayliV3',
            'track' => 'http://service.mngkargo.com.tr/musterikargosiparis/musterikargosiparis.asmx/FaturaSiparisListesi'
        ]
    ];
 
    //mng 创建订单
    /**@param array $reqParam 必选参数,
     * @param array $serParam 必选规格参数,重量,体积,名字,数量(因必选参数中有需要提前通过这些参数计算而得的参数,故分开)
     * @param array $optParam 可选参数
     * @param bool $env 环境 测试(false) | 正式(true)
     * @return string $result,返回格式见文件底部
     * */
    public function orderCreate(array $param, bool $env = false)
    {
        //获取请求参数
        $env = !$env ? 'test' : 'product';
        $requestData = [
//必填参数
            'pKullaniciAdi' => self::$_env[$env]['username'],
            'pSifre' => self::$_env[$env]['password'],
            'pChSiparisNo' => $param['order_no'],                   //订单编号,用于追踪订单的状态信息(数字+字母+特殊字符<=20个字符)
            'pGonderiHizmetSekli' => 'NORMAL',                      //服务类型,NORMAL(默认) GUNCI(?) AKSAM_TESLIMAT(?) ONCELIKI(特快)
            'pTeslimSekli' => (int)1,                               //到货通知 1:地址交付(送货上门) 2:收件通知 3:电话通知
            'pFlAlSms' => (int)1,                                   //到货时短信通知, 1:通知 0:不通知
            'pFlGnSms' => '1',                                      //到货时短信通知, 1:通知 0:不通知
            'pLuOdemeSekli' => 'P',                                 //P: 寄件人付 U: 收件人付 ?
            'pFlKapidaOdeme' => '',                                 //是否货到付款 0(否) 1(货到付款时prKiymet必填)
            'pMalBedeliOdemeSekli' => '',                           //COD付款方式 NAKIT:现金 KREDI_KARTI:信用卡
            //收件人信息
            'pAliciMusteriAdi' => $param['recipientName'],          //名字
            'pFlAdresFarkli' => '0',                                //地址是否在MNG系统中注册,0:没有 1:有
            'pChAdres' => $param['recipientStreet'] .               //地址
                $param['recipientStreetNo'],
            'pChIlce' => $param['recipientProvince'],               //地区
            'pChIl' => $param['recipientCity'],                     //城市
            'pKargoParcaList' => [                                  //箱子规格
                [
                    'weight' => '',
                    'volum'  => '',
                    'name' => '',
                    'num' => '',
                ]
            ],
//可选参数
            'pPlatformKisaAdi' => '',                               //平台通知类型,可选N11 | GG
            'pPlatformSatisKodu' => '',                             //属于平台的应用销售/广告系列 代码
            'pAliciMusteriMngNo' => '',                             //客户号
            'pPrKiymet' => '',                                      //货物价值(货到付款)?
            'receiverCustNum' => '',                                //收件人客户号(MNG系统中有)
            'pAliciMusteriBayiNo' => '',                            //收件人经销商(代理商)编号,经销商需要在MNG系统中用EXCEL注册
            'pChIrsaliyeNo' => '',                                  //商业包裹发票号码|合法文档号?
            'pChBarkod' => '',                                      //包裹标签的条形码值(建议使用订单号码)
            'pChIcerik' => '',                                      //包裹描述(不超过200个字符)
            //收件人地址细节
            'pChSemt' => '',                                        //附近
            'pChMahalle' => '',                                     //季?
            'pChMeydanBulvar' => '',                                //广场
            'pChCadde' => '',                                       //街道
            'pChSokak' => '',                                       //街道
            'pChTelEv' => $param['recipientTel'],                   //地区号+家庭号码(不带0)
            'pChTelCep' => '',                                      //地区号+手机号码(不带0)
            'pChTelIs' => '',                                       //地区号+商业号码(不带0)
            'pChFax' => '',                                         //地区号码+传真号码(不带0)
            'pChEmail' => $param['recipientEmail'],                 //收件人邮箱
            'pChVergiDairesi' => '',                                //收件人税务局的名称
            'pChVergiNumarasi' => $param['recipientTaxNo'],         //收件人税收号

        ];
        //箱子规格
        foreach ($param['declareItems'] as $k => $v) {
            $requestData['pKargoParcaList'][$k]['weight'] = (float)$v['declareWeight'];
            $requestData['pKargoParcaList'][$k]['volum'] = (float)$param['length'] *
                (float)$param['width'] * (float)$param['height'];
            $requestData['pKargoParcaList'][$k]['name'] = $v['declareEnName'];
            $requestData['pKargoParcaList'][$k]['num'] = $v['declarePieces'];
        }
        /*箱子规格格式: 重量 : v : x : 名称 : 数量 : ;
          v = (volum/3000);  x = 重量>volum1 ? 重量 : volum1; 四舍五入*/
        $requestData['pKargoParcaList'] = self::caculateSpecification($requestData['pKargoParcaList']);

        //COD订单
        if ('D' == $param['payType']) {
            $requestData['pLuOdemeSekli'] = 'U';
            $requestData['pFlKapidaOdeme'] = 1;
            $requestData['pMalBedeliOdemeSekli'] = 'NAKIT';         //付款方式 NAKIT:现金 KREDI_KARTI:信用卡
            $requestData['pPrKiymet'] = $param['salesMoney'];       //到付金额
        }

        //开始请求
        $result = self::curl(self::$_env[$env]['create'], http_build_query($requestData,'','&'));

        //请求结果解析
        $data = [
            'xmlStr' => $result['data'],
            'tagName' => 'string',
        ];
        $data = xml::hook('getValByTagName',$data);
        $data = explode(':',$data[0]);

        //失败
        if (!$data[0]  || !in_array($data[0],['E001','1']) ) {
            /* $requestData = json_encode($requestData,JSON_UNESCAPED_UNICODE);
              $responseData = json_encode($result['data'],JSON_UNESCAPED_UNICODE);
              LogServer::error('1','Mng',$requestData,$responseData);*/
            $result = [
                'status' => 'failed',
                'msg' =>  !in_array($data[0],array_keys(self::$errCode)) ?
                    implode(':',$data) :  self::$errCode[$data[0]]
            ];
            return $result;
        }

        //成功
        /* $requestData = json_encode($requestData,JSON_UNESCAPED_UNICODE);
            $responseData = json_encode($result['data'],JSON_UNESCAPED_UNICODE);
            LogServer::success('1','Mng',$requestData,$responseData);*/
        $result = [
            'status' => 'success',
            'trackingNo' => $param['order_no'],
        ];
        return $result;
    }

    //mng查询物流订单
    /**@param array $orderId 查单需要参数
     *@param bool $env
     * @return  string $result,返回格式见文件底部
     * */
    public function orderQuery(string $orderId, bool $env = false)
    {
        $env = !$env ? 'test': 'product';
        $requestData = [
            'pSiparisNo' => strtoupper($orderId),                       //ALJTR1802280006956 ,TR2018060509580196
            'pKullaniciAdi' => self::$_env[$env]['username'],           //298488995
            'pSifre' => self::$_env[$env]['password'],                  //TEEYGJOI
        ];
        foreach ($requestData as $k1 => $v1) {
            $requestData[$k1] = $k1.'='.$v1;
        }
        $requestData = implode('&', $requestData);

        try {
            //开始请求
            $result = self::curl(self::$_env[$env]['track'], $requestData);
            //结果解析
            $param  = [
                'xmlStr' => $result['data'],
                'tagName' => 'SIPARIS_NO',
            ];
            //处理接收的数据
            $tagVal = xml::hook('getValByTagName', $param);
    
            //失败(没找到对应的单号)
            if (!$tagVal[0]) {
                /* $requestData = json_encode($requestData,JSON_UNESCAPED_UNICODE);
                $responseData = json_encode($result['data'],JSON_UNESCAPED_UNICODE);
                LogServer::error('2','Mng',$requestData,$responseData);*/
                $result = [
                    'status' => 'failed',
                    'msg' => 'track number no found'
                ];
                return $result;
            }
            //成功
            //维护返回轨迹格式
            $tagName = [
                'KARGO_STATU' => [                                  //货物状态数字代码 0~7
                    //未交货
                    '0' => 'No process done yet',                   //还未完成交易
                    '1' => 'Order is Given to Cargo',               //订单已取件
                    '2' => 'In Transit',                            //转运中
                    '3' => 'Shipment is Reached to Delivery Unit',  //货物已达到交货单位
                    '4' => 'Shipment Directed to Delivery Address', //货物将送至送货地址
                    //已交货
                    '5' => 'Delivered',
                    '7' => 'Delivered to Sender'
                ],
                'KARGO_STATU_ACIKLAMA' => '',                       //货物状态土耳其语代码
                'GONDERI_CIKIS_TARIHI' => '',                       //出发日期
                'TESLIM_TARIHI' => '',                              //交货日期
                'TESLIM_SAATI' => '',                               //交货时间
                'TESLIM_ALAN_AD' => '',                             //交付名称
            ];
            $cargoStatus = [
                //未交货
                '0' => 'No process done yet',       //还未完成交易
                '1' => 'Order is Given to Cargo',   //订单已取件
                '2' => 'In Transit',                //转运中
                '3' => 'Shipment is Reached to Delivery Unit',//货物已达到交货单位
                '4' => 'Shipment Directed to Delivery Address',//货物将送至送货地址
                //已交货
                '5' => 'Delivered',
                '7' => 'Delivered to Sender'
            ];
            foreach ($tagName as $k1 => $v1) {
                $param['tagName'] = $k1;
                $tagVal = xml::hook('getValByTagName', $param);
                if ('KARGO_STATU' == $k1) {
                    isset($tagName[$k1][$tagVal[0]]) && $tagName[$k1] = $tagName[$k1][$tagVal[0]];
                } else {
                    $tagName[$k1] = $tagVal[0];
                }
            }
            var_dump($tagName);exit;
            $match = array();
            //根据货物状态数字代码(KARGO_STATU) 判断货物运输状态
           if( '6' == $tagName['KARGO_STATU'] ) {
                $partten = "/[\d]{2}/";
                preg_match($partten, $tagName['KARGO_STATU_ACIKLAMA'], $match);
                
                $a = [
                    '01' => 'Note is left',
                    '02' => 'Insufficient Address',
                    '03' => 'Consignee is moved',
                    '04' => 'Address Unknown',
                    '05' => '',
                    '06' => '',
                    '07' => '',
                    '08' => '',
                    '09' => '',
                    '10' => '',
                    '11' => '',
                    '12' => '',
                    '13' => '',
                    '14' => '',
                ];
                switch ($match[0]) {
                    case '02':
                        $tagName['KARGO_STATU_ACIKLAMA'] = 'Insufficient Address';
                        break;
                    case '03':
                        $tagName['KARGO_STATU_ACIKLAMA'] = 'Consignee is moved';
                        break;
                    case '04':
                        $tagName['KARGO_STATU_ACIKLAMA'] = 'Address Unknown';
                        break;
                    case '05':
                        $tagName['KARGO_STATU_ACIKLAMA'] = 'Customer Notified Delivery';
                        break;
                    case '06':
                        $tagName['KARGO_STATU_ACIKLAMA'] = 'Business is closed';
                        break;
                    case '07':
                        $tagName['KARGO_STATU_ACIKLAMA'] = 'Business is not in progress';
                        break;
                    case '08':
                        $tagName['KARGO_STATU_ACIKLAMA'] = 'Consignee has quitted job';
                        break;
                    case '09':
                        $tagName['KARGO_STATU_ACIKLAMA'] = 'Consignee is on leave';
                        break;
                    case '10':
                        $tagName['KARGO_STATU_ACIKLAMA'] = 'Consignee is not in place';
                        break;
                    case '11':
                        $tagName['KARGO_STATU_ACIKLAMA'] = 'Rejected because of fee';
                        break;
                    case '12':
                        $tagName['KARGO_STATU_ACIKLAMA'] = 'Consignee did not want to pay for COD';
                        break;
                    case '13':
                        $tagName['KARGO_STATU_ACIKLAMA'] = 'Did not accept return';
                        break;
                    case '14':
                        $tagName['KARGO_STATU_ACIKLAMA'] = 'Damages';
                        break;
                    case '01':
                        $tagName['KARGO_STATU_ACIKLAMA'] = 'Note is left';
                        break;
                    default:
                        $tagName['KARGO_STATU_ACIKLAMA'] = 'Missing shipment pieces';
                }
            } else {
                $tagName['KARGO_STATU_ACIKLAMA'] = 'not process yet';
            }
            /* $requestData = json_encode($requestData,JSON_UNESCAPED_UNICODE);
               $responseData = json_encode($result['data'],JSON_UNESCAPED_UNICODE);
               LogServer::success('2','Mng',$requestData,$responseData);*/
            $trackData = [
                $orderId => [
                    [
                        'time' => $tagName['GONDERI_CIKIS_TARIHI'],
                        'description' => ''
                    ]
                ]
            ];
            $result = [
                'status' => 'success',
                'data' => $trackData
            ];
        } catch (\Throwable $e) {
            $result = [
                'status' => 'failed',
                'data' => $e->getMessage(),
            ];
        }
        return $result;
    }


    private static function curl(string $url, string $param)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $param,
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Content-Type: application/x-www-form-urlencoded",
            ),
        ));
        $result = [];
        $data = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $result = [
            'status' => $err ? 'failed' : 'success',
            'data' => $err ? : $data,
        ];
        return $result;
    }
    /**按照mng文档规则 处理计算规格参数
     * @param array $serParam 规格参数
     * **/
    private static function caculateSpecification(array $serParam):string
    {
        $serStr = '';
        foreach ($serParam as $v1) {
            $volum = round(($v1['volum']/3000));
            $chose = $volum > $v1['weight'] ? $volum : $v1['weight'];
            $serStr .= $v1['weight'].':'.$volum.':'.$chose.':'.$v1['name'].':'.$v1['num'].':;';
        }
        return $serStr;
    }

    private static $errCode = [
        'E002' => 'Error occurred during registration!',
        'E003' => 'User name or password incorrect check again.',
        'E004' => 'User name or password cannot be left blank.',
        'E005' => 'A REGISTRATION NUMBER FOR THIS ORDER ALREADY EXISTS!',
        'E006' => 'User name must indicated as numbers. Dispatched value.',
        'E007' => 'pChIl parameter cannot be blank or null.',
        'E008' => 'pChIlce parameter cannot be blank or null.',
        'E010' => 'pChAdres parameter cannot be blank or null.',
        'E011' => 'pAliciMusteriBayiNo parameter contains maximum 20 characters.',
        'E014' => 'pChSiparisNo value parameter cannot be blank or null.',
        'E015' => 'pPrKiymet Payment on Delivery parameters cannot have a null or 0 (sıfır) value. Dispatch value: pPrKiymet,
        pPrKiymet parameter value cannot be other than a decimal value. Dispatch value: pPrKiymet.',
        'E016' => 'pFlAlSms parameter cannot be allocated a value other than 1 or 0. Dispatch value: pFlAlSms.ToString().',
        'E017' => 'pFlGnSms parameter cannot be allocated a value other than 1 or 0.. Dispatch value: + pFlGnSms.ToString',
        'E018' => 'pLuOdemeSekli parameter cannot be allocated a value other than P or U. Dispatch value: pLuOdemeSekli',
        'E019' => 'pFlAdresFarkli cannot be allocated a value other than 1 or 0. Dispatch value: pFlAdresFarkli',
        'E020' => 'pKargoParcaList parameter error has been determined. Parameter format should be A1:B1:C1:D1:E1:;A2:B2:C2:D2:E2:;',
        'E021' => 'pChTelCep/pChTelEv/pChTelIs parameter error',
        'E022' => 'pChIcerik parameter can have maximum 200 characters',
        'E023' => 'Toplam KgDesi parameter can have maximum 500 characters',
        'E024' => 'pChSiparisNo parameter can have maximum 30 characters/
        pMalBedeliOdemeSekli parameter error! Expected value must be NAKIT or KREDI_KARTI',
        'E025' => 'pGonderiHizmetSekli parameter error! Expected value must be NORMAL , ONCELIKLI, GUNICI or AKSAM_TESLIMAT',
        'E026' => 'Recipient Customer Name is not Long Enough !/
        pPlatformKisaAdi parameter error! Expected value must be N11 or GG',
        'E027' => 'pPlatformSatisKodu must also include pPlatformKisaAdi parameter ! Expected value must be N11 or GG',
        'E028' => 'pPlatformKisaAdi must also include pPlatformSatisKodu parameter !',
    ];
}


