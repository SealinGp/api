<?php
namespace API\ext;


//use FreeSSHd to build local sftp server
set_time_limit(0);
class sftp extends fileOperate
{
    //ssh2 resource
    private static $_resource;

    //sftp resource
    private static $_sftp;

    //remote dir
    private static $_remoteDir;

    //local dir
    private static $_localDir;

    //sftp url prefix
    private static $_sftpPathPrefix;
    /**
     * connect sftp
     * @param array $login   keys:
     * host
     * user
     * pass
     * port
     */
    public static function connect(array $login):bool
    {
        if (array_keys($login) != ['host','port','user','pass']) {
            return false;
        }
        if ( !(self::$_resource = @ssh2_connect($login['host'], $login['port']) ) ||
            !ssh2_auth_password(self::$_resource, $login['user'], $login['pass'])) {
            return false;
        }//init
        return (self::$_sftp = ssh2_sftp(self::$_resource)) &&
            self::$_sftpPathPrefix = 'ssh2.sftp://' . self::$_sftp;
    }

    //check connect
    private static function check():bool
    {
        return self::$_resource && self::$_sftp;
    }

    /**set remote/local dir suffix
     * mention : set $path = '' or $path = 'path/to/other' if change/remove dir suffix
     * mention : won't check the legal of path,only do when use
     *
     * @param string $path
     * @param string $type type of suffix, r:remote | l:local
     * */
    public static function setSuffixDir(string $path,string $type = 'r'):void
    {
        switch ($type) {
            case 'r':
                if ('' === $path ) {//reset suffix
                    self::$_remoteDir = null;
                    return;
                } //clean remote root dir  . ./  /. /
                parent::clean($path);
                '' === $path && $path = '.';
                $path = self::$_sftpPathPrefix .'/'. $path;
                parent::clean($path);
                self::$_remoteDir = $path;
                break;
            case 'l':
                if ('' === $path) {
                    self::$_localDir = null;
                    return;
                }
                $path = realpath($path);
                parent::clean($path);
                self::$_localDir = $path.'/';
                break;
        }
    }

    /**
     * down file (single)
     * @param string $remoteFile
     * @param string $localFile
     * @param string $mode d:download | u:upload
     * @return bool
     */
    public static function down(string $remoteFile,string $localFile,string $mode = 'd'):bool
    {
        if (!self::check() || !in_array($mode,['d','u'])) return false;
        parent::clean($remoteFile);
        parent::clean($localFile);
        $remoteFile = self::$_remoteDir ? self::$_remoteDir.'/'.$remoteFile :  self::$_sftpPathPrefix.'/'. $remoteFile;
        $localFile  = self::$_localDir ? self::$_localDir.'/'.$localFile  : $localFile;
        if ($mode === 'd') {
            $left  = &$remoteFile;
            $right = &$localFile;
        } else {
            $left  = &$localFile;
            $right = &$remoteFile;
        }
        return is_file($left) && @copy($left, $right);
    }

    //upload file (single)
    public static function upload(string $remoteFile,string $localFile):bool
    {
        return self::down($remoteFile,$localFile,'u');
    }

    /** down all files from sftp,
     * mandatory : set remote dir suffix first
     * mention : skip file existed in local and not count it
     *
     * @param string  $desDir file destination dir(save file downloaded)
     * @param string $type d:download | u:upload
     * @return int $num down file's number
     */
    public static function downAll(string $desDir,string $type = 'd'):int
    {
        //check
        $ifDown = 'd' === $type;
        if ( !self::check() ||
            !in_array($type,['d','u']) ||
            !($ifDown ? self::$_remoteDir : self::$_localDir)) {
            return 0;
        }
        //list files
        parent::clean($desDir);
        $desDir = $desDir.'/';
        $remote = self::listFile($ifDown ? '' : $desDir ,'r');
        $local  = self::listFile($ifDown ? $desDir : '','l');
        //complement of (left-right)
        $ifDown ?
            $mid = array_diff($remote,$local):
            $mid = array_diff($local,$remote);
        //start down
        $num = 0;
        foreach ($mid as $v) {
            if ($ifDown) {
                $left  = $v;
                $right = $desDir . $v;
            } else {
                $left  = $desDir . $v;
                $right = $v;
            }
            self::down($left, $right, $type) && $num += 1;
        }
        return $num;
    }

    /**upload all files to sftp
     * mandatory : set local dir suffix first
     * mention : skip file existed in remote and not count it
     *
     * @param string  $uploadDir remote dir
     */
    public static function uploadAll(string $remoteDir):int
    {
        return self::downAll($remoteDir,'u');
    }

