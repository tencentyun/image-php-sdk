<?php

//require('./vendor/autoload.php');

require('./include.php');

use Tencentyun\Image;
use Tencentyun\Auth;
use Tencentyun\Video;

// 上传图片
$uploadRet = Image::upload('/tmp/0f0000vvCL2tnMfkFprLPf.jpg');
if (0 === $uploadRet['code']) {
    $fileid = $uploadRet['data']['fileid'];

    // 查询管理信息
    $statRet = Image::stat($fileid);
    var_dump($statRet);

    // 复制
    $copyRet = Image::copy($fileid);
    var_dump($copyRet);

    // 生成私密下载url
    $downloadUrl = $copyRet['data']['downloadUrl'];
    $sign = Auth::appSign($downloadUrl, 0);
    $signedUrl = $downloadUrl . '?sign=' . $sign;
    var_dump($signedUrl);

    //生成新的上传签名
    $expired = time() + 999;
    $sign = Auth::appSign('http://web.image.myqcloud.com/photos/v1/200679/0/', $expired);
    var_dump($sign);

    $delRet = Image::del($fileid);
    var_dump($delRet);
} else {
    var_dump($uploadRet);
}

// 上传指定进行优图识别  fuzzy（模糊识别），food(美食识别）
// 如果要支持模糊识别，url?analyze=fuzzy
// 如果要同时支持模糊识别和美食识别，url?analyze=fuzzy.food
// 返回数据中
// "isFuzzy" 1 模糊 0 清晰
// "isFood" 1 美食 0 不是
$userid = 0;
$magicContext = '';
$gets = array(
    'analyze' => 'fuzzy.food'
);
$uploadRet = Image::upload('/tmp/20150624100808134034653.jpg',$userid,$magicContext,array('get'=>$gets));
var_dump($uploadRet);


// 上传视频
$uploadRet = Video::upload('c:/pic/0.jpg');
if (0 === $uploadRet['code']) {
    $fileid = $uploadRet['data']['fileid'];

    // 查询管理信息
    $statRet = Video::stat($fileid);
    var_dump($statRet);

    //生成新的上传签名
    $expired = time() + 999;
    $sign = Auth::appSign('http://web.video.myqcloud.com/videos/v1/200679/0/', $expired);
    var_dump($sign);

    $delRet = Video::del($fileid);
    var_dump($delRet);
} else {
    var_dump($uploadRet);
}



//end of script
