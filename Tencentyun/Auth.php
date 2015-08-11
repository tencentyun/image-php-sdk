<?php
namespace Tencentyun;

class Auth
{

    const AUTH_URL_FORMAT_ERROR = -1;
    const AUTH_SECRET_ID_KEY_ERROR = -2;

    /**
     * 支持自定义fileid签名函数
     * 复制、删除操作，fileid必须指定，且expired为0
     * @param  string $bucket  空间名称
     * @param  string $fileid  自定义fileid，无需urlencode
     * @param  int $expired    过期时间，单次签名请传0并指定fileid
     * @return userid          用户userid，建议不指定
     */
    public static function getAppSignV2($bucket, $fileid, $expired, $userid = '0') {

        $secretId = Conf::SECRET_ID;
        $secretKey = Conf::SECRET_KEY;
        $appid = Conf::APPID;
        
        if (empty($secretId) || empty($secretKey) || empty($appid)) {
            return self::AUTH_SECRET_ID_KEY_ERROR;
        }
        
        $puserid = '';
        if (isset($userid)) {
            if (strlen($userid) > 64) {
                return self::AUTH_URL_FORMAT_ERROR;
            }
            $puserid = $userid;
        }
                    
        $now = time();    
        $rdm = rand();

        $plainText = 'a='.$appid.'&b='.$bucket.'&k='.$secretId.'&e='.$expired.'&t='.$now.'&r='.$rdm.'&u='.$puserid.'&f='.$fileid;
        $bin = hash_hmac("SHA1", $plainText, $secretKey, true);
        $bin = $bin.$plainText;        
        $sign = base64_encode($bin);        
        return $sign;
    }

    /**
     * 签名函数（上传、下载会生成多次有效签名，复制删除资源会生成单次有效签名）
     * @param  string $url     请求url
     * @param  int $expired    过期时间
     * @return string          签名
     * @deprecated deprecated since v2 support fileid with slash
     */
    public static function appSignV2($url, $expired=0, $options=array()) {

        $secretId = Conf::SECRET_ID;
        $secretKey = Conf::SECRET_KEY;

        if (empty($secretId) || empty($secretKey)) {
            return self::AUTH_SECRET_ID_KEY_ERROR;
        }

        $urlInfo = self::getInfoFromUrlV2($url);
        if (empty($urlInfo)) {
            return self::AUTH_URL_FORMAT_ERROR;
        }

        $cate   = isset($urlInfo['cate']) ? $urlInfo['cate'] : '';
        $ver    = isset($urlInfo['ver']) ? $urlInfo['ver'] : '';
        $appid  = $urlInfo['appid'];
        $bucket  = $urlInfo['bucket'];
        $userid = $urlInfo['userid'];
        $oper   = isset($urlInfo['oper']) ? $urlInfo['oper'] : '';
        $fileid = isset($urlInfo['fileid']) ? $urlInfo['fileid'] : '';
        $style = isset($urlInfo['style']) ? $urlInfo['style'] : '';

        $onceOpers = array('del', 'copy');
        if (($oper && in_array($oper, $onceOpers))) {
            $expired = 0;
        }

        if (!$oper && $fileid && !$style) {
            // 自定义fileid上传
            $fileid = '';
        }
        
        $puserid = '';
        if (!empty($userid)) {
            if (strlen($userid) > 64) {
                return self::AUTH_URL_FORMAT_ERROR;
            }
            $puserid = $userid;
        }
                    
        $now = time();    
        $rdm = rand();

        $plainText = 'a='.$appid.'&k='.$secretId.'&e='.$expired.'&t='.$now.'&r='.$rdm.'&u='.$puserid.'&f='.$fileid;

        $bin = hash_hmac("SHA1", $plainText, $secretKey, true);

        $bin = $bin.$plainText;        

        $sign = base64_encode($bin);        
        
        return $sign;
    }

