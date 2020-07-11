<?php
namespace App;

use Rx\Observable;

// https://github.com/briannesbitt/Carbon
use Carbon\Carbon;

class CAppServer {
  protected static $instance;

  protected $socket;
  protected $sockFileName;

  public static function getInstance() : CAppServer {
    return self::$instance ?? self::$instance = new self();
  }

  /**
   * constructor
   */
  protected function __construct() {
    $this->socket = $this->disposable = null;
  } 

  /**
   * destruct
   */
  public function __destruct() {
  }

  /**
   * 初期処理
   */
  public function initialize(string $sockFileName, Drop\CDrop $drop) : void {
    $this->sockFileName = $sockFileName;
    $this->drop = $drop;

    // ソケット作成
    // https://www.php.net/manual/ja/function.socket-create.php
    $this->socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);

    // ソケットバインド
    if(file_exists($this->sockFileName) !== false) {
      unlink($this->sockFileName);
    }
    if(socket_bind($this->socket, $this->sockFileName) === false) {
      echo socket_last_error() . PHP_EOL;
    }

    // ノンブロッキングにする
    if (socket_set_nonblock($this->socket) === false) {
      echo socket_last_error() . PHP_EOL;
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

    // sockファイルを消す
    if(file_exists($this->sockFileName) !== false) {
      unlink($this->sockFileName);
    }
    // ソケットクローズ
    if(is_null($this->socket) === false) {
      socket_close($this->socket);
    }
    $this->socket = null;
  }

  /**
   * 実行処理部分
   */
  public function run() {
    // 監視は1秒単位でいいや
    $this->disposable = Observable::interval(1 * 1000)
    ->subscribe(function ($v) {
      // ソケット内容読み込み
      $read = [$this->socket];
      $write = null;
      $except = null;

      if(socket_select($read, $write, $except, 0) > 0) {
        // 変更があった
        $buffer = '';
        $port = null;
    
        if(socket_recvfrom($this->socket, $buffer, 4096, 0, $port) === false) {
          echo socket_last_error() . PHP_EOL;
        }
        else {
          if(($json = json_decode($buffer, false)) !== null) {
            switch(strtolower($json->tag ?? '')) {
              case 'addban':
                if (filter_var($json->source ?? '', FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                  $this->drop->addBan(
                    $json->process,
                    $json->source,
                    $json->protocol ?? 'all',
                    $json->port ?? 'all',
                    $json->rule ?? 'DROP',
                    $json->effective_date ?? Carbon::now()->getTimestamp(),
                  );
                }
                break;
              case 'removeban':
                if (filter_var($json->source ?? '', FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                  $this->drop->removeBan(
                    $json->process,
                    $json->source,
                    $json->protocol ?? 'all',
                    $json->port ?? 'all',
                    $json->rule ?? 'DROP'
                  );
                }
                break;
              default:
                break;
            }
          }
        }
      }
    });
  }
}