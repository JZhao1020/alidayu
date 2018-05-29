# 阿里大鱼短信平台

## 开源地址
https://github.com/JZhao1020/alidayu

##1.安装
```
composer require hao/alidayu 
```

##2.实例化
```
$config = [
    'key_id' => '', // AccessKeyId
    'key_secret' => '',
    'sign_name' => '',//签名名称
    'code' => '',//模板CODE
];
$send = new \Aliyun\Send($config);
```

##2.1 发送单条短信
```
//发送短信
//短信参数内容
$data = [
    'code' => 12456,
    'product' => 'xxx'
];
$send->sendSms('手机号',$data);
```

##2.2 短信发送记录查询
```
$send->queryDetails('手机号','查询日期（20180520）');
```

##2.3 群发短信
```
$phone = [
    '手机号1',
    '手机号2'
];
//写法1（发送内容一致、短信签名一致）：
//短信参数内容
$data = [
    'code' => 12456,
    'product' => 'xxx'
];
$send->sendBatchSms($phone,$data);
#################################################
//写法2（发送内容不一致、短信签名一致）：
$data = [
    ['code' => 12456,'product' => 'xxx'],
    ['code' => 15555,'product' => 'xxx'],
];
$send->sendBatchSms($phone,$data);
##################################################
//写法3（发送内容不一致、短信签名不一致）：
$data = [
    ['code' => 12456,'product' => 'xxx'],
    ['code' => 15555,'product' => 'xxx'],
];
$sign = ['签名1','签名2'];
$send->sendBatchSms($phone,$data,$sign);
```

