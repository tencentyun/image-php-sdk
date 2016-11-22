<?php

//require('./vendor/autoload.php');

require('./include.php');

use Tencentyun\ImageV2;
use Tencentyun\Auth;
use Tencentyun\Video;
use Tencentyun\ImageProcess;
use Tencentyun\Conf;

//智能鉴黄，单个Url
$pornUrl = 'http://b.hiphotos.baidu.com/image/pic/item/8ad4b31c8701a18b1efd50a89a2f07082938fec7.jpg';
$pornRet = ImageProcess::pornDetect($pornUrl);
var_dump($pornRet);

//智能鉴黄，单个或多个图片Url
$pornUrl = array(
        'http://b.hiphotos.baidu.com/image/pic/item/8ad4b31c8701a18b1efd50a89a2f07082938fec7.jpg',
        'http://c.hiphotos.baidu.com/image/h%3D200/sign=7b991b465eee3d6d3dc680cb73176d41/96dda144ad3459829813ed730bf431adcaef84b1.jpg',
    );
$pornRet = ImageProcess::pornDetectUrl($pornUrl);
var_dump($pornRet);

//智能鉴黄，单个或多个图片File
$pornFile = array(
        'D:\porn\test1.jpg',
        '..\..\..\..\porn\test2.jpg',
        '..\..\..\..\porn\测试.png',
    );
$pornRet = ImageProcess::pornDetectFile($pornFile);
var_dump($pornRet);

// V2增强版空间 带有空间和自定义文件名的示例
// 上传图片
$bucket = Conf::BUCKET; // 自定义空间名称，在http://console.qcloud.com/image/bucket创建
$fileid = 'sample'.time();  // 自定义文件名
$uploadRet = ImageV2::upload('D:/IMAG0449.jpg', $bucket, $fileid);
var_dump('upload',$uploadRet);

//分片上传
$uploadSliceRet = ImageV2::uploadSlice('D:/IMAG0007.jpg');
var_dump('upload_slice',$uploadSliceRet);



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
    $expired = time() + 999;
    $sign = Auth::getAppSignV2($bucket, $fileid, $expired);
    $signedUrl = $downloadUrl . '?sign=' . $sign;
    var_dump('downloadUrl:', $signedUrl);

    //生成新的单次签名, 必须绑定资源fileid，复制和删除必须使用，其他不能使用
    $fileid = $fileid.time().rand();  // 自定义文件名
    $expired = 0;
    $sign = Auth::getAppSignV2($bucket, $fileid, $expired);
    var_dump($sign);

    //生成新的多次签名, 可以不绑定资源fileid
    $fileid = '';
    $expired = time() + 999;
    $sign = Auth::getAppSignV2($bucket, $fileid, $expired);
    var_dump($sign);

    //$delRet = ImageV2::del($bucket, $fileid);
    //var_dump($delRet);
}


//end of script