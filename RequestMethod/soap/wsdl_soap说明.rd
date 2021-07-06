Web Service实现业务诉求：WebService是真正“办事”的那个，提供一种办事接口的统称。
WSDL:web service Description Language 描述语言 基于 XML 的格式,(办事的文档说明)
SOAP:simple object access protocol 提供“请求”的规范,标准

WSDL HTTP SOAP

WSDL支持SOAP传输的文档规范
SOAP目前主要用来调用远程的过程和函数

types:声明schema和namespaces
Messages: 函数参数（输入与输出分开）或文档描述
PortTypes:　引用消息定义描述函数(操作名,输入参数,输出参数)
Bindings: PortTypes部分的每一操作在此绑定实现
Services: 确定每个绑定的端口地址



<?xml version="1.0" encoding="UTF-8"?> 
＜definitions name="FooSample"
　targetNamespace="http://tempuri.org/wsdl/"
　xmlns:wsdl='http://schemas.xmlsoap.org/wsdl/'
  xmlns:soap='http://schemas.xmlsoap.org/wsdl/soap/'
  xmlns:xsd='http://www.w3.org/2001/XMLSchema'
  xmlns:SOAP-ENC='http://schemas.xmlsoap.org/soap/encoding/'
  xmlns='http://schemas.xmlsoap.org/wsdl/'
/＞

＜message name="Simple.foo"＞:定义了那个函数的参数+数据类型
　＜part name="arg" type="xsd:int"/＞:参数名和参数类型,type=xsd:X or soap:X or wsdl:X
＜/message＞

＜portType name="SimplePortType"＞
　＜operation name="函数名" parameterOrder="输入参数" ＞
　　＜input message="wsdlns:Simple.foo"/＞
　　＜output message="wsdlns:Simple.fooResponse"/＞
　＜/operation＞
＜/portType＞


soap消息:
＜?xml version="1.0" encoding="UTF-8" standalone="no"?＞

＜SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" 
xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"＞ 

＜SOAP-ENV:Body＞

＜/SOAP-ENV:Body＞

＜/SOAP-ENV:Envelope＞

版本
xmlns:SOAP-ENC=http://schemas.xmlsoap.org/soap/encoding SOAP 1.1 encoding

xmlns:wsdl=http://schemas.xmlsoap.org/wsdl/soap WSDL 1.1

xmlns:xsd=http://www.w3.org/2001/XMLSchema XML Schema

complex类型:
C语言:
(声明结构体变量)  typedef:定义新的类型名来代替已有的类型名
将struct声明为新的结构体PERSON
typedef struct {
　string firstName;
　string lastName;
　long ageInYears;
　float weightInLbs;
　float heightInInches;
} PERSON;

等同于
＜xsd:complexType name="PERSON"＞
＜xsd:sequence＞
　＜xsd:element name="firstName" type="xsd:string"/＞
＜/xsd:sequence＞

＜xsd:all＞
　＜xsd:element name="firstName" type="xsd:string"/＞
＜/xsd:all＞
＜/xsd:complexType＞

//-----------------------------------------------------------------------------
WSDL的元素 

1.＜definitions attr=''＞ 
attr: name,targetNamespace,xmlns
子元素:＜types＞,＜message＞,＜portType＞,＜binding＞,＜service＞

2.＜types attr=''＞
attr: none
子元素:＜xsd:schema＞

3.＜message attr=''＞
attr:Name,
子元素:＜part＞

4.＜portType attr=''＞
attr:Name,
子元素:＜operation＞

5.＜binding attr=''＞
attr:name,type
子元素:＜operation＞

6.＜part attr=''＞
attr:name,type
子元素:none

7.＜operation attr=''＞
attr:name,parameterOrder
子元素:＜input＞,＜output＞,＜fault＞

8.＜input attr=''＞
attr:name,message
子元素:none

9.<output attr=''＞
attr:name,message
子元素:none

10.<fault attr=''＞
attr:name,message
子元素:none

11.<port attr=''＞
attr:name,message
子元素:＜soap:address＞