    //get current remote location path
    public static function getCuRemotePath()
    {
        return strtr(self::$_remoteDir,[self::$_sftpPathPrefix => '']);
    }
    //get current local location path
    public static function getCuLocalPath()
    {
        return realpath(self::$_localDir);
    }
    /**
     * list all the files from $path
     * @param string $path  remote/local dir path
     * @param string $type  r:remote / l:local
     * @param bool $showDir  show dir(true) or file(false)
     * @param bool $addpath add path (true) or do not add path
     * @param int $time in show file situation if sort by alter time
     * @return array
     */
    public static function listFile(string $path,string $type = 'r', bool $showDir = false, bool $addpath = false,string $time = ''):array
    {
        //check
        parent::clean($path);
        switch ($type) {
            case 'r':
                if (!self::check()) return [];
                $path = self::$_remoteDir ?
                    self::$_remoteDir.'/'.$path  :
                    self::$_sftpPathPrefix. '/'. ((''===$path || '.'===$path) ? '.':$path) ;
                break;
            case 'l':
                $path = self::$_localDir ? (self::$_localDir .'/'.$path) : realpath($path);
                break;
            default:
                return [];
                break;
        }
        parent::clean($path);
        $path = $path .'/';
        if (!is_dir($path)) {
            return [];
        }
        //save dir and file and file's alter time
        $arr = [
            'dir' => [],
            'file'=> [],
        ];
        $filetime = [];
        //start read
        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file === "." || $file === "..") continue;
                $file = $path.$file;
                if (is_file($file) ) {//文件
                    $filetime[] = date("Y-m-d H:i:s", filemtime($file));
                    if ($type === 'r') {//是否显示完整路径(远程|本地)
                        $file = strtr($file,[self::$_sftpPathPrefix.'/' => '']);
                        $file = !$addpath ? strtr($file,[strtr($path,[self::$_sftpPathPrefix.'/'  => ''])=>'']) : $file;
                    } elseif ($type === 'l') {
                        $file = !$addpath ? strtr($file,[$path => '']): $file;
                    }
                    $arr['file'][] = $file;
                } elseif (is_dir($file)){//目录
                    if ($type === 'r') {//是否显示完整路径(远程|本地)
                        $file = strtr($file,[self::$_sftpPathPrefix.'/' => '']);
                        $file = !$addpath ? strtr($file,[strtr($path,[self::$_sftpPathPrefix.'/'  => ''])=>'']) : $file;
                    } elseif ($type === 'l') {
                        $file = !$addpath ? strtr($file,[$path => '']): $file;
                    }
                    $arr['dir'][] = $file;
                }
            }
            closedir($handle);
        }
        ('alterTime' === $time) && array_multisort($filetime, SORT_ASC, SORT_STRING, $arr['file']);
        return $showDir ? $arr['dir'] : $arr['file'];
    }


    //quick sort
    public static function myQsort(array $arr):array
    {
        $len = count($arr);
        if ($len <= 1) {
            return $arr;
        }
        $learr = [];
        $riarr = [];
        $first = $arr[0];
        for ($i = 1;$i < $len;$i++) {
            $arr[$i] <= $first ?
                $learr[] = $arr[$i]:
                $riarr[] = $arr[$i];
        }
        return  array_merge(self::myQsort($learr), [$first], self::myQsort($riarr));
    }

    /**
     * make dir in sftp
     * @param string $dirname
     * @return bool
     */
    public static function makeDir($dirname):bool
    {
        if (!self::check()) return false;
        parent::clean($dirname);
        $dirname = self::$_remoteDir ? strtr(self::$_remoteDir,[self::$_sftpPathPrefix => '']).'/'.$dirname : $dirname;
        return @ssh2_sftp_mkdir(self::$_sftp, $dirname);
    }


    /**把远程文件 数组按照时间排序(根据eBay文件名规则定制)
     *@param array $arr 远程文件数值键 数组列表
     *@return array $time_arr 远程文件时间排序数值键数组 0=>XXXX.zip (旧->新  0->length)
     * */
    public static function remoteDateSort(array $arr)
    {
        $date = date("Y-", time());
        //时间数值键 数组 0 => 2017xxx
        $date_arr=[];
        //时间关联键数组   2017xxx=>远程文件名
        $date_file = [];
        //对数组$arr中的值进行处理
        foreach ($arr as $k1 => $v1) {
            $str = str_replace('.zip', '', strstr($v1, $date, false));
            $str1 =  str_replace('-', '', substr($str, 0, -9));
            $date_file[$str1] = $v1;
            $date_arr[$k1] = $str1;
        }
        $time_arr = self::myQsort($date_arr);
        foreach ($time_arr as $k2 => $v2) {
            $time_arr[$k2] = $date_file[$v2];
        }
        unset($arr, $date_arr, $date_file);
        return $time_arr;
    }
}
