<?php
namespace App\Inotify;

// Config周り
// https://github.com/hassankhan/config
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;
use Noodlehaus\Parser\Yaml;

// https://github.com/briannesbitt/Carbon
use Carbon\Carbon;

use Rx\Subject\Subject;
use Rx\ObserverInterface;

use App\CAppLog;
use App\Drop\CDrop;

class CInotifyProcess {
  protected $drop;

  protected $config;

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
  }

  /**
   * destruct
   */
  public function __destruct() {
  }

  public function initialize($fd) : void {
    $subject = $this->createSubject();
    foreach($this->inotifyFileList as $inotifyFile) {
      $inotifyFile->initialize($subject, $fd);
    }
  }

  public function destroy() : void {
    foreach($this->inotifyFileList as $inotifyFile) {
      $inotifyFile->destroy();
    }
    $this->inotifyFileList = [];
  }

  /**
   * サブジェクト作成
   */
  protected function createSubject() : ObserverInterface {
    $subject = new Subject();
    $subject->subscribe(function($v) {
      // 正規表現でフィルタ
      foreach($this->config->get('regexes') as $regexStr) {
        // 正規表現にマッチした
        if(preg_match($regexStr, $v, $matches) === 1) {
          // 対象文字列がIPアドレス(IPv4とIPv6のプライベート領域および予約済み除く)
          if (filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
            // ログに追加する
            $this->drop->addLogs(
              $this->config->get('service'),
              $matches[1],
              Carbon::now()->getTimestamp()
            );

            // バンするのに必要な件数データが有るかチェック
            if($this->drop->checkAddBan(
              $this->config->get('service'),
              $matches[1],
              Carbon::now()->sub(1, 'day')->getTimestamp(),
              Carbon::now()->getTimestamp(),
              10  // TODO:件数@10は後で変える
              ) !== false) {
              // バンする
              foreach($this->config->get('protocols') as $protocol) {
                foreach($this->config->get('ports') as $port) {
                  foreach($this->config->get('rules') as $rule) {
                    $this->drop->addBan(
                      $matches[1],
                      $protocol,
                      $port,
                      $rule,
                      Carbon::now()->add(1, 'day')->getTimestamp()
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
      'IN_CLOSE' => IN_CLOSE,
      'IN_MOVE' => IN_MOVE,
    ];

    foreach($this->inotifyFileList as $inotifyFile) {
      foreach($events as $event) {
        if($inotifyFile->getWD() == $event['wd']) {
          // マスクに対応する関数を呼び出す
          foreach($_callList as $key => $value) {
            if($event['mask'] & $value) {
              CAppLog::getInstance()->debug($key);
              $inotifyFile->{$key}();
            }
          }
        }
      }
    }
  }
}