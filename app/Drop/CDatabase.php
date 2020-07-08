<?php
namespace App\Drop;

class CDatabase {
  protected $pdo;
  /**
   * constructor
   */
  public function __construct($dns) {
    $this->pdo = new \PDO($dns);
  }

  /**
   * destruct
   */
  public function __destruct() {
  }

  public function initialize() {
    // テーブル作成(banされたデータ用)
    $sql = <<< EOM
CREATE TABLE IF NOT EXISTS "bans" (
  "id"              INTEGER,
  "source"          TEXT,
  "protocol"        TEXT,
  "port"            TEXT,
  "rule"            TEXT,
  "effective_date"  INTEGER,
  PRIMARY KEY("id" AUTOINCREMENT)
);
EOM;
    $this->pdo->query($sql);

    // インデックス作成
    $sql = <<< EOM
CREATE INDEX IF NOT EXISTS ban_idx ON bans (effective_date);
EOM;
    $this->pdo->query($sql);

    // テーブル作成(ログからフィルタに引っかかったアドレス用)
    $sql = <<< EOM
CREATE TABLE IF NOT EXISTS "logs" (
  "id"              INTEGER,
  "service"         TEXT,
  "source"          TEXT,
  "create_date"     INTEGER,
  PRIMARY KEY("id" AUTOINCREMENT)
);
EOM;
    $this->pdo->query($sql);

    // インデックス作成
    $sql = <<< EOM
CREATE INDEX IF NOT EXISTS logs_sc_idx ON logs (service, create_date);
EOM;
    $this->pdo->query($sql);

    // インデックス作成
    $sql = <<< EOM
CREATE INDEX IF NOT EXISTS logs_cs_idx ON logs (create_date, service);
EOM;
    $this->pdo->query($sql);
  }

  public function destroy() : void {
  }

  /**
   * ログにデータを追加
   */
  public function addLogData(string $service, string $source, int $createDate) : bool {
    $sql = <<< EOM
INSERT INTO `logs` (`service`, `source`, `create_date`)
VALUES(:service, :source, :create_date);
EOM;
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindParam(':service', $service, \PDO::PARAM_STR);
    $stmt->bindParam(':source', $source, \PDO::PARAM_STR);
    $stmt->bindParam(':create_date', $createDate, \PDO::PARAM_INT);
    return($stmt->execute());
  }

  /**
   * 指定サービスのソースにおいて指定期間でのデータ数を返す
   */
  public function getLogDataCountBetween(string $service, string $source, int $fromDate, int $toDate) {
    $sql = <<< EOM
SELECT COUNT(*) as `cnt`
FROM `logs`
WHERE `service` = :service
AND `source` = :source
AND `create_date` BETWEEN :from_date AND :to_date;
EOM;
    $minDate = min($fromDate, $toDate);
    $maxDate = max($fromDate, $toDate);

    $stmt = $this->pdo->prepare($sql);
    $stmt->bindParam(':service', $service, \PDO::PARAM_STR);
    $stmt->bindParam(':source', $source, \PDO::PARAM_STR);
    $stmt->bindParam(':from_date', $minDate, \PDO::PARAM_INT);
    $stmt->bindParam(':to_date', $maxDate, \PDO::PARAM_INT);
    if($stmt->execute() === false) {
      return(false);
    }

    // laravelではFETCH_OBJになったのでそれに従ってみる
    $result = $stmt->fetch(\PDO::FETCH_OBJ);

    return($result->cnt ?? 0);
  }

  /**
   * DBにBanしたデータを追加(起動時にこのデータをもとにiptables作成する)
   */
  public function addBanData(string $source, string $protocol, string $port, string $rule, int $effectiveDate) : bool {
    $sql = <<< EOM
INSERT INTO `bans` (`source`, `protocol`, `port`, `rule`, `effective_date`)
VALUES(:source, :protocol, :port, :rule, :effective_date);
EOM;
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindParam(':source', $source, \PDO::PARAM_STR);
    $stmt->bindParam(':protocol', $protocol, \PDO::PARAM_STR);
    $stmt->bindParam(':port', $port, \PDO::PARAM_STR);
    $stmt->bindParam(':rule', $rule, \PDO::PARAM_STR);
    $stmt->bindParam(':effective_date', $effectiveDate, \PDO::PARAM_INT);
    return($stmt->execute());
  }

  /**
   * 指定日で有効期限が過ぎてるデータを返す
   */
  public function getBanList() {
    $sql = <<< EOM
SELECT *
FROM `bans`
EOM;
    $stmt = $this->pdo->prepare($sql);
    if($stmt->execute() === false) {
      return(false);
    }

    // laravelではFETCH_OBJになったのでそれに従ってみる
    $result = $stmt->fetchAll(\PDO::FETCH_OBJ);

    return($result);
  }

  /**
   * 指定日で有効期限が過ぎてるデータを返す
   */
  public function getEffectiveDateOverList(int $date) {
    $sql = <<< EOM
SELECT *
FROM `bans`
WHERE `effective_date` < :effective_date
EOM;
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindParam(':effective_date', $date, \PDO::PARAM_INT);
    if($stmt->execute() === false) {
      return(false);
    }

    // laravelではFETCH_OBJになったのでそれに従ってみる
    $result = $stmt->fetchAll(\PDO::FETCH_OBJ);

    return($result);
  }

  /**
   * DBからBanしたデータを削除
   */
  public function removeBanData(string $source, string $protocol, string $port, string $rule) : bool {
    $sql = <<< EOM
DELETE FROM `bans`
WHERE `source` = :source
AND `protocol` = :protocol
AND `port` = :port
AND `rule` = :rule
EOM;
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindParam(':source', $source, \PDO::PARAM_STR);
    $stmt->bindParam(':protocol', $protocol, \PDO::PARAM_STR);
    $stmt->bindParam(':port', $port, \PDO::PARAM_STR);
    $stmt->bindParam(':rule', $rule, \PDO::PARAM_STR);
    return($stmt->execute());
  }

  /**
   * 指定日で有効期限が過ぎてるデータを消す
   */
  public function removeEffectiveDateOverList(int $date) {
    $sql = <<< EOM
DELETE FROM `bans`
WHERE `effective_date` < :effective_date
EOM;
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindParam(':effective_date', $date, \PDO::PARAM_INT);
    return($stmt->execute());
  }
}
