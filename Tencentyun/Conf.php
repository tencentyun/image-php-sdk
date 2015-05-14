<?php
namespace Tencentyun;

class Conf
{
    const PKG_VERSION = '1.0.*'; 

    const API_IMAGE_END_POINT = 'http://web.image.myqcloud.com/photos/v1/';

    const APPID = '200679';

    const SECRET_ID = 'AKIDoleG4e6U0j6EVQcjWXxzSO2Vv7Hqlgp2';

    const SECRET_KEY = 'ROlw3XYdNXNnII18ATs6zd7m5mivnApa';

    public static function getUA() {
        return 'QcloudPHP/'.self::PKG_VERSION.' ('.php_uname().')';
    }
}


//end of script