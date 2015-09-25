<?php

namespace Tencentyun;

class ImageV2
{
    // 30 days
    const EXPIRED_SECONDS = 2592000;

    const IMAGE_FILE_NOT_EXISTS = -1;
    const IMAGE_NETWORK_ERROR = -2;
    const IMAGE_PARAMS_ERROR = -3;

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
                $data['FileContent'] = '@'.$fileObj;
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

}



//end of script


