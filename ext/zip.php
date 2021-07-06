<?php
namespace API\ext;

class zip
{
    //\ZipArchive object
    private $zip;
    
    //zip file real path
    private $file = '';
    
    public function __construct(string $file = '')
    {
        $this->zip = new \ZipArchive();
        $this->set_file($file);
    }
        
    /**get the extension of file
     *
     * @param string $file  file path
     * @param bool   $check if check real
     * @return string
     */
    public function ext(string $file, bool $check = false):string
    {
        $file = (
            $check && false === realpath($file)
        ) ? '' : $file;
        $file = pathinfo($file,PATHINFO_EXTENSION);
        
        unset($check);
        return $file;
    }
    
    
    /**
     * clean head '/' and foot '/',Win:change '\' to '/'
     *
     * @param string $path
     * @return void
     */
    protected function clean(string &$path):void
    {
        $path = strtr($path,['\\'=>'/']);
        
        stripos($path,'/') === 0 && $path = substr($path,1);
        
        strrpos($path,'/') == strlen($path) - 1 &&
        $path = substr_replace($path,'',strlen($path)-1,strlen($path)-1);
    }
    
    /**set zip file path
     *
     * @param string $file
     * @return zip
     */
    public function set_file(string $file):zip
    {
        $file = 'zip'  == $this->ext($file) ?
            $file : '';
        $this->clean($file);
        $this->file = $file;
        unset($file);
        return $this;
    }
    
    /**get current zip file path
     *
     * @return string
     */
    public function get_file():string
    {
        return $this->file;
    }

    /**
     * decompress files(default all) from zip (default overwrite same file name)
     *
     * @param string $des   destination DIR path
     * @param array  $files chose the file name from zip
     */
    public function decompress(string $des, array $files = []):bool
    {
        $this->zip->open($this->file);
        $return = $this->zip->extractTo($des, $files ? : null);
        $this->zip->close();
        
        unset($des,$files);
        return $return;
    }

    /**
     * compress file to zip(overwrite if file existed in zip)
     *
     * @param string $file file path + name which need compressed
     * @param string $rename file name (without path)
     * @return  bool
     */
    public function compress(string $file, string $rename = ''):bool
    {
        false !== ($file = @realpath($file)) &&
        $this->clean($file);
        
        $rename = $rename ? : pathinfo($file,PATHINFO_BASENAME);
        $this->zip->open($this->file,\ZipArchive::CREATE);
        $return = $this->zip->addFile($file,$rename);
        $this->zip->close();
        
        unset($file,$rename);
        return $return;
    }
    
    /**
     * read all files from zip
     *
     * @return array
     */
    public function read():array
    {
        $res   = zip_open($this->file);
        $files = [];
        while (false !== $read = zip_read($res)) {
            $files[] = zip_entry_name($read);
        }
        zip_close($res);
        
        unset($res);
        return $files;
    }
    
    /**
     * get ZipArchive object
     *
     * @return \ZipArchive
     */
    public function get_archive():\ZipArchive
    {
        return $this->zip;
    }
}
