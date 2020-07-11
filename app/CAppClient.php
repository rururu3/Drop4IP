<?php
namespace App;

// Config周り
// https://github.com/hassankhan/config
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;
use Noodlehaus\Parser\Yaml;

class CAppClient {
  protected static $instance;

  protected $socket;
  protected $config;

  public static function getInstance() : CAppClient {
    return self::$instance ?? self::$instance = new self();
  }

  /**
   * constructor
   */
  protected function __construct() {
    $this->socket = null;
    $this->config = new Config('config/app.yml');
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
    // ソケット作成
    // https://www.php.net/manual/ja/function.socket-create.php
    $this->socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);

    // ノンブロッキングにする
    if (socket_set_nonblock($this->socket) === false) {
      echo socket_last_error() . PHP_EOL;
    }

    // ノンブロッキングにする
    if (socket_connect($this->socket, $this->config->get('sock')) === false) {
      echo socket_last_error() . PHP_EOL;
    }
  }

  /**
   * 破棄処理
   */
  public function destroy() : void {
    // ソケットクローズ
    if(is_null($this->socket) === false) {
      socket_close($this->socket);
    }
    $this->socket = null;
  }

  /**
   * 実行中のプロセスにメッセージ送信する
   */
  public function send(array $arr) {
    $jsonEncode = json_encode($arr);
    // ソケット内容読み込み
    if(socket_send($this->socket, $jsonEncode, strlen($jsonEncode), 0) === false) {
      echo socket_last_error() . PHP_EOL;
    }
  }
}