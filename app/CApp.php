<?php
namespace App;

use React\EventLoop\Factory;
use Rx\Scheduler;

// Config周り
// https://github.com/hassankhan/config
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;
use Noodlehaus\Parser\Yaml;

class CApp {
  protected static $instance;

  protected $drop;
  protected $server;
  protected $config;

  protected $loop;
  
  protected $inotifyProcesseList;
  protected $pluginClassNameList;

  public static function getInstance() : CApp {
    return self::$instance ?? self::$instance = new self();
  }

  /**
   * constructor
   */
  protected function __construct() {
    $this->config = new Config('config/app.yml');

    $this->drop = new Drop\CDrop();

    $this->inotifyProcesseList = [];
    $this->pluginClassNameList = [];
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
    CAppServer::getInstance()->initialize($this->config->get('sock'), $this->drop);

    // You only need to set the default scheduler once
    $this->loop = Factory::create();
    Scheduler::setDefaultFactory(function() {
      return new Scheduler\EventLoopScheduler($this->loop);
    });

    $this->drop->initialize();

    // configフォルダに有るものを読み込む(アプリ用設定ファイル以外)
    foreach(glob('config/*.yml') as $confFileName){
      if(preg_match('/(app.yml|plugin.yml)/i', $confFileName, $m) === 0) {
        $this->addProcessFromConfig(new Config($confFileName));
      }
    }

    // プラグイン読み込み
    $pluginConfig = new Config('config/plugin.yml');
    foreach(glob('app/Plugins/*.php') as $pluginFileName) {
      $this->addPlugin(basename($pluginFileName, '.php'), $pluginConfig);
    }
  }

  /**
   * 破棄処理
   */
  public function destroy() : void {
    echo 'destoroy' . PHP_EOL;

    $processNameList = array_keys($this->inotifyProcesseList);
    foreach($processNameList as $processName) {
      $this->removeProcessName($processName);
    }

    $keys = array_keys($this->pluginClassNameList);
    foreach($keys as $pluginClassName) {
      $this->removePlugin($pluginClassName);
    }
    
    $this->drop->destroy();

    CAppServer::getInstance()->destroy();
  }

  /**
   * プロセスを追加
   */
  protected function addProcessFromConfig(Config $config) {
    $processName = $config->get('process_name');

    if(empty($this->inotifyProcesseList[$processName]) !== false) {
      $process = new Inotify\CInotifyProcess($this->drop, $config);
      $process->initialize();

      $this->drop->addProcessName($processName);

      $this->inotifyProcesseList[$processName] = $process;
    }
  }

  /**
   * プロセス削除
   */
  protected function removeProcessName(string $processName) {
    if(empty($this->inotifyProcesseList[$processName]) === false) {
      $this->inotifyProcesseList[$processName]->destroy();
      $this->drop->removeProcessName($processName);
    }
    unset($this->inotifyProcesseList[$processName]);
  }

  /**
   * プラグイン読み込み
   */
  protected function addPlugin(string $pluginClassName, Config $pluginConfig) {
    if(empty($this->pluginClassNameList[$pluginClassName]) !== false) {
      $pluginClassName = "App\\Plugins\\{$pluginClassName}";
      $this->pluginClassNameList[$pluginClassName] = new $pluginClassName();
      $this->pluginClassNameList[$pluginClassName]->initialize($pluginConfig);
    }
  }

  /**
   * プラグイン削除
   */
  protected function removePlugin(string $pluginClassName) {
    if(empty($this->pluginClassNameList[$pluginClassName]) === false) {
      $this->pluginClassNameList[$pluginClassName]->destroy();
    }
    unset($this->pluginClassNameList[$pluginClassName]);
  }

  /**
   * プラグインに処理を実行させる
   */
  public function executePluginFunction(string $functionName, array $pluginArgv) {
    foreach($this->pluginClassNameList as $plugin) {
      $plugin->{$functionName}($pluginArgv);
    }
  }

  /**
   * アプリケーションのルートフォルダを返す
   */
  public function rootPath() : string {
    return(dirname(__DIR__));
  }

  /**
   * 実行処理部分
   */
  public function run() : void {
    CAppLog::getInstance()->debug('app run');
    
    // プロセス実行
    foreach($this->inotifyProcesseList as $process) {
      $process->run();
    }
    CAppServer::getInstance()->run();

    // Rx
    $this->loop->run();
  }
}