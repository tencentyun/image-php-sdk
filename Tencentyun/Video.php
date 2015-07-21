<?php

namespace Tencentyun;

class Video
{
	public $appid;
	public $userid;
	public $secretId;
	public $secretKey;
	public $mySign;

	// 30 days
	const EXPIRED_SECONDS = 2592000;

	const VIDEO_FILE_NOT_EXISTS = -1;
	const VIDEO_NETWORK_ERROR = -2;
	const VIDEO_PARAMS_ERROR = -3;

    /**
     * 上传文件
     * @param  string  $filePath     本地文件路径
     * @param  integer $userid       用户自定义分类
     * @param  string  $title        视频标题
	 * @param  string  $desc         视频描述
	 * @param  string  $magicContext 自定义回调参数
     * @return [type]                [description]
     */
	public static function upload($filePath, $userid = 0,$title = '', $desc = '', $magicContext = '') {

        // $filePath = realpath($filePath);

		if (!file_exists($filePath)) {
			return array('httpcode' => 0, 'code' => self::VIDEO_FILE_NOT_EXISTS, 'message' => 'file '.$filePath.' not exists', 'data' => array());
		}

		$expired = time() + self::EXPIRED_SECONDS;
		$url = self::generateResUrl($userid);
		$sign = Auth::appSign($url, $expired);

		$data = array(
            'FileContent' => '@'.$filePath,
        );
        if ($title) {
        	$data['Title'] = $title;
        }
        if ($desc) {
        	$data['Desc'] = $desc;
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
                'Authorization:'.$sign,
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
								'downloadUrl' => $ret['data']['download_url'], 'fileid' => $ret['data']['fileid'],
								'cover_url' => (isset($ret['data']['cover_url']) ? $ret['data']['cover_url'] : ""),
								)
							);
			} else {
				return array(
					'httpcode' => $info['http_code'],
					'code' => $ret['code'],
					'message' => $ret['message'],
					'data' => array()
				);
			}
		} else {
			return array(
					'httpcode' => $info['http_code'],
					'code' => self::VIDEO_NETWORK_ERROR,
					'message' => 'network error', 'data' => array()
				);
		}
	}

    /**
     * 分片上传文件
     * @param  string  $filePath     本地文件路径
     * @param  integer $userid       用户自定义分类
     * @param  string  $title        视频标题
	 * @param  string  $desc         视频描述
	 * @param  string  $magicContext 自定义回调参数
     * @return [type]                [description]
     */
	public static function upload_slice($filePath, $userid = 0,$title = '', $desc = '', $magicContext = '') {

		$rsp = self::upload_prepare($filePath, $userid ,$title , $desc , $magicContext );
		if($rsp['httpcode'] != 200 || $rsp['code'] != 0)//错误
		{
			return $rsp;
		}
		else if(isset($rsp['data']))//秒传命中，直接返回了url
		{
			if (isset($rsp['data']['url']))
			{
				return $rsp;
			}
		}
		$slice_size = isset($rsp['data']['slice_size']) ? (int)$rsp['data']['slice_size'] : 0;
		$offset = isset($rsp['data']['offset']) ? (int)$rsp['data']['offset'] : 0;
		$session = isset($rsp['data']['session']) ? $rsp['data']['session'] : '';

		$handle = fopen($filePath, "rb");
		$file_size = filesize($filePath);
		while($file_size > $offset)
		{
			$contents = fread($handle, $slice_size);
			$ret = self::upload_data($userid,$contents,$session,$offset);
			if($ret['httpcode'] != 200 || $ret['code'] != 0)//错误
			{
				return $ret;
			}
			else if(isset($ret['data']))//上传完毕，返回了url
			{
				if (isset($ret['data']['url']))
				{
					return $ret;
				}
			}
			$offset += $slice_size;
		}

		return $ret;
	}

	public static function stat($fileid, $userid = 0) {

		if (!$fileid) {
			return array('httpcode' => 0, 'code' => self::VIDEO_PARAMS_ERROR, 'message' => 'params error', 'data' => array());
		}

		$expired = time() + self::EXPIRED_SECONDS;
		$url = self::generateResUrl($userid, $fileid);
		$sign = Auth::appSign($url, $expired);

		$req = array(
            'url' => $url,
            'method' => 'get',
            'timeout' => 10,
            'header' => array(
                'Authorization:'.$sign,
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
						'sha' => isset($retData['file_sha']) ? $retData['file_sha'] : '',
						'videoStatus' => isset($retData['video_status']) ? $retData['video_status'] : '',
						'videoStatusMsg' => isset($retData['video_status_msg']) ? $retData['video_status_msg'] : '',
						'videoPlayTime' => isset($retData['video_play_time']) ? $retData['video_play_time'] : '',
						'videoTitle' => isset($retData['video_title']) ? $retData['video_title'] : '',
						'videoDesc' => isset($retData['video_desc']) ? $retData['video_desc'] : '',
						'videoCoverUrl' => isset($retData['video_cover_url']) ? $retData['video_cover_url'] : '',
					)
				);
			} else {
				return array('httpcode' => $info['http_code'], 'code' => $ret['code'], 'message' => $ret['message'], 'data' => array());
			}
		} else {
			return array('httpcode' => $info['http_code'], 'code' => self::VIDEO_NETWORK_ERROR, 'message' => 'network error', 'data' => array());
		}
	}

	public static function del($fileid, $userid = 0)	{
		if (!$fileid) {
			return array('httpcode' => 0, 'code' => self::VIDEO_PARAMS_ERROR, 'message' => 'params error', 'data' => array());
		}

		$expired = time() + self::EXPIRED_SECONDS;
		$url = self::generateResUrl($userid, $fileid, 'del');
		$sign = Auth::appSign($url, $expired);

		$req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => 10,
            'header' => array(
                'Authorization:'.$sign,
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
			return array('httpcode' => $info['http_code'], 'code' => self::VIDEO_NETWORK_ERROR, 'message' => 'network error', 'data' => array());
		}
	}

	public static function generateResUrl($userid = 0, $fileid = null, $oper = '') {
	    if ($fileid) {
	        if ($oper) {
	            return Conf::API_VIDEO_END_POINT . Conf::APPID . '/' . $userid . '/' . $fileid . '/' . $oper;
	        } else {
	            return Conf::API_VIDEO_END_POINT . Conf::APPID . '/' . $userid . '/' . $fileid;
	        }
	    } else {
	        return Conf::API_VIDEO_END_POINT . Conf::APPID . '/' . $userid;
	    }
	}

    /**
     * 分片上传文件,控制包/断点续传
     * @param  string  $filePath     本地文件路径
     * @param  integer $userid       用户自定义分类
     * @param  string  $title        视频标题
	 * @param  string  $desc         视频描述
	 * @param  string  $magicContext 自定义回调参数
	 * @param  string  $session 	 分片上传唯一标识，断点续传需要提供此参数
     * @return [type]                [description]
     */
	 function upload_prepare($filePath, $userid = 0,$title = '', $desc = '', $magicContext = '',$session = '') {

        $filePath = realpath($filePath);

		if (!file_exists($filePath)) {
			return array('httpcode' => 0, 'code' => self::VIDEO_FILE_NOT_EXISTS, 'message' => 'file '.$filePath.' not exists', 'data' => array());
		}

		$expired = time() + self::EXPIRED_SECONDS;
		$url = self::generateResUrl($userid);
		$sign = Auth::appSign($url, $expired);
		$sha1file = sha1_file($filePath);

		$data = array(
            'FileSize' => filesize($filePath),
			'op' => 'upload_slice',
			'sha' => $sha1file,
        );

        if ($title) {
        	$data['Title'] = $title;
        }
        if ($desc) {
        	$data['Desc'] = $desc;
        }
		if ($magicContext) {
        	$data['MagicContext'] = $magicContext;
        }
		if ($session) {
        	$data['session'] = $session;
        }
        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => 10,
            'data' => $data,
            'header' => array(
                'Authorization:'.$sign,
            ),
        );

		$rsp = Http::send($req);
		$info = Http::info();
		$ret = json_decode($rsp, true);
		if ($ret) {
			if (0 === $ret['code']) {
				$ret['httpcode'] = $info['http_code'];
				return $ret;
			} else {
				return array(
					'httpcode' => $info['http_code'],
					'code' => $ret['code'],
					'message' => $ret['message'],
					'data' => array()
				);
			}
		} else {
			return array(
					'httpcode' => $info['http_code'],
					'code' => self::VIDEO_NETWORK_ERROR,
					'message' => 'network error', 'data' => array()
				);
		}
	}

	    /**
     * 上传文件流
     * @param  integer $userid       用户自定义分类
     * @param  string  $file_data    文件内容
	 * @param  string  $session      上传唯一标识符
	 * @param  string  $offset 		 开始传输的位移
     * @return [type]                [description]
     */
	public static function upload_data($userid,$file_data,$session,$offset){
		$expired = time() + self::EXPIRED_SECONDS;
		$url = self::generateResUrl($userid);
		$sign = Auth::appSign($url, $expired);

		$data = array(
            'FileContent' => $file_data,
			'op' => 'upload_slice',
			'session' => $session,
			'offset' => $offset,
        );

        $req = array(
            'url' => $url,
            'method' => 'post',
            'timeout' => 10,
            'data' => $data,
            'header' => array(
                'Authorization:'.$sign,
            ),
        );

		$rsp = Http::send($req);
		$info = Http::info();
		$ret = json_decode($rsp, true);
		if ($ret) {
			if (0 === $ret['code']) {
				$ret['httpcode'] = $info['http_code'];
				return $ret;
			} else {
				return array(
					'httpcode' => $info['http_code'],
					'code' => $ret['code'],
					'message' => $ret['message'],
					'data' => array()
				);
			}
		} else {
			return array(
					'httpcode' => $info['http_code'],
					'code' => self::VIDEO_NETWORK_ERROR,
					'message' => 'network error', 'data' => array()
				);
		}
	}
//end of script
}

