<?php
namespace App\Drop;

// https://github.com/briannesbitt/Carbon
use Carbon\Carbon;

// Config周り
// https://github.com/hassankhan/config
use Noodlehaus\Config;
use Noodlehaus\Parser\Json;
use Noodlehaus\Parser\Yaml;

use Rx\Observable;

class CDrop {
  protected $config;
  protected $database;
  protected $ipTables;

  protected $disposable;

  // キャッシュ用
  protected $cacheIPTables = [];

  /**
   * constructor
   */
  public function __construct() {
    $this->config = new Config('config/app.yml');

    $this->database = new CDatabase($this->config->get('dns'));
    $this->iptable = new CIPTables($this->config->get('chainName'));
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
    $this->database->initialize();
    $this->iptable->initialize();

    // キャッシュ削除
    $this->cacheIPTables = [];

    // 有効期限超えてるのを消す
    $this->database->removeEffectiveDateOverList(Carbon::now()->getTimestamp());

    // 現在あるデータでバンする
    $list = $this->database->getBanList();
    foreach($list as $v) {
      // iptablesに登録
      $this->iptable->addBanIP($v->source, $v->protocol, $v->port, $v->rule);

      // キャッシュに登録
      $this->cacheIPTables[$v->source][$v->protocol][$v->port][$v->rule] = 1;
    }

    // 監視は60秒単位でいいや
    $this->disposable = Observable::interval(60 * 1000)
    ->subscribe(function ($v) {
      // 時間による削除処理
      $date = Carbon::now()->add(10, 'day')->getTimestamp();
      $list = $this->database->getEffectiveDateOverList($date);
      foreach($list as $v) {
        $this->removeBan($v->source, $v->protocol, $v->port, $v->rule);
      }
      $this->database->removeEffectiveDateOverList($date);
    });
  }

  /**
   * 破棄処理
   */
  public function destroy() : void {
    $this->disposable->dispose();

    $this->database->destroy();
    $this->iptable->destroy();
  }

  /**
   * ログに追加する
   */
  public function addLogs(string $service, string $source, int $createDate) : void {
    // ログにデータを追加する
    $this->database->addLogData($service, $source, $createDate);
  }

  public function checkAddBan(string $service, string $source, int $fromDate, int $toDate, $needCount) : bool {
    // 指定期間でのデータ数を見る
    $count = $this->database->getLogDataCountBetween($service, $source, $fromDate, $toDate);
    return($count >= $needCount);
  }

  /**
   * バンに追加する
   */
  public function addBan(string $source, string $protocol, string $port, string $rule, int $effectiveDate) : void {
    // キャッシュになかったら処理をする
    if(isset($this->cacheIPTables[$source][$protocol][$port][$rule]) === false) {
      // 先にDBに登録
      $this->database->addBanData($source, $protocol, $port, $rule, $effectiveDate);

      // iptablesに登録
      $this->iptable->addBanIP($source, $protocol, $port, $rule);

      // キャッシュに乗せる
      $this->cacheIPTables[$source][$protocol][$port][$rule] = 1;
    }
  }

  /**
   * バンを削除する
   */
  public function removeBan(string $source, string $protocol, string $port, string $rule) : void {
    // 先にDBから削除
    $this->database->removeBanData($source, $protocol, $port, $rule);

    // iptablesから削除
    $this->iptable->removeBanIP($source, $protocol, $port, $rule);

    // キャッシュから消す
    unset($this->cacheIPTables[$source][$protocol][$port][$rule]);
  }
}
