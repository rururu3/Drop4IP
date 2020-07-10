<?php
namespace App\Inotify;

// Config周り
// https://github.com/hassankhan/config
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;
use Noodlehaus\Parser\Yaml;

// https://github.com/briannesbitt/Carbon
use Carbon\Carbon;

// 使うライブラリ
// Rx周り
// https://github.com/ReactiveX/RxPHP
use Rx\Observable;
use React\EventLoop\Factory;
use Rx\Scheduler;
use Rx\Subject\Subject;
use Rx\ObserverInterface;

use App\CAppLog;
use App\Drop\CDrop;

class CInotifyProcess {
  protected $drop;

  protected $config;
  protected $fd;
  protected $disposable;

  protected $inotifyFileList = [];

  /**
   * constructor
   */
  public function __construct(CDrop $drop, string $confFileName) {
    $this->drop = $drop;
    $this->config = new Config($confFileName);

    foreach($this->config->get('logs') as $fileName) {
      $this->inotifyFileList[] = new CInotifyFile($fileName);
    }

    $this->disposable = $this->fd = null;
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
    // inotify インスタンスを開きます
    $this->fd = inotify_init();

    $subject = $this->createSubject();
    foreach($this->inotifyFileList as $inotifyFile) {
      $inotifyFile->initialize($subject, $this->fd);
    }
  }

  /**
   * 破棄処理
   */
  public function destroy() : void {
    if(is_null($this->disposable) === false) {
      $this->disposable->dispose();
    }
    $this->disposable = null;

    foreach($this->inotifyFileList as $inotifyFile) {
      $inotifyFile->destroy();
    }
    $this->inotifyFileList = [];

    // メタデータ変更の監視を終了します
    if(is_null($this->fd) === false) {
      fclose($this->fd);
    }
    $this->fd = null;
  }

  /**
   * サービス名取得
   */
  public function getProcessName() : string {
    return($this->config->get('process'));
  }

  /**
   * 実行処理部分
   */
  public function run() : void {
    // ログだし
    CAppLog::getInstance()->debug($this->getProcessName() . " run");

    // 監視は1秒単位でいいや
    if(is_null($this->disposable) !== false) {
      $this->disposable = Observable::interval(1000)
      ->subscribe(function ($v) {
        // 待機中のイベントが有るか
        if(inotify_queue_len($this->fd) > 0) {
          $events = inotify_read($this->fd);
          $this->inotifyEvent($events);
        }
      });
    }
  }

  /**
   * サブジェクト作成
   */
  protected function createSubject() : ObserverInterface {
    $subject = new Subject();
    $subject->subscribe(function($v) {
      // ログだし
      CAppLog::getInstance()->debug($this->getProcessName() . ": {$v}");

      // 正規表現でフィルタ
      foreach($this->config->get('regexes') as $regexStr) {
        // 正規表現にマッチした
        if(preg_match($regexStr, $v, $matches) === 1) {
          // ipアドレスと日付取得
          $ipAdder = $matches['ip'];
          $date = empty($matches['date']) === false ?
                  Carbon::parse($matches['date'])->getTimestamp()
                  : Carbon::now()->getTimestamp();

          CAppLog::getInstance()->debug("ipAdder: {$ipAdder} date: {$date}");

          // 対象文字列がIPアドレス(IPv4とIPv6のプライベート領域および予約済み除く)
          if (filter_var($ipAdder, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
            // ログに追加する
            $this->drop->addLogs(
              $this->getProcessName(),
              $ipAdder,
              $date
            );

            // バンするのに必要な件数データが有るかチェック
            if($this->drop->checkAddBan(
              $this->getProcessName(),
              $ipAdder,
              Carbon::now()->sub(1, 'day')->getTimestamp(),   // TODO: 範囲は後で決める
              Carbon::now()->getTimestamp(),
              10  // TODO:件数@10は後で変える
              ) !== false) {
              // バンする
              foreach($this->config->get('protocols') as $protocol) {
                foreach($this->config->get('ports') as $port) {
                  foreach($this->config->get('rules') as $rule) {
                    $this->drop->addBan(
                      $this->getProcessName(),
                      $ipAdder,
                      $protocol,
                      $port,
                      $rule,
                      Carbon::now()->add(1, 'day')->getTimestamp()    // TODO: 期間は後で変える
                    );
                  }
                }
              }
            }
          }
        }
      }
    });
    return($subject);
  }

  /**
   * inotifyイベント用
   */
  public function inotifyEvent(array $events) : void {
    // 呼び出し用
    $_callList = [
      'IN_ACCESS' => IN_ACCESS,
      'IN_MODIFY' => IN_MODIFY,
      'IN_ATTRIB' => IN_ATTRIB,
      'IN_CLOSE_WRITE' => IN_CLOSE_WRITE,
      'IN_CLOSE_NOWRITE' => IN_CLOSE_NOWRITE,
      'IN_OPEN' => IN_OPEN,
      'IN_MOVED_FROM' => IN_MOVED_FROM,
      'IN_MOVED_TO' => IN_MOVED_TO,
      'IN_CREATE' => IN_CREATE,
      'IN_DELETE' => IN_DELETE,
      'IN_DELETE_SELF' => IN_DELETE_SELF,
      'IN_MOVE_SELF' => IN_MOVE_SELF,
      'IN_UNMOUNT' => IN_UNMOUNT,
      'IN_Q_OVERFLOW' => IN_Q_OVERFLOW,
      'IN_IGNORED' => IN_IGNORED,
      // 'IN_CLOSE' => IN_CLOSE,
      // 'IN_MOVE' => IN_MOVE,
    ];

    foreach($this->inotifyFileList as $inotifyFile) {
      foreach($events as $event) {
        if($inotifyFile->getWD() == $event['wd']) {
          // マスクに対応する関数を呼び出す
          foreach($_callList as $key => $value) {
            if($event['mask'] & $value) {
              // ログだし
              CAppLog::getInstance()->debug($this->getProcessName() . ": {$key} event");
              $inotifyFile->{$key}();
            }
          }
        }
      }
    }
  }
}