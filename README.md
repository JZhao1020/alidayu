# Aliyun
阿里大鱼短信平台

##1.安装
```
composer require hao/alidayu 
```

##2.示例
```
$config = [
    'key_id' => '', // AccessKeyId
    'key_secret' => '',
    'sign_name' => '',//签名名称
    'code' => '',//模板CODE
];
$send = new \Aliyun\Send($config);
//发送短信
$result = $send->sendSms('手机号','发送内容');

//查询短信
$list = $send->queryDetails('手机号','查询日期（20180520）');

//群发短信

```
