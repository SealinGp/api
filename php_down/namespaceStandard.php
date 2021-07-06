<?php
/**
 * Created by PhpStorm.
 * User: 46448
 * Date: 2018/5/9
 * Time: 10:22
 * namspace standard
 */
/* PSR0标准:
一个完全标准的命名空间(namespace)和类(class)的结构是这样的：\<Vendor Name>\(<Namespace>\)*<Class Name>
每个命名空间(namespace)都必须有一个顶级的空间名(namespace)("组织名(Vendor Name)")。
每个命名空间(namespace)中可以根据需要使用任意数量的子命名空间(sub-namespace)。
从文件系统中加载源文件时，空间名(namespace)中的分隔符将被转换为 DIRECTORY_SEPARATOR。
类名(class name)中的每个下划线_都将被转换为一个DIRECTORY_SEPARATOR。下划线_在空间名(namespace)中没有什么特殊的意义。
完全标准的命名空间(namespace)和类(class)从文件系统加载源文件时将会加上.php后缀。
组织名(vendor name)，空间名(namespace)，类名(class name)都由大小写字母组合而成。

ej1:
dir:path

namespace  a;
class b {}
\path\a\b.php

ej2:
namespace a;
class b_c {}
\path\a\b\c.php

ej3:
namespace a_b;
class b {}
\path\a_b\c.php


PSR4:
完全限定类名应该类似如下范例：

<NamespaceName>(<SubNamespaceNames>)*<ClassName>

完全限定类名必须有一个顶级命名空间（Vendor Name）；
完全限定类名可以有多个子命名空间；
完全限定类名应该有一个终止类名；
下划线在完全限定类名中是没有特殊含义的；
字母在完全限定类名中可以是任何大小写的组合；
所有类名必须以大小写敏感的方式引用；
当从完全限定类名载入文件时：

在完全限定类名中，连续的一个或几个子命名空间构成的命名空间前缀（不包括顶级命名空间的分隔符），至少对应着至少一个基础目录。
在「命名空间前缀」后的连续子命名空间名称对应一个「基础目录」下的子目录，其中的命名 空间分隔符表示目录分隔符。子目录名称必须和子命名空间名大小写匹配；
终止类名对应一个以 .php 结尾的文件。文件名必须和终止类名大小写匹配；
自动载入器的实现不可抛出任何异常，不可引发任何等级的错误；也不应返回值
ej:
base directory  |   namespace prefix    |   fully qualified class name   =>   return path
\a\b        |       \e\f          |       \e\f\g                     => \a\b\g.php


 *
 * */