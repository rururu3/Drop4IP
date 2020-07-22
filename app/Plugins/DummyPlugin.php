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
  } 

  /**
   * destruct
   */
  public function __destruct() {
  }

  /**
   * 初期処理
   */
  public function initialize(Config $pluginConfig) : void {
    echo 'Load DummyPlugin' . PHP_EOL;
  }

  /**
   * 破棄処理
   */
  public function destroy() : void {
    echo 'UnLoad DummyPlugin' . PHP_EOL;
  }
}