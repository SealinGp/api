<?php
namespace PhpOffice;

//use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class  MyExcelReader implements IReadFilter
{
    private $startRow = 0;
    private $endRow   = 0;
    private $columns  = array();

    /**  Get the list of rows and columns to read  */
    public function __construct($startRow, $endRow, $columns) {
        $this->startRow = $startRow;
        $this->endRow   = $endRow;
        $this->columns  = $columns;
    }

    public function readCell($column, $row, $worksheetName = '') {
        //  Only read the rows and columns that were configured
        if ($row >= $this->startRow && $row <= $this->endRow) {
            if (in_array($column,$this->columns)) {
                return true;
            }
        }
        return false;
    }
//you should write like this if you read excel file only
//$ExcelName = __DIR__.'/test.xlsx';
//你想要读取的内容的范围
//$myExcelReader = new MyExcelReader($startX,$endX,$YRange);
//识别excel版本
//$excelType = IOFactory::identify($ExcelName);
//$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($excelType);
//设置只读数据,否则需要很大内存去打开excel,打开不了
//$reader->setReadDataOnly(true);
//设置自定义的读取方式函数
//$reader->setReadFilter($myExcelReader);
//加载文件并输出为数组
//$sheetData = $reader->load($ExcelName)->getSheetByName($SheetName)->toArray(null, false, false, true);
//dump($sheetData);

}