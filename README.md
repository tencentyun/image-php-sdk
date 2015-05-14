# tencentyun-image-php
php sdk for [腾讯云万象图片服务](http://app.qcloud.com/image.html)

## 安装

### 使用composer
php composer.phar require tencentyun/php-sdk

### 下载源码
从github下载源码装入到您的程序中，并加载Tencentyun目录下的文件

## 修改配置
修改Tencentyun/Conf.php内的appid等信息为您的配置

## 图片上传、查询、删除程序示例（使用composer安装后生成的autoload）
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