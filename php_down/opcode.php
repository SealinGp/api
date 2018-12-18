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
            znode result;                   //保存该指令,执行完毕后的结果
            znode op1;
            znode op2;
            ulong extended_value;
            uint lineno;
            zend_uchar opcode;  //opcode代码
 *      }
 *
 * 例子
 * ej:
 * print 函数/语句 有设置返回值相关信息result
        void zend_do_print(znode *result，const znode *arg TSRMLS_DC)
        {
            zend_op *opline = get_next_op(CG(active_op_array) TSRMLS_CC);   //创建一个实例zend_op
            
            opline->result.op_type = IS_TMP_VAR;                            //返回值类型设置为临时变量
            opline->result.u.var = get_temporary_variable(CG(active_op_array));
            opline->opcode = ZEND_PRINT;
            opline->op1 = *arg;
            SET_UNUSED(opline->op2);
             *result = opline->result;
        }
 *
 * echo 语句 没有设置返回值相关信息result
        void zend_do_echo(const znode *arg TSRMLS_DC)
        {
            zend_op *opline = get_next_op(CG(active_op_array) TSRMLS_CC);
            
            opline->opcode = ZEND_ECHO;
            opline->op1 = *arg;                                             //输入参数
            SET_UNUSED(opline->op2);
        }
 *
*/
