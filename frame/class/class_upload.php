<?php

/**
 * 文件上传类3.0
 * 支持上传到阿里云，支持tfs集群
 *
 * @author flybug
 * @data 2016-01-04
 *
 */
////require_once(WEBROOT . "/include/lib/aliyun/aliyun.php");

class uploadFile {

    private $saveName; // 保存文件名
    private $savePath; // 保存路径(完整路径)
    private $flieobj; // 上传文件对象
    private $fileFormat = array('jpg', 'png', 'gif', 'zip'); // 文件格式&MIME限定
    private $overwrite = 0; // 覆盖模式
    private $maxSize = 0; // 文件最大字节
    private $ext; // 文件扩展名
    private $errno; // 错误代号
    private $returnArray = array(); // 所有文件的返回信息
    private $returninfo = array(); // 每个文件返回信息
    private $autoCreateDir = 1; //是否自动创建目录
    private $target = 0; //上传目标（0-本地，1-阿里云，2-tfs）

    // 构造函数
    // @param $savePath 文件保存相对路径
    // @param $Fileobj 文件对象
    // @param $fileFormat 文件格式限制数组
    // @param $maxSize 文件最大尺寸(单位:KB)
    // @param $overwriet 是否覆盖 1 允许覆盖 0 禁止覆盖
    // 格式化文件名，并判断文件夹属性

    public function __construct($Fileobj, $savePath, $fileFormat = '', $maxSize = 0, $overwrite = 0, $autocreatedir = 1, $target = 0) {
        $this->setSavepath($savePath); //如果是阿里云存储，直接给存储路径即可
        $this->target = $target;
        $this->flieobj = $Fileobj;
        $this->setFileformat($fileFormat);
        $this->setMaxsize($maxSize);
        $this->setOverwrite($overwrite);
        $this->errno = 0;
        $this->autoCreateDir = $autocreatedir;
        if ($autocreatedir == 1) {
            $this->makeDirectory($this->savePath);
        }
    }

    //检查目录是否存在和可写
    //由于www服务器上安全设置较高，不允许随意创建目录，所以默认需要的目录都已经创建完毕。editer:flybug,2013-03-25
    public function makeDirectory($directoryName) {
        /* 		//使用/替换$directoryName中的\\
          $directoryName = str_replace("\\","/",$directoryName);
          //在/处分割字符串
          $dirNames = explode('/', $directoryName);
          //计算数组中的单元个数或者对象的属性个数
          $total = count($dirNames) ;
          //定义缓存文件
          $temp = '';
          //遍历每一层的文件夹是否存在、可写
          for($i=0; $i<$total; $i++)
          {
          $temp .= $dirNames[$i].'/';
          //是否存在
          if (!is_dir($temp))
          {
          //使用umask设置linux文件夹的属性
          $oldmask = umask(0);
          //创建temp文件路径
          if (!mkdir($temp, 0777)) exit("不能建立目录 $temp");
          umask($oldmask);
          }
          } */
        return true;
    }

    // 开始上传
    // @param $fileInput 网页Form(表单)中input的名称
    //run实际上只是判断文件上传的数量，拼接文件名。实际的文件上传是由copyfile（）来完成；
    public function run($fileInput) {
        //检查是否存在文件对象,$this->option['filename']
        if (isset($this->flieobj[$fileInput])) {
            $fileArr = $this->flieobj[$fileInput];

            //检查文件名是否为数组，判断是否为多文件上传
            if (is_array($fileArr['name'])) {
                //上传同文件域名称多个文件
                //遍历文件的各属性
                for ($i = 0; $i < count($fileArr['name']); $i++) {
                    $ar['tmp_name'] = $fileArr['tmp_name'][$i];
                    $ar['name'] = $fileArr['name'][$i];
                    $ar['type'] = $fileArr['type'][$i];
                    $ar['size'] = $fileArr['size'][$i];
                    $ar['error'] = $fileArr['error'][$i];
                    //取得扩展名，赋给$this->ext，下次循环会更新
                    $this->getExt($ar['name']);
                    //取得文件的存储名=名字+扩展名
                    $this->setSavename();
                    $this->saveName = "$this->saveName.$this->ext";
                    //copyfile,不成功就返回报错
                    if (!$this->copyfile($ar)) {
                        $this->returninfo['error'] = $this->errmsg();
                    }
                    $this->returnArray[] = $this->returninfo;
                }
            } else {//上传单个文件
                //取得扩展名
                $this->getExt($fileArr['name']);
                if (F::isNotNull($this->saveName)) {//拼接指定文件名,并设置同名覆盖
                    $this->saveName = $this->saveName . '.' . $this->ext;
                    $this->setOverwrite(1);
                } else {//如果没有名称,就去生成文件名称
                    $this->setSavename();
                }
                //取得文件的存储名=名字+扩展名(统一默认为jpg 方便直接调用,无需入库)
                //echo $this->saveName;
                if (!strpos($this->saveName, '.')) {
                    $this->saveName = $this->saveName . '.' . $this->ext;
                }
                if (!$this->copyfile($fileArr)) {
                    $this->returninfo['error'] = $this->errmsg();
                }
                $this->returnArray[] = $this->returninfo;
            }
            //是否返回错误代码

            return $this->errno ? false : true;
        } else {
            //返回输入的文件名无效
            $this->errno = 10;
            return false;
        }
    }

