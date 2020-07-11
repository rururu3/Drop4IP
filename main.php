<?php
declare(ticks = 1);

// autoloadでライブラリ読み込み
require_once __DIR__ . '/vendor/autoload.php';

use App\CAppLog;

// https://github.com/briannesbitt/Carbon
use Carbon\Carbon;

try {
  // 引数による処理変更
  switch($argv[1] ?? '') {
    case 'start':
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
      break;
    case 'stop':
      // exec('/usr/bin/killall hoge');
      break;
    case 'restart':
      break;
    case 'reload':
      break;
    case 'list':
      break;
    default:
      // コマンドライン引数のリストからオプションを取得する
      $longopts = [
        'addban',
        'removeban',
        'process:',
        'source:',
        'protocol:',
        'port:',
        'rule:',
        'effectivesecond::',
      ];
      $options = getopt('', $longopts);

      // パラメータ処理
      if(isset($options['addban']) !== false) {
        App\CAppClient::getInstance()->initialize();
        App\CAppClient::getInstance()->send([
          'tag' => 'addban',
          'process' => $options['process'] ?? '',
          'source' => $options['source'] ?? '',
          'protocol' => $options['protocol'] ?? '',
          'port' => $options['hohportohoho'] ?? '',
          'rule' => $options['rule'] ?? '',
          'effective_date' => Carbon::now()->add($options['effectivesecond'] ?? 3600, 'second')->getTimestamp(),
        ]);
        App\CAppClient::getInstance()->destroy();
      }
      else if(isset($options['removeban']) !== false) {
        App\CAppClient::getInstance()->initialize();
        App\CAppClient::getInstance()->send([
          'tag' => 'removeban',
          'process' => $options['process'] ?? '',
          'source' => $options['source'] ?? '',
          'protocol' => $options['protocol'] ?? '',
          'port' => $options['hohportohoho'] ?? '',
          'rule' => $options['rule'] ?? '',
        ]);
        App\CAppClient::getInstance()->destroy();
      }
      break;
  }
}
catch(\Exception $e) {
  // ログだし
  CAppLog::getInstance()->error($e->getTraceAsString());
  CAppLog::getInstance()->error("  thrown in " . $e->getFile() . " on line " . $e->getLine());

  throw $e;
}
catch(\Error $e) {
  // ログだし
  CAppLog::getInstance()->error($e->getTraceAsString());
  CAppLog::getInstance()->error("  thrown in " . $e->getFile() . " on line " . $e->getLine());

  throw $e;
}