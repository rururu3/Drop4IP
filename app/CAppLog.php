<?php
namespace App;

class CAppLog {
  protected static $instance;
  protected $logger;

  /**
   * constructor
   */
  protected function __construct() {
    // 設定ファイルを読み込みます  
    \Logger::configure([
      'rootLogger' => [
        'level' => 'INFO',
        'appenders' => ['default'],
      ],
      'loggers' => [
        'develop' => [
          'level' => 'DEBUG',
          'additivity' => false,
          'appenders' => ['default'],
        ],
      ],
      'appenders' => [
        'default' => [
          'class' => 'LoggerAppenderFile',
          'layout' => [
            'class' => 'LoggerLayoutPattern',
            'params' => [
              // 'conversionPattern' => '%date %logger %-5level %msg%n',
              'conversionPattern' => '%date %-5level - %msg%n',
            ],
          ],
          'params' => [
            'file' => __DIR__ . '/../storage/drop4ip.log',
            'append' => true,
          ],
        ],
      ],
    ]);
    $this->logger = \Logger::getLogger("develop");
  }

  /**
   * インスタンス取得
   */
  public static function getInstance() : CAppLog {
    return self::$instance ?? self::$instance = new self();
  }

  /**
   * マジックメソッドを利用してログクラスのメソッド呼び出し
   */
  public function __call(string $method, array $args) {
    return($this->logger->{$method}(...$args));
  }
}