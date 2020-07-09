<?php
namespace App;

use React\EventLoop\Factory;
use Rx\Scheduler;

class CApp {
  protected static $instance;

  protected $drop;

  protected $loop;
  
  protected $inotifyProcesseList = [];

  public static function getInstance() : CApp {
    return self::$instance ?? self::$instance = new self();
  }

  /**
   * constructor
   */
  protected function __construct() {
    $this->drop = new Drop\CDrop();

    // configフォルダに有るものを読み込む(アプリ用設定ファイル以外)
    foreach(glob('config/*.yml') as $file){
      if(preg_match('/(app.yml|apache.yml|postfix.yml)/i', $file, $m) === 0) {
        $this->inotifyProcesseList[] = new Inotify\CInotifyProcess($this->drop, $file);
      }
    }
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
    foreach($this->inotifyProcesseList as $process) {
      $process->initialize();
    }

    // You only need to set the default scheduler once
    $this->loop = Factory::create();
    Scheduler::setDefaultFactory(function() {
      return new Scheduler\EventLoopScheduler($this->loop);
    });

    $this->drop->initialize();
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

    // Rx
    $this->loop->run();
  }
}