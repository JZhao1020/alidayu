<?php
// +----------------------------------------------------------------------
// | 阿里大鱼短信发送
// +----------------------------------------------------------------------
// | 版权所有
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/JZhao1020/alidayu
// +----------------------------------------------------------------------
namespace Aliyun;

use Aliyun\Core\Config as core_config;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Api\SendSmsRequest;
use Aliyun\Api\QuerySendDetailsRequest;
use Aliyun\Api\SendBatchSmsRequest;

class Send{
    static $acsClient = null;
    static $dasms;

    /**
     * @param array $config
     * @param string $config['key_id'] AccessKeyId
     * @param string $config['key_secret'] AccessKeyId
     * @param string $config['sign_name'] 签名名称
     * @param string $config['code'] 模板CODE
     */
    public function __construct($config = array()){
        self::$dasms = $config;

        // 加载区域结点配置
        core_config::load();
    }

    /**
     * 取得AcsClient
     *
     * @return DefaultAcsClient
     */
    public static function getAcsClient() {
        //产品名称:云通信流量服务API产品,开发者无需替换
        $product = "Dysmsapi";

        //产品域名,开发者无需替换
        $domain = "dysmsapi.aliyuncs.com";

        // TODO 此处需要替换成开发者自己的AK (https://ak-console.aliyun.com/)
        $accessKeyId = self::$dasms['key_id']; // AccessKeyId

        $accessKeySecret = self::$dasms['key_secret']; // AccessKeySecret
        // 暂时不支持多Region
        $region = "cn-hangzhou";
        // 服务结点
        $endPointName = "cn-hangzhou";
        if(static::$acsClient == null) {
            //初始化acsClient,暂不支持region化
            $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);
            // 增加服务结点
            DefaultProfile::addEndpoint($endPointName, $region, $product, $domain);
            // 初始化AcsClient用于发起请求
            static::$acsClient = new DefaultAcsClient($profile);
        }
        return static::$acsClient;
    }

    /**
     * 发送短信
     * @param string $phone 必填，手机号
     * @param array $data 必填，短信发送数据
     * @param string $signName 可空，签名名称
     * @param string $code 可空，模板code
     * @return true|object
     */
    public function sendSms($phone,$data,$signName = null,$code = null){
        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendSmsRequest();

        //可选-启用https协议
        //$request->setProtocol("https");

        // 必填，设置短信接收号码
        $request->setPhoneNumbers($phone);

        // 必填，设置签名名称，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        if(!isset($signName)){
            $signName = self::$dasms['sign_name'];
        }
        $request->setSignName($signName);

        // 必填，设置模板CODE，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        if(!isset($code)){
            $code = self::$dasms['code'];
        }
        $request->setTemplateCode($code);

        // 可选，设置模板参数, 假如模板中存在变量需要替换则为必填项
        $request->setTemplateParam(json_encode($data, JSON_UNESCAPED_UNICODE));

        // 可选，设置流水号
//        $request->setOutId("yourOutId");

        // 选填，上行短信扩展码（扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段）
//        $request->setSmsUpExtendCode("1234567");

        // 发起访问请求
        $acsResponse = static::getAcsClient()->getAcsResponse($request);
        if($acsResponse->Code == 'OK')
            return true;
        return $acsResponse;
    }

    /**
     * 短信发送记录查询
     *
     * @param string $phoneNumbers 必填, 短信接收号码 (e.g. 12345678901)
     * @param string $sendDate 必填，短信发送日期，格式Ymd，支持近30天记录查询 (e.g. 20170710)
     * @param int $pageSize 必填，分页大小
     * @param int $currentPage 必填，当前页码
     * @param string $bizId 选填，短信发送流水号 (e.g. abc123)
     * @return stdClass
     */
    public static function queryDetails($phoneNumbers, $sendDate, $pageSize = 10, $currentPage = 1, $bizId = null) {
        // 初始化QuerySendDetailsRequest实例用于设置短信查询的参数
        $request = new QuerySendDetailsRequest();
        // 必填，短信接收号码
        $request->setPhoneNumber($phoneNumbers);

        // 选填，短信发送流水号
        if(isset($bizId)) {
            $request->setBizId($bizId);
        }

        // 必填，短信发送日期，支持近30天记录查询，格式Ymd
        $request->setSendDate($sendDate);

        // 必填，分页大小
        $request->setPageSize($pageSize);

        // 必填，当前页码
        $request->setCurrentPage($currentPage);

        // 发起访问请求
        $acsResponse = static::getAcsClient()->getAcsResponse($request);
        // 打印请求结果
        return self::object2array($acsResponse);
    }

    /**
     * 群发短信
     * @param array $phoneNumbers 必填, 短信接收号码 ['18816781234','18816781235'...]
     * @param array $templateParam 必填，短信内容
     * @param array $signName 短信签名
     * @param string $code 模板CODE
     * @return stdClass
     */
    public function sendBatchSms(array $phoneNumbers,array $templateParam,$signName = array(),$code = null) {
        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendBatchSmsRequest();

        // 必填:待发送手机号。支持JSON格式的批量调用，批量上限为100个手机号码,批量调用相对于单条调用及时性稍有延迟,验证码类型的短信推荐使用单条调用的方式
        $request->setPhoneNumberJson(json_encode($phoneNumbers, JSON_UNESCAPED_UNICODE));

        $count = count($phoneNumbers);
        $count_sign = count($signName);
        if(is_array($signName) && $count_sign == 0) {
            $signName = array(self::$dasms['sign_name']);
        }

        if($count != $count_sign){
            $sign_array = array();
            for ($i = 0;$i < $count;$i++){
                $sign_array[$i] = $signName[0];
            }
            $signName = $sign_array;
        }


        // 必填:短信签名-支持不同的号码发送不同的短信签名
        $request->setSignNameJson(json_encode($signName, JSON_UNESCAPED_UNICODE));

        if(!isset($code)){
            $code = self::$dasms['code'];
        }

        // 必填:短信模板-可在短信控制台中找到
        $request->setTemplateCode($code);
        // 必填:模板中的变量替换JSON串,如模板内容为"亲爱的${name},您的验证码为${code}"时,此处的值为
        // 友情提示:如果JSON中需要带换行符,请参照标准的JSON协议对换行符的要求,比如短信内容中包含\r\n的情况在JSON中需要表示成\\r\\n,否则会导致JSON在服务端解析失败


        if(is_array($templateParam)){
            $param = false;
            foreach ($templateParam as $key => $val){
                if(is_array($val)){
                    $param = true;
                    break;
                }
            }

            if($param === false){
                $param_array = array();
                for ($i = 0;$i < $count;$i++){
                    $param_array[$i] = $templateParam;
                }
                $templateParam = $param_array;
            }
        }

        $request->setTemplateParamJson(json_encode($templateParam, JSON_UNESCAPED_UNICODE));
        // 发起访问请求
        $acsResponse = static::getAcsClient()->getAcsResponse($request);
        return self::object2array($acsResponse);
    }

    private static function object2array($object) {
        if (is_object($object)) {
            foreach ($object as $key => $value) {
                $array[$key] = $value;
            }
        }
        else {
            $array = $object;
        }
        return $array;
    }
}