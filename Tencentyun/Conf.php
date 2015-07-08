<?php
namespace Tencentyun;

class Conf
{
    const PKG_VERSION = '1.3.0'; 

    const API_IMAGE_END_POINT = 'http://web.image.myqcloud.com/photos/v1/';

    const API_IMAGE_END_POINT_V2 = 'http://web.image.myqcloud.com/photos/v2/';

	const API_VIDEO_END_POINT = 'http://web.video.myqcloud.com/videos/v1/';
		
    const APPID = '10000002';

    const SECRET_ID = 'AKIDL5iZVplWMenB5Zrx47X78mnCM3F5xDbC';

    const SECRET_KEY = 'Lraz7n2vNcyW3tiP646xYdfr5KBV4YAv';

    public static function getUA() {
        return 'QcloudPHP/'.self::PKG_VERSION.' ('.php_uname().')';
    }
}


//end of script