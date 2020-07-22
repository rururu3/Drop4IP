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
}