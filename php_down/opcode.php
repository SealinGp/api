<?php
/**
 * Created by PhpStorm.
 * User: sealingp@163.com
 * Date: 2018/12/12
 * Time: 19:09
 */
/**
 * opcode : 字节码,寄存器中的值,堆栈中的值,某块内存的值或者IO端口中的值等等,
 * 相当于
 *      java中的虚拟机(JVM)
 *      .NET中的通用中间语言
 *
 * 结构体:
 *      struct _zend_op {
            opcode_handler_t handler;       //执行该opcode时调用的处理函数
            znode result;
            znode op1;
            znode op2;
            ulong extended_value;
            uint lineno;
            zend_uchar opcode;  //opcode代码
 *      }
 *
*/
