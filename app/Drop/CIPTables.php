<?php
namespace App\Drop;

use App\CAppLog;

// iptables
// http://web.mit.edu/rhel-doc/4/RH-DOCS/rhel-rg-ja-4/s1-iptables-options.html
class CIPTables {
  protected $chainName;
  protected $iptables = "/sbin/iptables";
  protected $ip6tables = "/sbin/ip6tables";

  /**
   * constructor
   */
  public function __construct(string $chainName) {
    $this->chainName = $chainName;
  }

  /**
   * destruct
   */
  public function __destruct() {
  }

  /**
   * iptablesのチェインを初期化(削除＆再作成)
   */
  public function initialize() : void {
    // 削除＆再作成
    $this->deleteChain($this->chainName);
    $this->addChain($this->chainName);
  }

  /**
   * 破棄処理
   */
  public function destroy() : void {
    $this->deleteChain($this->chainName);
  }

  /**
   * チェインを削除する
   */
  public function deleteChain(string $chainName) : void {
    foreach([$this->iptables, $this->ip6tables] as $iptables) {
      // INPUTチェインからチェインを削除
      $this->execute("{$iptables} -D INPUT -j {$chainName}");
      // チェインを削除
      $this->execute("{$iptables} -F {$chainName}");
      $this->execute("{$iptables} -X {$chainName}");
    }
  }

  /**
   * チェインを追加するする
   */
  public function addChain(string $chainName) : void {
    foreach([$this->iptables, $this->ip6tables] as $iptables) {
      // チェインを追加する
      $this->execute("{$iptables} -N {$chainName}");
      $this->execute("{$iptables} -A {$chainName} -j RETURN");
      // INPUTチェインにチェインを追加(最初に)
      $this->execute("{$iptables} -I INPUT -j {$chainName}");
    }
  }

  /**
   * 指定情報でiptablesに追加
   */
  public function addBanIP(string $source, string $protocol, string $port, string $rule) : void {
    $command = "";
    if (filter_var($source, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
      // IPv4処理
      $command = $this->iptables;
    }
    else if (filter_var($source, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
      // IPv6処理
      $command = $this->ip6tables;
    }

    if(empty($command) === false) {
      $this->execute("{$command} -I {$this->chainName} --source {$source} --proto {$protocol} --dport {$port} --jump {$rule}");
    }
  }

  /**
   * 指定情報でiptablesから削除
   */
  public function removeBanIP(string $source, string $protocol, string $port, string $rule) : void {
    $command = "";
    if (filter_var($source, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
      // IPv4処理
      $command = $this->iptables;
    }
    else if (filter_var($source, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
      // IPv6処理
      $command = $this->ip6tables;
    }

    if(empty($command) === false) {
      $this->execute("{$command} -D {$this->chainName} --source {$source} --proto {$protocol} --dport {$port} --jump {$rule}");
    }
  }

  protected function readIPTables() : void {
    $handle = popen("/sbin/iptables -L {$this->chainName} -n", "r");
    // リソース$PPから一行ずつ読み込む
    while (($str = fgets($handle)) !== false) {
      $str = trim($str);
    }
    pclose($handle);
  }

  /**
   * system関数実行
   */
  protected function execute(string $command) : void {
    CAppLog::getInstance()->debug($command);
    system($command);
  }
}
