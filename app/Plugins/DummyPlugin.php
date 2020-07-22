<?php
namespace App\Plugins;

// Config周り
// https://github.com/hassankhan/config
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;
use Noodlehaus\Parser\Yaml;

use App\IPlugin;

class DummyPlugin implements IPlugin {
  /**
   * constructor
   */
  public function __construct() {
    echo 'Load DummyPlugin' . PHP_EOL;
  } 

  /**
   * destruct
   */
  public function __destruct() {
    echo 'UnLoad DummyPlugin' . PHP_EOL;
  }

  /**
   * 初期処理
   */
  public function initialize(Config $pluginConfig) : void {
    echo 'initialize DummyPlugin' . PHP_EOL;
  }

  /**
   * 破棄処理
   */
  public function destroy() : void {
    echo 'destroy DummyPlugin' . PHP_EOL;
  }


  /**
   * ログに追加されたとき
   */
  public function addLogs(array $pluginArgv) {
    echo 'addLogs DummyPlugin' . PHP_EOL;
    var_dump($pluginArgv);
  }

  /**
   * バンに追加されたとき
   */
  public function addBan(array $pluginArgv) {
    echo 'addBan DummyPlugin' . PHP_EOL;
    var_dump($pluginArgv);
  }

  /**
   * バンを削除されたとき
   */
  public function removeBan(array $pluginArgv) {
    echo 'removeBan DummyPlugin' . PHP_EOL;
    var_dump($pluginArgv);
  }
}