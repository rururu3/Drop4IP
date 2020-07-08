<?php
// autoloadでライブラリ読み込み
require_once __DIR__ . '/vendor/autoload.php';

// アプリインスタンス取得
$app = \App\CApp::getInstance();

// 初期設定
$app->initialize();

// 実行
$app->run();

// 破棄
$app->destroy();
