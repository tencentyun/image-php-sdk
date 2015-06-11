# tencentyun-image-php
php sdk for [腾讯云万象图片服务](http://app.qcloud.com/image.html)

## 安装（使用composer获取或者直接下载源码集成）

### 使用composer获取
php composer.phar require tencentyun/php-sdk
调用请参考示例1

### 直接下载源码集成
从github下载源码装入到您的程序中，并加载include.php
调用请参考示例2

## 修改配置
修改Tencentyun/Conf.php内的appid等信息为您的配置

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