    // 将单个上传的文件复制到指定文件夹为指定的文件名，并删除系统临时文件
    // @param $fileArray 文件信息数组
    public function copyfile($fileArray) {
        //创建数组
        $this->returninfo = array();
        // 返回信息
        $this->returninfo['name'] = $fileArray['name'];
        $this->returninfo['saveName'] = $this->saveName;

        //通过千分组来格式化数字  将($fileArray['size'])/1024 保存0位小数，没三位用'.'隔开
        $this->returninfo['size'] = number_format(($fileArray['size']) / 1024, 0, '.', ' '); //以KB为单位
        //获取文件类型
        $this->returninfo['type'] = $fileArray['type'];
        // 检查文件格式
        //echo $this->savePath.'as';
        if (!$this->validateFormat()) {
            $this->errno = 11;
            return false;
        }
        // 检查目录是否可写
        if (($this->target == 0) && !is_writable($this->savePath)) {

            $this->errno = 12;
            return false;
        }

        // 如果不允许覆盖，检查文件是否已经存在
        if ($this->overwrite == 0 && file_exists($this->savePath . $this->saveName)) {
            $this->errno = 13;
            return false;
        }
        // 如果有大小限制，检查文件是否超过限制
        if ($this->maxSize != 0) {
            if (($fileArray['size']) / 1024 > $this->maxSize) {
                $this->errno = 14;
                return false;
            }
        }
        /*
         * 文件上传使用move_uploaded_file函数代替copy函数
         * move_uploaded_file() 函数将上传的文件移动到新位置
         * 
         * memo by flybug
         * 2014-07-10
         * 集成阿里云服务，如果是同步阿里云，则将文件复制到cache/temp目录，然后上传并删除临时文件
         * 如果是本地上传，则直接复制到制定目录即可
         * 
         */

        switch ($this->target) {
            case 0://本地
                $fullFileName = $this->savePath . $this->saveName;
                if (!move_uploaded_file($fileArray["tmp_name"], $fullFileName)) {
                    $this->errno = $fileArray["error"];
                    return false;
                }
                break;
            case 1://阿里云
                $oss = new aliyun();
                $oss->putResourceObject($this->savePath . $this->saveName, $fileArray["tmp_name"]); //如果是阿里云，在cache/temp里面上传文件
                $oss = null;
                break;
            case 2://tfs
                $file = file_get_contents($fileArray["tmp_name"]);
                $this->returninfo['saveName'] = tfs::save($file, ".$this->ext", 1);
                if ($this->returninfo['saveName'] === false){
                    return false;
                }
                break;
        }
        //检查文件
        if (is_file($this->savePath . $this->saveName)) {
            $oldumask = umask(0);
            chmod($this->savePath . $this->saveName, 0777);
            umask($oldumask);
        }
        // 删除临时文件
        if (file_exists($fileArray["tmp_name"]) && !$this->del($fileArray["tmp_name"])) {
            $this->errno = 15;
            return false;
        }
        return true;
    }

    // 文件格式检查,MIME检测,不区分大小写
    public function validateFormat() {
        //是否为数组、扩展名是否在数组中、小写类型是否在数组中
        return (!is_array($this->fileFormat) || in_array(strtolower($this->ext), $this->fileFormat) || in_array(strtolower($this->returninfo['type']), $this->fileFormat));
    }

    // 获取文件扩展名
    // @param $fileName 上传文件的原文件名
    public function getExt($fileName) {
        //文件名在.处被切断成数组
        $ext = explode(".", $fileName);
        //取数组的最后一个	
        $ext = $ext[count($ext) - 1];
        //全部小写	
        $this->ext = strtolower($ext);
    }

    // 设置上传文件的最大字节限制
    // @param $maxSize 文件大小(bytes) 0:表示无限制
    public function setMaxsize($maxSize) {
        $this->maxSize = $maxSize;
    }

