<?php
namespace App\Plugins;

use App\CAppLog;

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
    CAppLog::getInstance()->debug('__construct DummyPlugin');
  } 

  /**
   * destruct
   */
  public function __destruct() {
    CAppLog::getInstance()->debug('__destruct DummyPlugin');
  }

  /**
   * 初期処理
   */
  public function initialize(Config $pluginConfig) : void {
    CAppLog::getInstance()->debug('initialize DummyPlugin');
  }

  /**
   * 破棄処理
   */
  public function destroy() : void {
    CAppLog::getInstance()->debug('destroy DummyPlugin');
  }


  /**
   * ログに追加されたとき
   */
  public function addLogs(array $pluginArgv) {
    CAppLog::getInstance()->debug('addLogs DummyPlugin');
    CAppLog::getInstance()->debug(print_r($pluginArgv, true));
  }

  /**
   * バンに追加されたとき
   */
  public function addBan(array $pluginArgv) {
    CAppLog::getInstance()->debug('addBan DummyPlugin');
    CAppLog::getInstance()->debug(print_r($pluginArgv, true));
  }

  /**
   * バンを削除されたとき
   */
  public function removeBan(array $pluginArgv) {
    CAppLog::getInstance()->debug('removeBan DummyPlugin');
    CAppLog::getInstance()->debug(print_r($pluginArgv, true));
  }
}