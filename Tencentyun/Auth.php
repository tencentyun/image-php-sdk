<?php
namespace Tencentyun;

class Auth
{

    const AUTH_URL_FORMAT_ERROR = -1;
    const AUTH_SECRET_ID_KEY_ERROR = -2;

    /**
     * 签名函数
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

        $cate   = $urlInfo['cate'];
        $ver    = $urlInfo['ver'];
        $appid  = $urlInfo['appid'];
        $userid = $urlInfo['userid'];
        $oper   = isset($urlInfo['oper']) ? $urlInfo['oper'] : '';
        $fileid = isset($urlInfo['fileid']) ? $urlInfo['fileid'] : '';

        $onceOpers = array('del', 'copy');
        if ($oper && in_array($oper, $onceOpers)) {
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
     * 获取url信息
     * @param  string $url 请求url
     * @return array       信息数组
     */
	public static function getInfoFromUrl($url) {
        $args = parse_url($url);
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
	}
}

//end of script

