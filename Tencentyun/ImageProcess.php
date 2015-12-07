<?php

namespace Tencentyun;

class ImageProcess
{
    const TIME_OUT=10;
    
    /**
     * 智能鉴黄
     * @param  string  $pronDetectUrl     要进行黄图检测的图片url
     */
    public static function pornDetect($pronDetectUrl) {           
        $sign = Auth::getPornDetectSign($pronDetectUrl);
        if(false === $sign)
        {
            $data = array("code"=>9,
                    "message"=>"Secret id or key is empty.",
                    "data"=>array());
            
            return $data;
        }
        $data = array(
                'bucket'=>Conf::BUCKET,
                'appid'=>Conf::APPID,                
                'url'=>($pronDetectUrl));
        
        $reqData =  json_encode($data);
        $req = array(
                'url' => Conf::API_PRONDETECT_URL,
                'method' => 'post',
                'timeout' => self::TIME_OUT,
                'header' => array(
                        'Authorization:'.$sign,
                        'Content-Type:application/json',    
                ),
                'data' => $reqData,
        );

        $ret = Http::send($req);        

        return $ret;
    }
}






