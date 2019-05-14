<?php
/**
 * Created by PhpStorm.
 * User: v_kenqzhang
 * Date: 2019/5/7
 * Time: 15:54
 * ref url:http://www.php-internals.com/book/?p=chapt05/05-01-class-struct
 */

/**
struct _zend_class_entry {
    char type;  //类型
    char *name; //类名
    zend_uint name_length; //sizeof(name)-1 类名长度
    int refcount; //引用数
    zend_bool constants_updated;
    zend_uint ce_flags; //ZEND_ACC_IMPLICIT_ABSTRACT_CLASS: 类存在abstract方法
    
    HashTable function_table; //方法
    HashTable default_properties;//默认属性
    HashTable *static_members; //type == ZEND_USER_CLASS时 取&default_static_members;
 * //type == ZEND_INSERT_CLASS 时,设为NULL
    HashTable constants_table;
   struct _zend_function_entry *builtin_funcitons;//方法定义入口
   
   union _zend_function *constructor;
   union _zend_function *destructor;
   union _zend_function *clone;
   
 * #魔术方法
 * union _zend_function *__get;
 * union _zend_function *__set;
 * union _zend_function *__unset;
 * union _zend_function *__isset;
 * union _zend_function *__call;
 * union _zend_function *__toString;
 * union _zend_function *serialize_func;
 * union _zend_function *unserialize_func;
 * zend_class_iterator_funcs iterator_funcs;//迭代
 *
 * #类句柄
 * zend_object_value (*create_object)(zend_class_entry *class_type TSRMLS_DC);
 * zend_object_iterator *(*get_iterator)(zend_class_entry *ce,zval *object,intby_ref TSRMLS_DC);
 *
 * #类声明的接口
 * int(*interface_gets_implemented)(zend_class_entry *iface,zend_class_entry *class_type TSRMLS_DC);
 *
 * #序列化回调函数指针
 * int(*serialize)(zval *object,unsignedchar**buffer,zend_uint *buf_len,zend_serialize_data *data TSRMLS_DC);
 * int(*unserialize)(zval **object,zend_class_entry *ce,constunsignedchar*buf,zend_uint *buf_len,zend_unserialize_data *data TSRMLS_DC);
 *
 * zend_class_entry **interfaces //类实现的接口
 * zend_uint num_interfaces;//类实现的接口数
 *
 * char *filename; //类的存放文件地址
 * zend_uint line_start;//类定义的开始行
 * zend_uint line_end;//类定义的结束行
 * char *doc_comment;
 * zend_uint doc_comment_len;
 *
 * struct _zend_module_entry *module;//类所在的模块入口: EG(current_module)
}
 *
 */

?>
