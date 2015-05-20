<?php

namespace Tencentyun;

class Image
{
	public $appid;
	public $userid;
	public $secretId;
	public $secretKey;
	public $mySign;

	// 30 days
	const EXPIRED_SECONDS = 2592000;

	const IMAGE_FILE_NOT_EXISTS = -1;
	const IMAGE_NETWORK_ERROR = -2;
	const IMAGE_PARAMS_ERROR = -3;
					
	public static function upload($filePath, $userid = 0, $magicContext = '') {

		if (!file_exists($filePath)) {
			return array('httpcode' => 0, 'code' => self::IMAGE_FILE_NOT_EXISTS, 'message' => 'file '.$filePath.' not exists', 'data' => array());
		}

		$expired = time() + self::EXPIRED_SECONDS;
		$url = self::generateResUrl($userid);
		$sign = Auth::appSign($url, $expired);

		$data = array(
            'FileContent' => '@'.$filePath,
        );
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
				return array('httpcode' => $info['http_code'], 'code' => $ret['code'], 'message' => $ret['message'], 
					'data' => array('url' => $ret['data']['url'], 'downloadUrl' => $ret['data']['download_url'], 'fileid' => $ret['data']['fileid']));
			} else {
				return array('httpcode' => $info['http_code'], 'code' => $ret['code'], 'message' => $ret['message'], 'data' => array());
			}
		} else {
			return array('httpcode' => $info['http_code'], 'code' => self::IMAGE_NETWORK_ERROR, 'message' => 'network error', 'data' => array());
		}
	}
		
	public static function stat($fileid, $userid = 0) {

		if (!$fileid) {
			return array('httpcode' => 0, 'code' => self::IMAGE_PARAMS_ERROR, 'message' => 'params error', 'data' => array());
		}

		$expired = time() + self::EXPIRED_SECONDS;
		$url = self::generateResUrl($userid, $fileid);
		$sign = Auth::appSign($url, $expired);

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
		
	public static function del($fileid, $userid = 0)	{
		if (!$fileid) {
			return array('httpcode' => 0, 'code' => self::IMAGE_PARAMS_ERROR, 'message' => 'params error', 'data' => array());
		}

		$expired = time() + self::EXPIRED_SECONDS;
		$url = self::generateResUrl($userid, $fileid, 'del');
		$sign = Auth::appSign($url, $expired);

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

	public static function generateResUrl($userid = 0, $fileid = null, $oper = '') {
	    if ($fileid) {
	        if ($oper) {
	            return Conf::API_IMAGE_END_POINT . Conf::APPID . '/' . $userid . '/' . $fileid . '/' . $oper;
	        } else {
	            return Conf::API_IMAGE_END_POINT . Conf::APPID . '/' . $userid . '/' . $fileid;
	        }
	    } else {
	        return Conf::API_IMAGE_END_POINT . Conf::APPID . '/' . $userid;
	    }
	}
		
}



//end of script


