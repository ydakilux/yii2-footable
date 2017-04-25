<?php

namespace ydakilux\footable;

use \Yii;

class FootableAsset extends \yii\web\AssetBundle
{
//    public $sourcePath = '@vendor/ydakilux/footable';
    public $sourcePath = '@bower/footable/';

    public $css = [
        'compiled/footable.standalone.css'
    ];

    public $js = [
        'compiled/footable.js'
    ];
    
    public $depends = [
        'yii\web\JqueryAsset'
    ];
}
