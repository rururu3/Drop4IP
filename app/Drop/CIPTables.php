<?php
namespace App\Drop;

use App\CAppLog;

// iptables
// http://web.mit.edu/rhel-doc/4/RH-DOCS/rhel-rg-ja-4/s1-iptables-options.html
class CIPTables {
  protected $iptables = "/sbin/iptables";
  protected $ip6tables = "/sbin/ip6tables";

  protected $processNameList;

  /**
   * constructor
   */
  public function __construct() {
    $this->processNameList = [];
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
  }

  /**
   * 破棄処理
   */
  public function destroy() : void {
    $keys = array_keys($this->processNameList);
    foreach($keys as $processName) {
      $this->removeProcessName($processName);
    }
  }

  /**
   * 処理するプロセス名を追加
   */
  public function addProcessName(string $processName) {
    if(empty($this->processNameList[$processName]) !== false) {
      $this->deleteChain($processName);
      $this->addChain($processName);
      $this->processNameList[$processName] = $processName;
    }
  }

  /**
   * 処理するプロセス名を削除
   */
  public function removeProcessName(string $processName) {
    if(empty($this->processNameList[$processName]) === false) {
      $this->deleteChain($processName);
    }
    unset($this->processNameList[$processName]);
  }

  /**
   * チェインを削除する
   */
  public function deleteChain(string $processName) : void {
    foreach([$this->iptables, $this->ip6tables] as $iptables) {
      // INPUTチェインからチェインを削除
      $this->execute("{$iptables} -D INPUT -j {$processName}");
      // チェインを削除
      $this->execute("{$iptables} -F {$processName}");
      $this->execute("{$iptables} -X {$processName}");
    }
  }

  /**
   * チェインを追加するする
   */
  public function addChain(string $processName) : void {
    foreach([$this->iptables, $this->ip6tables] as $iptables) {
      // チェインを追加する
      $this->execute("{$iptables} -N {$processName}");
      $this->execute("{$iptables} -A {$processName} -j RETURN");
      // INPUTチェインにチェインを追加(最初に)
      $this->execute("{$iptables} -I INPUT -j {$processName}");
    }
  }

  /**
   * 指定情報でiptablesに追加
   */
  public function addBanIP(string $processName, string $source, string $protocol, string $port, string $rule) : void {
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
        $this->execute("{$command} -I {$processName} --source {$source} --jump {$rule}");
      }
      else {
        $this->execute("{$command} -I {$processName} --source {$source} --proto {$protocol} --dport {$port} --jump {$rule}");
      }
    }
  }

  /**
   * 指定情報でiptablesから削除
   */
  public function removeBanIP(string $processName, string $source, string $protocol, string $port, string $rule) : void {
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
        $this->execute("{$command} -D {$processName} --source {$source} --jump {$rule}");
      }
      else {
        $this->execute("{$command} -D {$processName} --source {$source} --proto {$protocol} --dport {$port} --jump {$rule}");
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