    // 设置文件格式限定
    // @param $fileFormat 文件格式数组
    public function setFileformat($fileFormat) {
        if (is_array($fileFormat)) {
            $this->fileFormat = $fileFormat;
        }
    }

    // 设置覆盖模式
    // @param overwrite 覆盖模式 1:允许覆盖 0:禁止覆盖
    public function setOverwrite($overwrite) {
        $this->overwrite = $overwrite;
    }

    // 设置保存路径
    // @param $savePath 文件保存路径：以 "/" 结尾，若没有 "/"，则补上
    public function setSavepath($savePath) {
        $filePath = substr(str_replace("\\", "/", $savePath), -1) == "/" ? $savePath : $savePath . "/";
        $makePath = trim($filePath, "/");
        //$a = F::creatdir($makePath);
        $this->savePath = $filePath;
    }

    // 设置文件保存名
    // @param $saveName 保存名，如果为空，则系统自动生成一个随机的文件名,不带扩展名
    public function setSavename($type = 0, $saveName = '') {
        switch ($type) {
            case 1://指定文件名
                $this->saveName = $saveName;
                break;
            case 2://指定文件名为前缀的随机文件名
                $this->saveName = $saveName . '_' . date('YmdHis') . '_' . rand(100, 999);
                break;
            default:
                $this->saveName = date('YmdHis') . rand(100, 999);
        }
    }

    // 删除文件
    // @param $fileName 所要删除的文件名
    public function del($fileName) {
        if (!@unlink($fileName)) {
            $this->errno = 15;
            return false;
        }
        return true;
    }

    // 返回上传文件的信息
    public function getInfo() {
        return $this->returnArray;
    }

    // 得到错误信息
    public function errmsg($language = "zh") {
        if ($language == "zh") {
            $uploadClassError = array(
                0 => '上传文件成功!',
                1 => '上传文件的大小超过PHP.ini配置文件中规定的参数[upload_max_filesize].',
                2 => '上传文件的大小超过HTML表单中规定的参数[MAX_FILE_SIZE].',
                3 => '文件部分上传,不完整.',
                4 => '上传文件失败,没有文件被上传.',
                6 => '没有找到系统临时文件夹,请检查PHP4和PHP5的相关设置.',
                7 => '磁盘写入文件失败,请检查PHP5相关设置.',
                10 => '输入的文件名无效!',
                11 => '文件格式不正确!',
                12 => '目录没有写入权限!',
                13 => '文件已经存在!',
                14 => '上传文件大小超过限制范围!',
                15 => '删除文件不成功!',
                16 => '您的PHP版本不支持GIF操作函数模块.',
                17 => '您的PHP版本不支持JPEG操作函数模块.',
                18 => '您的PHP版本不支持图象操作函数模块.',
                19 => '试图拷贝源图像文件时发生错误,也许您的PHP版本不支持这类图像操作.',
                20 => '试图创建新图像文件时发生错误.',
                21 => '试图从源图像拷贝到缩略图像时发生错误.',
                22 => '试图保存缩略图像到文件系统时发生错误,确认您的系统是否允许读写指定的文件夹.'
            );
        } else {
            $uploadClassError = array(
                0 => 'Upload file successful!',
                1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                2 => 'The uploaded file exceeds the MAX_FILE_SIZE that was specified in the HTML form.',
                3 => 'The uploaded file was only partially uploaded.',
                4 => 'No file was uploaded.',
                6 => 'Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.',
                7 => 'Failed to write file to disk. Introduced in PHP 5.1.0.',
                10 => 'Input name is not unavailable!',
                11 => 'File\'s type is error!',
                12 => 'Directory unwritable!',
                13 => 'File exist already!',
                14 => 'The uploaded file exceed that was specified.',
                15 => 'Delete file unsuccessfully!',
                16 => 'Your version of PHP does not appear to have GIF thumbnailing support.',
                17 => 'Your version of PHP does not appear to have JPEG thumbnailing support.',
                18 => 'Your version of PHP does not appear to have pictures thumbnailing support.',
                19 => 'An error occurred while attempting to copy the source image. Your version of php (' . phpversion() . ') may not have this image type support.',
                20 => 'An error occurred while attempting to create a new image.',
                21 => 'An error occurred while copying the source image to the thumbnail image.',
                22 => 'An error occurred while saving the thumbnail image to the filesystem. Are you sure that PHP has been configured with both read and write access on this folder?'
            );
        }
        if ($this->errno == 0) {
            return false;
        } else {
            return $uploadClassError[$this->errno];
        }
    }

}
