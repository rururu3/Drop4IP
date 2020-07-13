<?php
declare(ticks = 1);

// autoloadでライブラリ読み込み
require_once __DIR__ . '/vendor/autoload.php';

// Config周り
// https://github.com/hassankhan/config
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;
use Noodlehaus\Parser\Yaml;

use App\CAppLog;

// https://github.com/briannesbitt/Carbon
use Carbon\Carbon;

/**
 * 開始処理
 */
function commandStart(Config $config) {
  file_put_contents($config->get('pid'), getmypid());

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
}

/**
 * 終了処理
 */
function commandStop(Config $config) {
  if(($pid = file_get_contents($config->get('pid'))) !== false) {
    // ban4ipdプロセスをすべてkillする
    exec('/bin/kill -TERM ' . $pid);

    // 少し待ってから
    sleep(2);

    // プロセスIDが存在したら消す
    if(file_exists($config->get('pid')) !== false) {
      unlink($config->get('pid'));
    }
    // ソケットファイルがあれば
    if(file_exists($config->get('sock')) !== false) {
      unlink($config->get('sock'));
    }
    echo <<< EOM
ban4ipd stop

EOM;
  }
}

/**
 * 再起動
 */
function commandRestart(Config $config) {

}

/**
 * 再読み込み
 */
function commandReload(Config $config) {

}

/**
 * コマンドリスト
 */
function commandList(Config $config) {

}

/**
 * 引数に当てはまらないとき
 */
function commandDefault(Config $config) {
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

  $output =<<< EOM
DROP for IP Controller

Usage:

  drop4ip start    ... drop4ip start.
  drop4ip stop     ... drop4ip stop.
  drop4ip restart  ... drop4ip stop and start.
  drop4ip reload   ... reload config file.
  drop4ip list     ... output banned IPs list.

  drop4ip --addban --process postfix --source <IP> --protocol all --port all --rule DROP --effectivesecond <SECOND>
  drop4ip --removeban --process postfix --source <IP> --protocol all --port all --rule DROP
      
      <IP>         ... IPv4 or IPv6 address.
      <PROCESS>    ... set "config service" name
      <SECOND>     ... effective seconds from now

Harasawa Naoya(rururu3)
  
EOM;
  
  // パラメータ処理
  if(file_exists($config->get('pid')) !== false) {
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
    else {
      echo $output;
    }
  }
  else {
    echo $output;
  }
}

try {
  $config = new Config('config/app.yml');

  // 引数による処理変更
  switch($argv[1] ?? '') {
    case 'start':
      if(file_exists($config->get('pid')) !== false) {
        echo <<< EOM
other drop4ip process found.

EOM;
      }
      else {
        commandStart($config);
      }
      break;
    case 'stop':
      if(file_exists($config->get('pid')) !== false) {
        commandStop($config);
      }
      break;
    case 'restart':
      commandRestart($config);
      break;
    case 'reload':
      commandReload($config);
      break;
    case 'list':
      commandList($config);
      break;
    default:
      commandDefault($config);
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