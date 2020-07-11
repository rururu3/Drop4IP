<?php
namespace App;

use React\EventLoop\Factory;
use Rx\Scheduler;

// Config周り
// https://github.com/hassankhan/config
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;
use Noodlehaus\Parser\Yaml;

class CApp {
  protected static $instance;

  protected $drop;
  protected $server;
  protected $config;

  protected $loop;
  
  protected $inotifyProcesseList = [];

  public static function getInstance() : CApp {
    return self::$instance ?? self::$instance = new self();
  }

  /**
   * constructor
   */
  protected function __construct() {
    $this->config = new Config('config/app.yml');

    $this->drop = new Drop\CDrop();
  } 

  /**
   * destruct
   */
  public function __destruct() {
  }

  /**
   * 初期処理
   */
  public function initialize() : void {
    CAppServer::getInstance()->initialize($this->config->get('sock'));

    // configフォルダに有るものを読み込む(アプリ用設定ファイル以外)
    $processList = [];
    foreach(glob('config/*.yml') as $file){
      if(preg_match('/(app.yml)/i', $file, $m) === 0) {
        $process = new Inotify\CInotifyProcess($this->drop, $file);
        $process->initialize();

        $this->inotifyProcesseList[] = $process;
        $processList[] = $process->getProcessName();
      }
    }

    // You only need to set the default scheduler once
    $this->loop = Factory::create();
    Scheduler::setDefaultFactory(function() {
      return new Scheduler\EventLoopScheduler($this->loop);
    });

    $this->drop->initialize($processList);
  }

  /**
   * 破棄処理
   */
  public function destroy() : void {
    echo 'destoroy' . PHP_EOL;

    foreach($this->inotifyProcesseList as $process) {
      $process->destroy();
    }
    $this->inotifyProcesseList = [];
    
    $this->drop->destroy();

    CAppServer::getInstance()->destroy();
  }

  /**
   * アプリケーションのルートフォルダを返す
   */
  public function rootPath() : string {
    return(dirname(__DIR__));
  }

  /**
   * 実行処理部分
   */
  public function run() : void {
    CAppLog::getInstance()->debug('app run');
    
    // プロセス実行
    foreach($this->inotifyProcesseList as $process) {
      $process->run();
    }
    CAppServer::getInstance()->run();

    // Rx
    $this->loop->run();
  }
}