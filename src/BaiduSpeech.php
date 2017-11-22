<?php

namespace eyunduan\aipSpeech;

use eyunduan\aipSpeech\libs\AipSpeech;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;

/**
 * author     : forecho <caizhenghai@gmail.com>
 * createTime : 2017/11/08 15:56
 * description:
 */
class BaiduSpeech extends Component
{
    /**
     * 切割个数 http://yuyin.baidu.com/docs/tts/136
     * 百度语音合成接口每次请求文本必须小于512个中文字或者英文数字
     * @var integer
     */
    const SLICE_LENGTH = 500;

    /**
     * 百度语音 App ID
     * @var string
     */
    public $appId;

    /**
     * 百度语音 API Key
     * @var string
     */
    public $apiKey;

    /**
     * 百度语音 Secret Key
     * @var string
     */
    public $secretKey;

    /**
     * [可选参数]储存路径
     * @var string
     */
    public $path;


    public function init()
    {
        if ($this->appId === null) {
            throw new InvalidConfigException('百度语音 App ID 必填');
        } elseif ($this->apiKey === null) {
            throw new InvalidConfigException('百度语音 API Key 必填');
        } elseif ($this->secretKey === null) {
            throw new InvalidConfigException('百度语音 Secret Key 必填');
        }

        $this->path = $this->path ?: $this->path = \Yii::getAlias('@runtime/audios/' . date('Ymd') . '/');
        FileHelper::createDirectory($this->path);
    }

    /**
     * 语音识别
     *
     * @param $filePath string 语音文件本地路径,优先使用此项
     * @param $url string 语音文件URL路径
     * @param $callback string 回调地址
     * @param $userID string 用户唯一标识
     * @param $format string 语音文件格式 ['pcm', 'wav', 'opus', 'speex', 'amr']
     * @param $rate integer 采样率 [8000, 16000]
     * @param $lan string 语音 ['zh', 'ct', 'en']
     * @return array
     */
    public function recognize($filePath, $url, $callback, $userID = null, $format = 'wav', $rate = 16000, $lan = 'zh')
    {
        $return = ['success' => false, 'msg' => '网络超时'];
        if (!$filePath && !$url) {
            $return['msg'] = '语音文件本地路径或URL路径需要至少提供一个';
            return $return;
        }
        if ($filePath && !file_exists($filePath)) {
            $return['msg'] = '语音文件路径错误';
            return $return;
        }
        if (!in_array($format, ['pcm', 'wav', 'opus', 'speex', 'amr'])) {
            $return['msg'] = '语音文件格式错误,当前支持以下格式:pcm（不压缩）、wav、opus、speex、amr';
            return $return;
        }
        if (!in_array($rate, [8000, 16000])) {
            $return['msg'] = '采样率错误，当前支持8000或者16000';
            return $return;
        }
        if (!in_array($lan, ['zh', 'ct', 'en'])) {
            $return['msg'] = '语言错误，当前支持中文(zh)、粤语(ct)、英文(en)';
            return $return;
        }
        $aipSpeech = new AipSpeech($this->appId, $this->apiKey, $this->secretKey);
        $options = [
            'lan' => $lan
        ];
        if (!$filePath && $url) {
            $options['url'] = $url;
        }
        if ($callback) {
            $options['callback'] = $callback;
        }
        if ($userID) {
            $options['cuid'] = $userID;
        }
        $response = $aipSpeech->asr($filePath ? file_get_contents($filePath) : null, $format, $rate, $options);
        if ($response['err_no'] == 0) {
            $return = [
                'success' => true,
                'msg' => '语音识别成功',
                'data' => $response['result']
            ];
        } else {
            $return['msg'] = '语音识别错误,错误码:' . $response['err_no'] . ',错误信息:' . $response['err_msg'];
        }
        return $return;
    }


    /**
     * 语音合成
     *
     * @param $text string 合成的文本
     * @param $userID string 用户唯一标识
     * @param $lan string 语音 ['zh']
     * @param $speed integer 语速，取值0-9，默认为5中语速
     * @param $pitch integer 音调，取值0-9，默认为5中语调
     * @param $volume integer 音量，取值0-15，默认为5中音量
     * @param $person integer 发音人选择, 0为女声，1为男声，3为情感合成-度逍遥，4为情感合成-度丫丫，默认为普通女
     * @param $fileName string 存储文件路径名称
     * @return array
     */
    public function combine($text, $userID = null, $lan = 'zh', $speed = 5, $pitch = 5, $volume = 5, $person = 0, $fileName = null)
    {
        $return = ['success' => false, 'msg' => '网络超时'];
        if (!$text) {
            $return['msg'] = '缺少合成的文本';
            return $return;
        }
        $text = strip_tags($text); // 去掉 HTML 标签
        $text = preg_replace("/&#?[a-z0-9]{2,8};/i", "", $text); // 去掉空格
        if ($speed < 0 || $speed > 9) {
            $return['msg'] = '语速错误';
            return $return;
        }
        if ($pitch < 0 || $pitch > 9) {
            $return['msg'] = '音调错误';
            return $return;
        }
        if ($volume < 0 || $volume > 15) {
            $return['msg'] = '音量错误';
            return $return;
        }
        if ($person < 0 || $person > 4) {
            $return['msg'] = '发音人错误';
            return $return;
        }
        $aipSpeech = new AipSpeech($this->appId, $this->apiKey, $this->secretKey);
        $options = [
            'lan' => $lan,
            'spd' => $speed,
            'pit' => $pitch,
            'vol' => $volume,
            'per' => $person
        ];
        if (!$userID) {
            $options['cuid'] = $userID;
        }

        $number = mb_strlen($text, 'UTF-8') / self::SLICE_LENGTH;
        if ($number > 1) {
            $response = '';
            for ($x = 0; $x <= $number; $x++) {
                $result = $aipSpeech->synthesis(mb_substr($text, $x * self::SLICE_LENGTH, self::SLICE_LENGTH, 'UTF-8'), $lan, 1, $options);
                if (is_array($result) && isset($result['err_no'])) {
                    $return['msg'] = '语音合成错误,错误码:' . $result['err_no'] . ',错误信息:' . $result['err_msg'];
                    return $return;
                }
                $response .= $result;
            }
        } else {
            // 小于512个中文字或者英文数字
            $response = $aipSpeech->synthesis($text, $lan, 1, $options);
        }
        if (!is_array($response)) {
            !$fileName && $fileName = uniqid() . '.mp3';
            $this->putFile($fileName, $response);
            $return = [
                'success' => true,
                'msg' => '语音合成成功',
                'data' => $this->path . $fileName
            ];
        } else {
            $return['msg'] = '语音合成错误,错误码:' . $response['err_no'] . ',错误信息:' . $response['err_msg'];
        }
        return $return;
    }

    /**
     * @param $fileName
     * @param $fileData
     */
    private function putFile($fileName, $fileData)
    {
        file_put_contents($this->path . $fileName, $fileData, FILE_APPEND);
    }
}