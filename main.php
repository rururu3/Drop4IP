<?php
declare(ticks = 1);

// autoloadでライブラリ読み込み
require_once __DIR__ . '/vendor/autoload.php';


// アプリインスタンス取得
$app = \App\CApp::getInstance();

// 初期設定
$app->initialize();

// シグナルハンドラ関数
$callback = function($signo) use ($app) {
  switch ($signo) {
    case SIGTERM:
      // シャットダウンの処理
      break;
    case SIGHUP:
      // 再起動の処理
      break;
    case SIGINT:
      break;
    default:
      // それ以外のシグナルの処理
      break;
  }

  // 破棄
  $app->destroy();
};

// シグナル登録
pcntl_signal(SIGTERM, $callback);
pcntl_signal(SIGHUP,  $callback);
pcntl_signal(SIGINT, $callback);

// 実行
$app->run();
