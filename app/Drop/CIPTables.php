<?php
namespace App\Drop;

use App\CAppLog;

// iptables
// http://web.mit.edu/rhel-doc/4/RH-DOCS/rhel-rg-ja-4/s1-iptables-options.html
class CIPTables {
  protected $iptables = "/sbin/iptables";
  protected $ip6tables = "/sbin/ip6tables";

  protected $processList;

  /**
   * constructor
   */
  public function __construct() {
    $this->processList = [];
  }

  /**
   * destruct
   */
  public function __destruct() {
  }

  /**
   * 初期処理
   */
  public function initialize(array $processList) : void {
    $this->processList = $processList;

    // 削除＆再作成
    foreach($this->processList as $process) {
      $this->deleteChain($process);
      $this->addChain($process);
    }
  }

  /**
   * 破棄処理
   */
  public function destroy() : void {
    foreach($this->processList as $process) {
      $this->deleteChain($process);
    }
  }

  /**
   * チェインを削除する
   */
  public function deleteChain(string $process) : void {
    foreach([$this->iptables, $this->ip6tables] as $iptables) {
      // INPUTチェインからチェインを削除
      $this->execute("{$iptables} -D INPUT -j {$process}");
      // チェインを削除
      $this->execute("{$iptables} -F {$process}");
      $this->execute("{$iptables} -X {$process}");
    }
  }

  /**
   * チェインを追加するする
   */
  public function addChain(string $process) : void {
    foreach([$this->iptables, $this->ip6tables] as $iptables) {
      // チェインを追加する
      $this->execute("{$iptables} -N {$process}");
      $this->execute("{$iptables} -A {$process} -j RETURN");
      // INPUTチェインにチェインを追加(最初に)
      $this->execute("{$iptables} -I INPUT -j {$process}");
    }
  }

  /**
   * 指定情報でiptablesに追加
   */
  public function addBanIP(string $process, string $source, string $protocol, string $port, string $rule) : void {
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
      if(strtolower($protocol) === 'all' && strtolower($protocol) === 'all') {
        $this->execute("{$command} -I {$process} --source {$source} --jump {$rule}");
      }
      else {
        $this->execute("{$command} -I {$process} --source {$source} --proto {$protocol} --dport {$port} --jump {$rule}");
      }
    }
  }

  /**
   * 指定情報でiptablesから削除
   */
  public function removeBanIP(string $process, string $source, string $protocol, string $port, string $rule) : void {
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
      if(strtolower($protocol) === 'all' && strtolower($protocol) === 'all') {
        $this->execute("{$command} -D {$process} --source {$source} --jump {$rule}");
      }
      else {
        $this->execute("{$command} -D {$process} --source {$source} --proto {$protocol} --dport {$port} --jump {$rule}");
      }
    }
  }

  // protected function readIPTables() : void {
  //   $handle = popen("/sbin/iptables -L {$this->chainName} -n", "r");
  //   // リソース$PPから一行ずつ読み込む
  //   while (($str = fgets($handle)) !== false) {
  //     $str = trim($str);
  //   }
  //   pclose($handle);
  // }

  /**
   * system関数実行
   */
  protected function execute(string $command) : void {
    $start = hrtime(true);
    CAppLog::getInstance()->debug($command);
    system($command);
    CAppLog::getInstance()->debug(__CLASS__ . ':' . __FUNCTION__ . " miliseconds: " . (hrtime(true) - $start) / (1000 * 1000));
  }
}