    /**
     * 签名函数（上传、下载会生成多次有效签名，复制删除资源会生成单次有效签名）
	 * 如果需要针对下载生成单次有效签名，请使用函数appSign_once
     * @param  string $url     请求url
     * @param  int $expired    过期时间
     * @return string          签名
     * @deprecated deprecated since v2 
     */
    public static function appSign($url, $expired) {

        $secretId = Conf::SECRET_ID;
        $secretKey = Conf::SECRET_KEY;

        if (empty($secretId) || empty($secretKey)) {
            return self::AUTH_SECRET_ID_KEY_ERROR;
        }

        $urlInfo = self::getInfoFromUrl($url);
        if (empty($urlInfo)) {
            return self::AUTH_URL_FORMAT_ERROR;
        }

        $cate   = isset($urlInfo['cate']) ? $urlInfo['cate'] : '';
        $ver    = isset($urlInfo['ver']) ? $urlInfo['ver'] : '';
        $appid  = $urlInfo['appid'];
        $userid = $urlInfo['userid'];
        $oper   = isset($urlInfo['oper']) ? $urlInfo['oper'] : '';
        $fileid = isset($urlInfo['fileid']) ? $urlInfo['fileid'] : '';
        $style = isset($urlInfo['style']) ? $urlInfo['style'] : '';

        $onceOpers = array('del', 'copy');
        if ($fileid || ($oper && in_array($oper, $onceOpers))) {
            $expired = 0;
        }
        
        $puserid = '';
        if (!empty($userid)) {
            if (strlen($userid) > 64) {
                return self::AUTH_URL_FORMAT_ERROR;
            }
            $puserid = $userid;
        }
                    
        $now = time();    
        $rdm = rand();

        $plainText = 'a='.$appid.'&k='.$secretId.'&e='.$expired.'&t='.$now.'&r='.$rdm.'&u='.$puserid.'&f='.$fileid;
        $bin = hash_hmac("SHA1", $plainText, $secretKey, true);
        $bin = $bin.$plainText;        
        $sign = base64_encode($bin);        
        return $sign;
    }

	 /**
     * 生成单次有效签名函数（用于复制、删除和下载指定fileid资源，使用一次即失效）
     * @param  string $fileid     文件唯一标识符
	 * @param  string $userid  开发者账号体系下的userid，没有请使用默认值0
     * @return string          签名
     */
    public static function appSign_once($fileid, $userid = '0') {

        $secretId = Conf::SECRET_ID;
        $secretKey = Conf::SECRET_KEY;
		$appid = Conf::APPID;
		
        if (empty($secretId) || empty($secretKey) || empty($appid)) {
            return self::AUTH_SECRET_ID_KEY_ERROR;
        }
        
        $puserid = '';
        if (!empty($userid)) {
            if (strlen($userid) > 64) {
                return self::AUTH_URL_FORMAT_ERROR;
            }
            $puserid = $userid;
        }
                    
        $now = time();    
        $rdm = rand();

        $plainText = 'a='.$appid.'&k='.$secretId.'&e=0'.'&t='.$now.'&r='.$rdm.'&u='.$puserid.'&f='.$fileid;
        $bin = hash_hmac("SHA1", $plainText, $secretKey, true);
        $bin = $bin.$plainText;        
        $sign = base64_encode($bin);        
        return $sign;
    }
	
	/**
     * 生成多次有效签名函数（用于上传和下载资源，有效期内可重复对不同资源使用）
     * @param  int $expired    过期时间
	 * @param  string $userid  开发者账号体系下的userid，没有请使用默认值0
     * @return string          签名
     */
    public static function appSign_more($expired,$userid = '0') {

        $secretId = Conf::SECRET_ID;
        $secretKey = Conf::SECRET_KEY;
		$appid = Conf::APPID;
		
        if (empty($secretId) || empty($secretKey) || empty($appid)) {
            return self::AUTH_SECRET_ID_KEY_ERROR;
        }
        
        $puserid = '';
        if (!empty($userid)) {
            if (strlen($userid) > 64) {
                return self::AUTH_URL_FORMAT_ERROR;
            }
            $puserid = $userid;
        }
                    
        $now = time();    
        $rdm = rand();

        $plainText = 'a='.$appid.'&k='.$secretId.'&e='.$expired.'&t='.$now.'&r='.$rdm.'&u='.$puserid.'&f=';
        $bin = hash_hmac("SHA1", $plainText, $secretKey, true);
        $bin = $bin.$plainText;        
        $sign = base64_encode($bin);        
        return $sign;
    }

