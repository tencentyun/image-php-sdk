<?php

//require('./vendor/autoload.php');

require('./include.php');

use Tencentyun\ImageV2;
use Tencentyun\Auth;
use Tencentyun\Video;
use Tencentyun\ImageProcess;

//智能鉴黄
$pornUrl = 'http://b.hiphotos.baidu.com/image/pic/item/8ad4b31c8701a18b1efd50a89a2f07082938fec7.jpg';
$pornRet = ImageProcess::pornDetect($pornUrl);
var_dump($pornRet);

// V2增强版空间 带有空间和自定义文件名的示例
// 上传图片
$bucket = 'zlktest'; // 自定义空间名称，在http://console.qcloud.com/image/bucket创建
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