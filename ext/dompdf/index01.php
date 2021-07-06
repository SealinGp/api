<?php
include 'autoload.php';
//pdflib
function buildPdf (string $html,string $pdfFile = null) {
    //Renderer
    $dompdf = new \Dompdf\Dompdf();
    
    $dompdf->loadHtml($html);

    
    //设置纸张规格
    $dompdf->setPaper('A6','portrait');
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A6','portrait');
    
    //html转换成pdf
    $dompdf->render();
    
    //下载pdf
    $dompdf->stream($pdfFile);
    /*$output = $dompdf->output();
    file_put_contents($pdfFile,$output);*/
}

$info = [
    'shipper' => 'ALLJOY SUPPLY CHAIN (HONG KONG) CO.,LIMITED',
    'consignee' => 'MIHURISHA GLOBAL MARKETING PRIVATE LIMITED'.
        'T-2/162, MANGOLPURI INDUSTRIAL AREA PHASE - 1, NEW DELHI,',
    'gst' => '07AALCM8714F1ZA',
    'iec' => 'AALCM8714F',
    'noOfPkg' => '01',
    'weight' => '',
    'desc' => 'MIX GOODS AS PER INV',
    'package' => '1/1',
    'remark' => 'beizhu',
];

$str = '<body style="            
            margin-top: 0;
            height: 100px;
            ">      
        <img style=" width: 100%;height: 100px;"  src="1.png">
        <div style="margin-top:5px; font-weight: bold;">SHIPPER:</div>
        <div style="margin-top:5px;">'.$info['shipper'].'</div>
        <div style="margin-top:5px; font-weight: bold;">CONSIGNEE:</div>
        <div style="margin-top:5px; ">'.$info['consignee'].'</div>
        <div style="margin-top:5px; font-weight: bold;">GST: <span style="font-weight: lighter;">'.$info['gst'].'</span> </div>
        <div style="margin-top:5px; font-weight: bold;">IEC: <span style="font-weight: lighter;">'.$info['iec'].'</span></div>
        <div style="margin-top:5px; font-weight: bold;">NO OF PKG:<span style="font-weight: lighter;">'.$info['noOfPkg'].'</span></div>
        <div style="margin-top:5px; font-weight: bold;">WEIGHT:<span style="font-weight: lighter;">'.$info['weight'].'</span></div>
        <div style="margin-top:5px; font-weight: bold;">DESC:<span style="font-weight: lighter;">'.$info['desc'].'</span></div>        
        <div style="margin-top:5px; font-weight: bold;">PACKAGE:<span style="font-weight: lighter;">'.'1/1'.'</span></div>
        <div style="margin-top:5px; font-weight: bold;">REMARK:<span style="font-weight: lighter;">'.'2222'.'</span></div>
</body>';

$html = <<<html
$str
html;

buildPdf($html,'a.pdf');



