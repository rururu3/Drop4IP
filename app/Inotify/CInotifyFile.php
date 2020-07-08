<?php
namespace App\Inotify;

use Rx\ObserverInterface;

class CInotifyFile implements IInotifyEvent {
  protected $fileName;

  protected $fd;
  protected $wd;
  protected $fp;
  protected $subject;

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

  public function initialize(ObserverInterface $subject, $fd) {
    $this->subject = $subject;
    $this->fd = $fd;

    // メタデータ (変更時刻など) の変更を監視します
    // 定数に関しては
    // https://www.php.net/manual/ja/inotify.constants.php
    $this->wd = inotify_add_watch($this->fd, $this->fileName, IN_ALL_EVENTS);

    // 最終ポインタ
    $this->fp = fopen($this->fileName, 'r');
    fseek($this->fp, 0, SEEK_END);
  }

  public function destroy() {
    // メタデータ変更の監視を終了します
    inotify_rm_watch($this->fd, $this->wd);
    fclose($this->fp);
  }

  /**
   * wd取得
   */
  public function getWD() {
    return($this->wd);
  }

  //=====================================================================
  // IInotifyEventインターフェイス用
  //=====================================================================
  public function IN_ACCESS() {

  }
  public function IN_MODIFY() {
    // データがある分処理
    while(($buffer = fgets($this->fp, 8192)) !== false) {
      // trimをかけて改行を取り除く
      $this->subject->onNext(trim($buffer));
    }

    // これしないと複数回読み込んでくれない
    fseek($this->fp, 0, SEEK_END);
  }
  public function IN_ATTRIB() {
    
  }
  public function IN_CLOSE_WRITE() {
    
  }
  public function IN_CLOSE_NOWRITE() {
    
  }
  public function IN_OPEN() {
    
  }
  public function IN_MOVED_FROM() {
    
  }
  public function IN_MOVED_TO() {
    
  }
  public function IN_CREATE() {
    
  }
  public function IN_DELETE() {
    
  }
  public function IN_DELETE_SELF() {
    
  }
  public function IN_MOVE_SELF() {
    
  }
  public function IN_UNMOUNT() {
    
  }
  public function IN_Q_OVERFLOW() {
    
  }
  public function IN_IGNORED() {
    
  }
  public function IN_CLOSE() {
    
  }
  public function IN_MOVE() {
    
  }
}