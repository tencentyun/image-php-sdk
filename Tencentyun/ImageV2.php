<?php

namespace Tencentyun;

class ImageV2
{
    
    public static $_messageInfo = array();
    // 30 days
    const EXPIRED_SECONDS = 2592000;

    const IMAGE_FILE_NOT_EXISTS = -1;
    const IMAGE_NETWORK_ERROR = -2;
    const IMAGE_PARAMS_ERROR = -3;
    const COSAPI_ILLEGAL_SLICE_SIZE_ERROR = -4;
    
    //10M
    const MIN_SLICE_FILE_SIZE = 10485;
    
    //16K
    const DEFAULT_SLICE_SIZE = 16384;   //16*1024
    const MAX_RETRY_TIMES = 3;
    
    private static $timeout = 10;
    public static $_sliceSize = 1048576;

    /**
     * 上传文件
     * @param  string  $filePath     本地文件路径
     * @param  string  $bucket       空间名
     * @param  integer $userid       用户自定义分类
     * @param  string  $magicContext 自定义回调参数
     * @param  array   $params       参数数组
     * @return [type]                [description]
     */
    public static function upload($filePath, $bucket, $fileid = '', $userid = 0, $magicContext = '', $params = array()) {
        if (!file_exists($filePath)) {
            return array('httpcode' => 0, 'code' => self::IMAGE_FILE_NOT_EXISTS, 'message' => 'file '.$filePath.' not exists', 'data' => array());
        }

        return self::upload_impl($filePath, 0, $bucket, $fileid, $userid, $magicContext, $params);
    }
    
    /**
     * 上传文件
     * @param  string  $filePath     本地文件路径
     * @param  string  $bucket       空间名
     * @param  integer $userid       用户自定义分类
     * @param  string  $magicContext 自定义回调参数
     * @param  array   $params       参数数组
     * @return [type]                [description]
     */
    public static function uploadSlice($filePath, $bucket=Conf::BUCKET, $fileid = '', $sliceSize = 0, $session = null,$userid = 0, $magicContext = null,   $params = array()) {  
        $res = self::upload_slice_impl($filePath, $bucket, $fileid, $userid, $magicContext, $sliceSize, $session, $params);      
        if(false === $res)
        {
            $data = array();
        }else{
            $data = $res;
        }

         return array('code' =>self::$_messageInfo["code"], 
                         'message' => self::$_messageInfo["message"],
                         'data' => $data);
    }
    
    

    /**
     * Upload a file via in-memory binary data
     * The only difference with upload() is that 1st parameter is binary string of an image
     */
    public static function upload_binary($fileContent, $bucket, $fileid = '', $userid = 0, $magicContext = '', $params = array()) {
        return self::upload_impl($fileContent, 1, $bucket, $fileid, $userid, $magicContext, $params);
    }

    /**
     * filetype: 0 -- filename, 1 -- in-memory binary file
     */
    public static function upload_impl($fileObj, $filetype, $bucket, $fileid, $userid, $magicContext, $params) {

        // $filePath = realpath($filePath);

        $expired = time() + self::EXPIRED_SECONDS;
        $url = self::generateResUrl($bucket, $userid, $fileid);
        $sign = Auth::getAppSignV2($bucket, $fileid, $expired);

        // add get params to url
        if (isset($params['get']) && is_array($params['get'])) {
            $queryStr = http_build_query($params['get']);
            $url .= '?'.$queryStr;
        }

        $data = array();

        if ($filetype == 0) {
            if (function_exists('curl_file_create')) {
                $data['FileContent'] = curl_file_create(realpath($fileObj));
            } else {
                $data['FileContent'] = '@'.realpath($fileObj);
            }
        } else if ($filetype == 1) {
            $data['FileContent'] = $fileObj;
        }

        if ($magicContext) {
            $data['MagicContext'] = $magicContext;
        }

        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => 10,
            'data' => $data,
            'header' => array(
                'Authorization:QCloud '.$sign,
            ),
        );