    /**
     * 获取url信息（目前支持腾讯云万象优图带有bucket和自定义fileId特性的）
     * 老版本（无bucket或者自定义fileId特性）请使用 @see Auth::getInfoFromUrl
     * @param  string $url 请求url
     * @return array       信息数组
     * @deprecated deprecated since v2 support fileid with slash
     */
    public static function getInfoFromUrlV2($url) {
        $args = parse_url($url);
        $endPointArgs_image = parse_url(Conf::API_IMAGE_END_POINT_V2);
        // 非下载url
        if ($args['host'] == $endPointArgs_image['host']) {
            if (isset($args['path'])) {
                $parts = explode('/', $args['path']);
                switch (count($parts)) {
                    case 6:
                        $cate = $parts[1];
                        $ver = $parts[2];
                        $appid = $parts[3];
                        $bucket = $parts[4];
                        $userid = $parts[5];
                        return array('cate' => $cate, 'ver' => $ver, 'appid' => $appid, 'bucket' => $bucket, 'userid' => $userid);
                    break;
                    case 7:
                        $cate = $parts[1];
                        $ver = $parts[2];
                        $appid = $parts[3];
                        $bucket = $parts[4];
                        $userid = $parts[5];
                        $fileid = $parts[6];
                        return array('cate' => $cate, 'ver' => $ver, 'appid' => $appid, 'bucket' => $bucket, 'userid' => $userid, 'fileid' => $fileid);
                    break;
                    case 8:
                        $cate = $parts[1];
                        $ver = $parts[2];
                        $appid = $parts[3];
                        $bucket = $parts[4];
                        $userid = $parts[5];
                        $fileid = $parts[6];
                        $oper = $parts[7];
                        return array('cate' => $cate, 'ver' => $ver, 'appid' => $appid, 'bucket' => $bucket, 'userid' => $userid, 'fileid' => $fileid, 'oper' => $oper);
                    break;
                    default:
                        return array();
                }
            } else {
                return array();
            }
        } else {
            // 下载url
            if (isset($args['path'])) {
                $parts = explode('/', $args['path']);
                switch (count($parts)) {
                    case 5:
                        $arr = explode('-', $parts[1]);
                        if (2 !== count($arr)) {
                            return array();
                        }
                        $bucket = $arr[0];
                        $appid = $arr[1];
                        $userid = $parts[2];
                        $fileid = $parts[3];
                        $style = $parts[4];
                        return array('appid' => $appid, 'bucket' => $bucket, 'userid' => $userid, 'fileid' => $fileid, 'style' => $style);
                    break;
                    default:
                        return array();
                }
            } else {
                return array();
            }
        }
    }
	
    /**
     * 获取url信息
     * @param  string $url 请求url
     * @return array       信息数组
     * @deprecated deprecated since v2 support fileid with slash
     */
	public static function getInfoFromUrl($url) {
        $args = parse_url($url);
        $endPointArgs_image = parse_url(Conf::API_IMAGE_END_POINT);
		$endPointArgs_video = parse_url(Conf::API_VIDEO_END_POINT);
        // 非下载url
        if ($args['host'] == $endPointArgs_image['host'] || $args['host'] == $endPointArgs_video['host']) {
            if (isset($args['path'])) {
                $parts = explode('/', $args['path']);
                switch (count($parts)) {
                    case 5:
                        $cate = $parts[1];
                        $ver = $parts[2];
                        $appid = $parts[3];
                        $userid = $parts[4];
                        return array('cate' => $cate, 'ver' => $ver, 'appid' => $appid, 'userid' => $userid);
                    break;
                    case 6:
                        $cate = $parts[1];
                        $ver = $parts[2];
                        $appid = $parts[3];
                        $userid = $parts[4];
                        $fileid = $parts[5];
                        return array('cate' => $cate, 'ver' => $ver, 'appid' => $appid, 'userid' => $userid, 'fileid' => $fileid);
                    break;
                    case 7:
                        $cate = $parts[1];
                        $ver = $parts[2];
                        $appid = $parts[3];
                        $userid = $parts[4];
                        $fileid = $parts[5];
                        $oper = $parts[6];
                        return array('cate' => $cate, 'ver' => $ver, 'appid' => $appid, 'userid' => $userid, 'fileid' => $fileid, 'oper' => $oper);
                    break;
                    default:
                        return array();
                }
            } else {
                return array();
            }
        } else {
            // 下载url
            if (isset($args['path'])) {
                $parts = explode('/', $args['path']);
                switch (count($parts)) {
                    case 5:
                        $appid = $parts[1];
                        $userid = $parts[2];
                        $fileid = $parts[3];
                        $style = $parts[4];
                        return array('appid' => $appid, 'userid' => $userid, 'fileid' => $fileid, 'style' => $style);
                    break;
                    default:
                        return array();
                }
            } else {
                return array();
            }
        }
	}
}

//end of script

