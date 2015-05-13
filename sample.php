<?php

require('./vendor/autoload.php');

use Tencentyun\Image;

// 上传
$uploadRet = Image::upload('./154631959.jpg');
if (0 === $uploadRet['code']) {
    $fileid = $uploadRet['data']['fileid'];

    // 查询管理信息
    $statRet = Image::stat($fileid);
    var_dump($statRet);

    $delRet = Image::del($fileid);
    var_dump($delRet);
}






//end of script