        $rsp = Http::send($req);
        $info = Http::info();
        $ret = json_decode($rsp, true);
        if ($ret) {
            if (0 === $ret['code']) {
                $data = array(
                    'url' => $ret['data']['url'],
                    'downloadUrl' => $ret['data']['download_url'],
                    'fileid' => $ret['data']['fileid'],
                    'info' => $ret['data']['info'],
                );
                if (array_key_exists('is_fuzzy', $ret['data'])) {
                    $data['isFuzzy'] = $ret['data']['is_fuzzy'];
                }
                if (array_key_exists('is_food', $ret['data'])) {
                    $data['isFood'] = $ret['data']['is_food'];
                }
                return array('httpcode' => $info['http_code'], 'code' => $ret['code'], 'message' => $ret['message'], 'data' => $data);
            } else {
                return array('httpcode' => $info['http_code'], 'code' => $ret['code'], 'message' => $ret['message'], 'data' => array());
            }
        } else {
            return array('httpcode' => $info['http_code'], 'code' => self::IMAGE_NETWORK_ERROR, 'message' => 'network error', 'data' => array());
        }
    }
    
    /**
     * filetype: 0 -- filename, 1 -- in-memory binary file
     */
    public static function upload_slice_impl($filePath, $bucket, $fileid, $userid = 0, $magicContext, $sliceSize, $session, $params) {
         $filePath = realpath($filePath);
         
         if (!file_exists($filePath)) {
             self::setMessageInfo(-1, "file not exixts");                        
             return false;
         }
         
         $fileSize = filesize($filePath);  
         
        $expired = time() + self::EXPIRED_SECONDS;
        $url = self::generateResUrl($bucket, $userid, $fileid);
        $sign = Auth::getAppSignV2($bucket, $fileid, $expired);
        if(false === $sign)
        {
            return false;
        }
        
        $sha1 = hash_file('sha1', $filePath);

        $info = self::upload_slice_init(
               $url, $sign,$sha1,
                 $fileSize,$sliceSize,$session,$magicContext);
        if(false === $info)
        {
            return false;
        }
        
        if(isset($info['slice_size']))
        {
            self::$_sliceSize = $info['slice_size'];
        }
        else
        {
            self::$_sliceSize = $sliceSize;
        }
        
        $retryTimes = 0;
        $newInfo = array();
        while(!isset($info['url']) && isset($info['session']))
        {  
            $newInfo = self::upload_slice_data($filePath, $info, $sign, $url);
            if(false === $newInfo)
            { 
                $retryTimes++;
                if($retryTimes >= self::MAX_RETRY_TIMES)
                {
                    $info = false;
                    break;
                }
                continue;
            }                                  

            $retryTimes = 0;

            $info = $newInfo;
            if(isset($newInfo['offset']))
            {
                $info['offset'] = $newInfo['offset'] + self::$_sliceSize;
            }                      
        }
        
        $messageInfo = self::getMessageInfo();
        
        if(false === $info)
        {
            return false;
        }
        
        $data = array(
                'url' => $info['url'],
                'downloadUrl' => $info['download_url'],
                'fileid' => $info['fileid'],
            );
          
        self::setMessageInfo(0, "upload slice success");

        return $data;
        
    }
    
    private static function upload_slice_init($url, $sign,$sha1,$fileSize,$sliceSize,$session,$magicContext)
     {   
        $data = array(
                'op' => 'upload_slice',
                'filesize' => $fileSize,
                'sha' => $sha1,
        );
        
        if($magicContext) {
            $data['magicContext'] = $magicContext;
        }
        if($session){
            $data['session'] = $session;
        }
        
        if ($sliceSize > 0) {
                $data['slice_size'] = $sliceSize;
            }
             else {
                $data['slice_size'] = self::DEFAULT_SLICE_SIZE;
            }
    
        $req = array(
                'url' => $url,
                'method' => 'post',
                'timeout' => self::$timeout,
                'data' => $data,
                'header' => array(
                        'Connection:Keep-Alive',
                        'Authorization:'.$sign,
                ),
        );

        $rsp = Http::send($req);       
        $info = Http::info();
        $ret = json_decode($rsp, true);    
        if(!$ret ) 
        {
            self::setMessageInfo($ret['code'], 'network error');
            return false;
        }
        
        if(0 !== $ret['code'] || (200 != $info['http_code']))
        {
            self::setMessageInfo($ret['code'], $ret['message']);
            return false;
        }
                
        $info = $ret['data']; 
           
        return $info;
    }
    
    private static function upload_slice_data($filePath,$info, $sign,$url){       
        // 设置分割标识
        srand((double)microtime()*1000000);
        $boundary = '---------------------------'.substr(md5(rand(0,32000)),0,10);
        $data = self::generateSliceData($filePath,$info,$boundary);
        if(false === $data)
        {
            return false;
        }
        
        $req = array(
                'url' => $url,
                'method' => 'post',
                'timeout' => self::$timeout,
                'data' => $data,
                'header' => array(
                        'accept:*/*',
                        'Connection:Keep-Alive',
                        'user-agent:qcloud-php-sdk',
                        'Host:web.image.myqcloud.com',
                        'Authorization:'.$sign,
                        'Expect: ',  
                        'Method:POST',
                        'Content-Type: multipart/form-data; boundary=' . $boundary,             
                ),
        );

        $rsp = Http::send($req);       
        $info = Http::info(); 
        $ret = json_decode($rsp, true);
        
        if(!$ret || (200 != $info['http_code'])) 
        {
            self::setMessageInfo($ret['code'], 'network error');
            return false;
        }
        
        if(0 !== $ret['code'])
        {
            self::setMessageInfo($ret['code'], $ret['message']);
            return false;
        }

        $info = $ret['data'];

        return $info;
    }
    
    private static function generateSliceData($filePath, $info,$boundary) {  
        $filecontent = file_get_contents(
                $filePath, false, null,$info['offset'],self::$_sliceSize); 
             
        if(false === $filecontent ){
            self::setMessageInfo(-1, 'file content get error');
            return false;
        }
                  
        $formdata = '';
    
        $formdata .= '--' . $boundary . "\r\n";
        $formdata .= "content-disposition: form-data; name=\"op\"\r\n\r\nupload_slice\r\n";
    
        $formdata .= '--' . $boundary . "\r\n";
        $formdata .= "content-disposition: form-data; name=\"offset\"\r\n\r\n" . $info['offset']. "\r\n";
    
        $formdata .= '--' . $boundary . "\r\n";
        $formdata .= "content-disposition: form-data; name=\"session\"\r\n\r\n" . $info['session'] . "\r\n";
    
        $formdata .= '--' . $boundary . "\r\n";
        $formdata .= "content-disposition: form-data; name=\"fileContent\"; filename=\"" . basename($filePath) . "\"\r\n";
        $formdata .= "content-type: application/octet-stream\r\n\r\n";
    
        $data = $formdata . $filecontent . "\r\n--" . $boundary . "--\r\n";

        return $data;
    }

    /**
     * 查询
     * @param  string  $bucket 空间名
     * @param  string  $fileid 文件名
     * @param  string  $userid [description]
     * @return array           返回信息
     */
    public static function stat($bucket, $fileid, $userid=0) {

        if (!$fileid) {
            return array('httpcode' => 0, 'code' => self::IMAGE_PARAMS_ERROR, 'message' => 'params error', 'data' => array());
        }

        $expired = time() + self::EXPIRED_SECONDS;
        $url = self::generateResUrl($bucket, $userid, $fileid);
        $sign = Auth::getAppSignV2($bucket, $fileid, $expired);

        $req = array(
            'url' => $url,
            'method' => 'get',
            'timeout' => 10,
            'header' => array(
                'Authorization:QCloud '.$sign,
            ),
        );

        $rsp = Http::send($req);
        $info = Http::info();
        $ret = json_decode($rsp, true);
        if ($ret) {
            if (0 === $ret['code']) {
                $retData = $ret['data'];
                return array('httpcode' => $info['http_code'], 'code' => $ret['code'], 'message' => $ret['message'],
                    'data' => array(
                        'downloadUrl' => isset($retData['file_url']) ? $retData['file_url'] : '',
                        'fileid' => isset($retData['file_fileid']) ? $retData['file_fileid'] : '',
                        'uploadTime' => isset($retData['file_upload_time']) ? $retData['file_upload_time'] : '',
                        'size' => isset($retData['file_size']) ? $retData['file_size'] : '',
                        'md5' => isset($retData['file_md5']) ? $retData['file_md5'] : '',
                        'width' => isset($retData['photo_width']) ? $retData['photo_width'] : '',
                        'height' => isset($retData['photo_height']) ? $retData['photo_height'] : '',
                    )
                );
            } else {
                return array('httpcode' => $info['http_code'], 'code' => $ret['code'], 'message' => $ret['message'], 'data' => array());
            }
        } else {
            return array('httpcode' => $info['http_code'], 'code' => self::IMAGE_NETWORK_ERROR, 'message' => 'network error', 'data' => array());
        }
    }

    public static function copy($bucket, $fileid, $userid=0)    {
        if (!$fileid) {
            return array('httpcode' => 0, 'code' => self::IMAGE_PARAMS_ERROR, 'message' => 'params error', 'data' => array());
        }

        $expired = 0;
        $url = self::generateResUrl($bucket, $userid, $fileid, 'copy');
        $sign = Auth::getAppSignV2($bucket, $fileid, $expired);

        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => 10,
            'header' => array(
                'Authorization:QCloud '.$sign,
            ),
        );

        $rsp = Http::send($req);
        $info = Http::info();
        $ret = json_decode($rsp, true);
        if ($ret) {
            if (0 === $ret['code']) {
                return array(
                    'httpcode' => $info['http_code'],
                    'code' => $ret['code'],
                    'message' => $ret['message'],
                    'data' => array(
                        'url' => $ret['data']['url'],
                        'downloadUrl' => $ret['data']['download_url'],
                    )
                );
            } else {
                return array('httpcode' => $info['http_code'], 'code' => $ret['code'], 'message' => $ret['message'], 'data' => array());
            }
        } else {
            return array('httpcode' => $info['http_code'], 'code' => self::IMAGE_NETWORK_ERROR, 'message' => 'network error', 'data' => array());
        }
    }

    public static function del($bucket, $fileid, $userid=0)    {
        if (!$fileid) {
            return array('httpcode' => 0, 'code' => self::IMAGE_PARAMS_ERROR, 'message' => 'params error', 'data' => array());
        }

        $expired = 0;
        $url = self::generateResUrl($bucket, $userid, $fileid, 'del');
        $sign = Auth::getAppSignV2($bucket, $fileid, $expired);

        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => 10,
            'header' => array(
                'Authorization:QCloud '.$sign,
            ),
        );

        $rsp = Http::send($req);
        $info = Http::info();
        $ret = json_decode($rsp, true);
        if ($ret) {
            if (0 === $ret['code']) {
                return array('httpcode' => $info['http_code'], 'code' => $ret['code'], 'message' => $ret['message'], 'data' => array());
            } else {
                return array('httpcode' => $info['http_code'], 'code' => $ret['code'], 'message' => $ret['message'], 'data' => array());
            }
        } else {
            return array('httpcode' => $info['http_code'], 'code' => self::IMAGE_NETWORK_ERROR, 'message' => 'network error', 'data' => array());
        }
    }

    public static function generateResUrl($bucket, $userid=0, $fileid='', $oper = '') {
        if ($fileid) {
            $fileid = urlencode($fileid);
            if ($oper) {
                return Conf::API_IMAGE_END_POINT_V2 . Conf::APPID . '/' . $bucket . '/' . $userid . '/' . $fileid . '/' . $oper;
            } else {
                return Conf::API_IMAGE_END_POINT_V2 . Conf::APPID . '/' . $bucket . '/' . $userid . '/' . $fileid;
            }
        } else {
            return Conf::API_IMAGE_END_POINT_V2 . Conf::APPID . '/' . $bucket . '/' . $userid;
        }
    }

    public static function setMessageInfo($code, $message) {
        self::$_messageInfo["code"] = $code;
        self::$_messageInfo["message"] = $message;
    }
    
    public static function getMessageInfo() {
        return self::$_messageInfo;
    }
}

//end of script


