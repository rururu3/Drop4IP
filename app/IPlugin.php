<?php
namespace App;

// Config周り
use Noodlehaus\Config;

interface IPlugin {
  /**
   * 初期処理
   */
  public function initialize(Config $pluginConfig) : void;

  /**
   * 破棄処理
   */
  public function destroy() : void;

  /**
   * ログに追加されたとき
   */
  public function addLogs(array $pluginArgv);

  /**
   * バンに追加されたとき
   */
  public function addBan(array $pluginArgv);

  /**
   * バンを削除されたとき
   */
  public function removeBan(array $pluginArgv);
}