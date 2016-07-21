# tencentyun/image-php-sdk
腾讯云 [万象优图（Cloud Image）](https://www.qcloud.com/product/ci.html) SDK for PHP

## 安装（使用composer获取或者直接下载源码集成）

### 使用composer获取
php composer.phar require tencentyun/php-sdk
调用请参考示例1

### 直接下载源码集成
从github下载源码装入到您的程序中，并加载include.php
调用请参考示例2

## 修改配置
修改Tencentyun/Conf.php内的appid等信息为您的配置

## V2版本空间和自定义文件名的上传，分片上传、查询和删除示例
```php
require('./include.php');

use Tencentyun\ImageV2;
use Tencentyun\Auth;
use Tencentyun\Video;

//分片上传
$uploadSliceRet = ImageV2::uploadSlice('/tmp/tencent.jpg');
var_dump('uploadSlice',$uploadSliceRet);

// V2版本 带有空间和自定义文件名的示例
// 上传图片
$bucket = 'test2'; // 自定义空间名称，在http://console.qcloud.com/image/bucket创建
$fileid = 'sample'.time();  // 自定义文件名
$uploadRet = ImageV2::upload('/tmp/amazon.jpg', $bucket, $fileid);

var_dump('upload',$uploadRet);

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
    var_dump($signedUrl);

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
} else {
    var_dump($uploadRet);
}

```

## 智能鉴黄示例
```php
<?php

require('./include.php');

use Tencentyun\ImageProcess;
use Tencentyun\Auth;

// 智能鉴黄
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
```

## 图片上传并进行优图识别示例1
```php
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
```

## 图片上传、查询、删除程序示例1（使用composer安装后生成的autoload）
```php
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
```

## 图片上传、查询、删除程序示例2（使用tencentyun提供的include.php）
```php
<?php

//require('./vendor/autoload.php');

require('./include.php');

use Tencentyun\Image;
use Tencentyun\Auth;

// 上传
$uploadRet = Image::upload('/tmp/amazon.jpg');
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


## 视频上传、查询、删除程序示例1（使用composer安装后生成的autoload）
```php
require('./vendor/autoload.php');

use Tencentyun\Video;

// 上传
$uploadRet = Video::upload('./154631959.jpg');
if (0 === $uploadRet['code']) {
    $fileid = $uploadRet['data']['fileid'];

    // 查询管理信息
    $statRet = Video::stat($fileid);
    var_dump($statRet);

    $delRet = Video::del($fileid);
    var_dump($delRet);
}
```

## 视频上传、查询、删除程序示例2（使用tencentyun提供的include.php）
```php
<?php

//require('./vendor/autoload.php');

require('./include.php');

use Tencentyun\Video;
use Tencentyun\Auth;

// 上传
$uploadRet = Video::upload('/tmp/amazon.jpg');
if (0 === $uploadRet['code']) {
    $fileid = $uploadRet['data']['fileid'];

    // 查询管理信息
    $statRet = Video::stat($fileid);
    var_dump($statRet);

    // 生成私密下载url
    $downloadUrl = $copyRet['data']['downloadUrl'];
    $sign = Auth::appSign($downloadUrl, 0);
    $signedUrl = $downloadUrl . '?sign=' . $sign;
    var_dump($signedUrl);

    //生成新的上传签名
    $expired = time() + 999;
    $sign = Auth::appSign('http://web.video.myqcloud.com/videos/v1/200679/0/', $expired);
    var_dump($sign);

    $delRet = Video::del($fileid);
    var_dump($delRet);
} else {
    var_dump($uploadRet);
}
```
