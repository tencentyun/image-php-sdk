<?php
namespace Tencentyun;

class Auth
{

    const AUTH_URL_FORMAT_ERROR = -1;
    const AUTH_SECRET_ID_KEY_ERROR = -2;

    /**
     * 签名函数（上传、下载会生成多次有效签名，复制删除资源会生成单次有效签名）
	 * 如果需要针对下载生成单次有效签名，请使用函数appSign_once
     * @param  string $url     请求url
     * @param  int $expired    过期时间
     * @return string          签名
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
     * 获取url信息
     * @param  string $url 请求url
     * @return array       信息数组
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

