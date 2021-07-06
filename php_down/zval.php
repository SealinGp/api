<?php
header('content-type:application/json;');

/*
 zend 是php底层引擎(车子的发动机)
 zend引擎：Zend整体用纯C实现，是PHP的内核部分，它将PHP代码翻译（词法,语法解析等一系列编译过程）
 为可执行opcode(命令)的处理并实现相应的处理方法,实现了基本的数据结构（如hashtable,oo）,内存分配及管理,
 提供了相应的api方法供外部调用，是一切的核心，所有的外围功能均围绕Zend实现.

 Zval 是zend中的数据结构

 Zval 组成部分:
    type: 指定变量类型 (int->long,string,array)
    refcount&is_ref: 实现计数的功能
    value: 存储了变量的值

      typedef union _zvalue_value {
                long lval;          #long value == lvalue
                double lval;        #double value == dvalue
                struct {            #string value == str
                    char *val;
                    int len;
                } str;
                HashTable *ht;      #hash table == ht
        }

php变量的'写时复制'(=) 和 '引用赋值'(=&) 通过zval中的is_ref和ref_count实现

'变量赋值'流程:  1.zend将 变量 -> zval 2.ref_count++  (当unset时,ref_count--)
$a = 2;

'写时复制'流程:  1.zend发现 zval 被多个 变量 共享,复制一份 ref_cont=1 的zval, ref_count--
$b = $a;

 String 拼接
$str1 = '1'; $str2 = '2';

    $str = $str1.$str2; $str = "$str1$str2";    #zend会重新 malloc 一块内存进行处理
    $str1 = $str1.$str2;                        #zend会在当前$str1的基础上直接relloc,速度最快,避免重复拷贝
    
    malloc: memory allocation 动态内存分配,申请一块连续的指定大小的内存块区域以 void* 类型返回分配的内存区域地址
            使用情景: 想要绑定真正的内存空间,无法知道内存的具体位置时.
    void*: 未确定类型的指针,它可以转换为其它任何类型的指针.
    
    relloc: 修改一个原先已经分配的内存块的大小,可以使一块内存的扩大或缩小

 Array的foreach操作
    array 通过Zend hash table来实现,一个数组的foreach就是通过遍历hashtable中的双向链表完成.对于索引数组,
    通过foreach遍历效率比for高很多
 
*/