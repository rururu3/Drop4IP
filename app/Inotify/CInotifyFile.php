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

  public function initialize(ObserverInterface $subject, $fd) : void {
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

  public function destroy() : void {
    // メタデータ変更の監視を終了します
    inotify_rm_watch($this->fd, $this->wd);
    fclose($this->fp);
  }

  /**
   * wd取得
   */
  public function getWD() : int {
    return($this->wd);
  }

  //=====================================================================
  // IInotifyEventインターフェイス用
  //=====================================================================
  public function IN_ACCESS() : void {

  }
  public function IN_MODIFY() : void {
    // データがある分処理
    while(($buffer = fgets($this->fp, 8192)) !== false) {
      // trimをかけて改行を取り除く
      $this->subject->onNext(trim($buffer));
    }

    // これしないと複数回読み込んでくれない
    fseek($this->fp, 0, SEEK_END);
  }
  public function IN_ATTRIB() : void {
    
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
    
  }
  public function IN_MOVE_SELF() : void {
    
  }
  public function IN_UNMOUNT() : void {
    
  }
  public function IN_Q_OVERFLOW() : void {
    
  }
  public function IN_IGNORED() : void {
    
  }
  public function IN_CLOSE() : void {
    
  }
  public function IN_MOVE() : void {
    
  }
}