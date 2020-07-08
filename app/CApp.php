<?php
namespace App;

// 使うライブラリ
// Rx周り
// https://github.com/ReactiveX/RxPHP
use Rx\Observable;
use React\EventLoop\Factory;
use Rx\Scheduler;
use Rx\Subject\Subject;
use Rx\ObserverInterface;

class CApp {
  protected static $instance;

  protected $drop;

  protected $fd;
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

  public function initialize() : void {
    // inotify インスタンスを開きます
    $this->fd = inotify_init();

    foreach($this->inotifyProcesseList as $process) {
      $process->initialize($this->fd);
    }

    // You only need to set the default scheduler once
    $this->loop = Factory::create();
    Scheduler::setDefaultFactory(function() {
      return new Scheduler\EventLoopScheduler($this->loop);
    });

    $this->drop->initialize();
  }

  public function destroy() : void {
    foreach($this->inotifyProcesseList as $process) {
      $process->destroy();
    }
    $this->inotifyProcesseList = [];

    // メタデータ変更の監視を終了します
    fclose($this->fd);
    
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
    CAppLog::getInstance()->debug('run');
    
    // 監視は1秒単位でいいや
    Observable::interval(1000)
    ->subscribe(function ($v) {
      // 待機中のイベントが有るか
      if(inotify_queue_len($this->fd) > 0) {
        $events = inotify_read($this->fd);
        foreach($this->inotifyProcesseList as $inotifyProcess) {
          $inotifyProcess->inotifyEvent($events);
        }
      }
    });

    $this->loop->run();
  }
}