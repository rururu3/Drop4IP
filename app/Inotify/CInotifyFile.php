<?php
namespace App\Inotify;

use Rx\Observable;
use Rx\ObserverInterface;

use App\CAppLog;

class CInotifyFile implements IInotifyEvent {
  protected $fileName;

  protected $fd;
  protected $wd;
  protected $fp;
  protected $subject;

  protected $disposable;

  /**
   * constructor
   */
  public function __construct(string $fileName) {
    $this->fileName = $fileName;
  }
  
  /**
   * destruct
   */
  public function __destruct() {
  }

  /**
   * 初期処理
   */
  public function initialize(ObserverInterface $subject, $fd) : void {
    $this->subject = $subject;
    $this->fd = $fd;

    $this->disposable = null;

    // メタデータ (変更時刻など) の変更を監視します
    // 定数に関しては
    // https://www.php.net/manual/ja/inotify.constants.php
    $this->wd = inotify_add_watch($this->fd, $this->fileName, IN_ALL_EVENTS);

    // 最終ポインタ
    $this->fp = fopen($this->fileName, 'r');
    fseek($this->fp, 0, SEEK_END);
  }

  /**
   * 破棄処理
   */
  public function destroy() : void {
    if(is_null($this->disposable) === false) {
      $this->disposable->dispose();
    }

    // メタデータ変更の監視を終了します
    if(is_null($this->wd) === false) {
      inotify_rm_watch($this->fd, $this->wd);
    }
    if(is_null($this->fp) === false) {
      fclose($this->fp);
    }
    $this->wd = $this->fp = null;
  }

  /**
   * wd取得
   */
  public function getWD() {
    return($this->wd);
  }

  /**
   * ファイル読み込み処理
   */
  protected function readFile() : void {
    $list = [];

    // データがある分処理
    while(($buffer = fgets($this->fp, 8192)) !== false) {
      $list[] = trim($buffer);
    }

    // これしないと複数回読み込んでくれない
    fseek($this->fp, 0, SEEK_END);

    // データ数分処理
    foreach($list as $v) {
      $this->subject->onNext($v);
    }
  }

  /**
   * ファイル監視
   */
  protected function watchFile() : void {
    // メタデータ変更の監視を終了します
    if(is_null($this->wd) === false) {
      inotify_rm_watch($this->fd, $this->wd);
    }
    if(is_null($this->fp) === false) {
      fclose($this->fp);
    }
    $this->wd = $this->fp = null;

    // 監視中ではない
    if(is_null($this->disposable) !== false) {
      CAppLog::getInstance()->debug("{$this->fileName} is move or delete. wait around 5 seconds.");

      // 監視は5秒単位でいいや
      $this->disposable = Observable::interval(5 * 1000)
      ->subscribe(function ($v) {
        if(file_exists($this->fileName) !== false) {
          // dispose呼び出し＆nullを入れておく
          $this->disposable->dispose();
          $this->disposable = null;

          // 監視開始＆ファイルオープン
          $this->wd = inotify_add_watch($this->fd, $this->fileName, IN_ALL_EVENTS);
          $this->fp = fopen($this->fileName, 'r');

          // ファイル読み込み
          $this->readFile();
        }
      });
    }
  }

  //=====================================================================
  // IInotifyEventインターフェイス用
  //=====================================================================
  public function IN_ACCESS() : void {

  }
  public function IN_MODIFY() : void {
    // ファイル読み込み処理
    $this->readFile();
  }
  public function IN_ATTRIB() : void {
    // ファイルが消えたときに何故かこっちに来る？
    if(file_exists($this->fileName) === false) {
      // ファイルが消えたので監視する
      $this->watchFile();
    }
  }
  public function IN_CLOSE_WRITE() : void {
    
  }
  public function IN_CLOSE_NOWRITE() : void {
    
  }
  public function IN_OPEN() : void {
    
  }
  public function IN_MOVED_FROM() : void {
    
  }
  public function IN_MOVED_TO() : void {
    
  }
  public function IN_CREATE() : void {
    
  }
  public function IN_DELETE() : void {
    
  }
  public function IN_DELETE_SELF() : void {
    // ファイルが消えたので監視する
    $this->watchFile();
  }
  public function IN_MOVE_SELF() : void {
    // ファイルが移動したので監視する
    $this->watchFile();
  }
  public function IN_UNMOUNT() : void {
    
  }
  public function IN_Q_OVERFLOW() : void {
    
  }
  public function IN_IGNORED() : void {
    
  }
  // public function IN_CLOSE() : void {
    
  // }
  // public function IN_MOVE() : void {
    
  // }
}