<?php
namespace API\redis;
class redis
{
    /*使用说明:
     * redis 一共有16个 (0~15号) 库
     * redis命令大全 http://doc.redisfans.com/index.html (中文)
     * */
    //\Redis object
    private static $_redis;
    //error log
    public static  $_log;
    
    public function __construct(array $conf)
    {
        $this->connect($conf);
    }
    
    /**
     * 重置库
     */
    public function reset():void
    {
        self::dbName(0);
        if (self::$_redis) self::close();
    }
    
    // connect (port 6379)
    public function connect(array $conf):bool
    {
        foreach ($conf as $k => $v) {
            if (!in_array($k,['host','port','password'])) return false;
        }
        self::$_redis = new \Redis();
        $conn =  self::$_redis->connect($conf['host'], $conf['port']);
        $auth = $conf['password'] ? self::$_redis->auth($conf['password']) : 'no auth';
        $result = (!$conn || !$auth) ? false : true;
        unset($conn,$auth);
        return $result;
    }

    //test
    public function test(string $hashKey){
        return self::hmget($hashKey,[]);
        return method_exists(self::$_redis,'select');
    }

    /*command命令行操作---------------------------------------------------------------------------------------
     * */
    /**查看已存储的所有键
     * @return array
     */
    /**执行Redis命令,得到相应的结果$cmd 命令关键词,$words 参数
     * @param string $cmd 命令关键词 见@http://doc.redisfans.com/index.html
     * @param string $words
     * @return bool
     */
    public  function command_exec(string $cmd, string $words)
    {
        if (!self::$_redis) return false;
        return self::$_redis->rawCommand($cmd,$words);
    }

    /*server操作---------------------------------------------------------------------------------------
     * */
    /**
     * 清楚所有库里面的所有数据
     */
    public  function server_flushAll():void
    {
        if (!self::$_redis) return;
        self::$_redis->flushAll();
    }

    /*hash表操作
    * hash表 结构为  hashKey(表名) => field(字段名)=>value(字段值)
     * */
    
    /**set fields
     * @param string $hashKey
     * @param array $arr fields => value
     * @return bool
     */
    public  function hash_setFields(string $hashKey, array $arr):bool
    {
        if (!self::$_redis) return false;
        return self::$_redis->hmset($hashKey,$arr);
    }
    
    /**get all fields' name
     * @param string $hashKey
     * @return array
     */
    public  function hash_getAllFields(string $hashKey):array
    {
        if (!self::$_redis) return [];
        return self::$_redis->hkeys($hashKey);
    }
    
    /**get fields' values
     * @param string $hashKey
     * @param array $fields [field1,field2]
     * @return array
     */
    public  function hash_getValues(string $hashKey,array $fields):array
    {
        if (!self::$_redis) return [];
        return false === ( $fields = self::$_redis->hmget($hashKey,$fields) ) ? [] : $fields;
    }
    
    /**get field's value
     * @param string $hashKey
     * @param string $field
     * @return string
     */
    public  function hash_getValue(string $hashKey,string $field):string
    {
        if (!self::$_redis) return '';
        $result = false === ($hget = self::$_redis->hget($hashKey,$field)) ? '' : $hget;
        return $result;
    }

    /*key操作-------------------------------------------------------------------------------------------
     *key=>value
     * */
    /**get key type
     * @param string $key
     * @return string
     */
    public function key_getType(string $key):string
    {
        $type = ['not found','string','set','list','zset','hash'];
        if (!self::$_redis) return '';
        return isset($type[self::$_redis->type($key)]) ?
            $type[self::$_redis->type($key)] : '';
    }
    
    /**set key value (json encode)
     * @param string $key
     * @param array $value [value1,value2...] | [key=>value,...]
     * @param int $time time out (s)
     * @return bool
     */
    public  function key_set(string $key,array $value,int $time = 0):bool
    {
        if (!self::$_redis) return false;
        $value = json_encode($value);
        return self::$_redis->set($key, $value,$time);
    }
    
    /**get key value
     * @param string $key
     * @return array
     */
    public  function key_get(string $key):array
    {
        if (!self::$_redis) return [];
        return json_decode(self::$_redis->get($key),true) ? : [];
    }
    
    /**delete key
     * @param string $key
     * @return bool
     */
    public function key_del(string $key):bool
    {
        if (!self::$_redis) return [];
        return self::$_redis->del($key) ? false:true;
    }

    
   /* //记录错误信息
    public  function logRecord():void
    {
        if (!self::$_redis) return;
        self::$_log[date("Y-m-dH:i:s",time())] = self::$_redis->getLastError();
        
    }*/
    //关闭连接资源
    public  function close()
    {
        if (!self::$_redis) return [];
        self::$_redis->close();
    }
    //获取Redis类中封装的所有方法
    public  function getMethods():array
    {
        if (!self::$_redis) return [];
        return get_class_methods('Redis');
    }
    //获取当前的库索引(正在使用第index个库)
    public  function getDbName():int
    {
        if (!self::$_redis) return 0;
        return self::$_redis->getDBNum();
    }
    //设置切换到第index个库
    public  function setDbName(int $index):void
    {
        if (!self::$_redis ||  ( $index<0 && $index>15 )) return;
        self::$_redis->select($index);
    }
    //查看hashKey->对应的数组
    public  function hmgets(string $hashKey):array
    {
        if (!self::$_redis) return [];
        return  self::hmget($hashKey,self::allFields($hashKey));
    }
    public function getRedis():\Redis
    {
        return self::$_redis ? : new \Redis();
    }
}
