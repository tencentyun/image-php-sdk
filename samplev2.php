<?php

//require('./vendor/autoload.php');

require('./include.php');

use Tencentyun\ImageV2;
use Tencentyun\Auth;
use Tencentyun\Video;

// V2版本 带有空间和自定义文件名的示例
// 上传图片
$bucket = 'test1'; // 自定义空间名称，在http://console.qcloud.com/image/bucket创建
$fileid = 'sample'.time();  // 自定义文件名
$uploadRet = ImageV2::upload('/tmp/amazon.jpg', $bucket, $fileid);

if (0 === $uploadRet['code']) {
    $fileid = $uploadRet['data']['fileid'];
    $downloadUrl = $uploadRet['data']['downloadUrl'];

    // 查询管理信息
    $statRet = ImageV2::stat($bucket, $fileid);
    var_dump('stat',$statRet);
    // 复制
    $copyRet = ImageV2::copy($bucket, $fileid);
    var_dump('copy', $copyRet);

    // 生成私密下载url
    $sign = Auth::appSignV2($downloadUrl);
    $signedUrl = $downloadUrl . '?sign=' . $sign;
    var_dump($signedUrl);

    //生成新的上传签名
    $expired = time() + 999;
    $sign = Auth::appSignV2('http://test1-10000002.image.myqcloud.com/test1-10000002/0/sample1436341553/', $expired);
    var_dump($sign);

    //$delRet = ImageV2::del($bucket, $fileid);
    //var_dump($delRet);
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
$fileid = 'sample'.time();
$uploadRet = ImageV2::upload('/tmp/amazon.jpg', $bucket, $fileid, $userid, $magicContext, array('get'=>$gets));
var_dump($uploadRet);


//end of script