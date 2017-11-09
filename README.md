baidu ai speech for Yii2
========================

[![Latest Stable Version](https://poser.pugx.org/e-yunduan/yii2-aip-speech/v/stable)](https://packagist.org/packages/e-yunduan/yii2-aip-speech) 
[![Total Downloads](https://poser.pugx.org/e-yunduan/yii2-aip-speech/downloads)](https://packagist.org/packages/e-yunduan/yii2-aip-speech) 
[![Latest Unstable Version](https://poser.pugx.org/e-yunduan/yii2-aip-speech/v/unstable)](https://packagist.org/packages/e-yunduan/yii2-aip-speech) 
[![License](https://poser.pugx.org/e-yunduan/yii2-aip-speech/license)](https://packagist.org/packages/e-yunduan/yii2-aip-speech)


百度语音合成

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist e-yunduan/yii2-aip-speech "*"
```

or add

```
"e-yunduan/yii2-aip-speech": "*"
```

to the require section of your `composer.json` file.


Usage
-----


配置文件添加组件  :

```php
components => [
     'aipSpeech' => [
        'class' => 'eyunduan\aipSpeech\BaiduSpeech',
        'appId' => 'xxxx', // 百度语音 App ID
        'apiKey' => 'xxxx', // 百度语音 API Key
        'secretKey' => 'xxxxxx', // 百度语音 Secret Key
    ],
]
```

```php
/** @var \eyunduan\aipSpeech\BaiduSpeech $aipSpeech */
$aipSpeech = \Yii::$app->get('aipSpeech');
$aipSpeech->combine('您好，世界');
```