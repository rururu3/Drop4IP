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
          'appenders' => ['default'],
        ],
      ],
      'appenders' => [
        'default' => [
          'class' => 'LoggerAppenderFile',
          'layout' => [
            'class' => 'LoggerLayoutSimple'
          ],
          'params' => [
            'file' => __DIR__ . '/../storage/ipban.log',
            'append' => true,
          ],
        ],
      ],
    ]);
    $this->logger = \Logger::getLogger("develop");
  }

  public static function getInstance() : CAppLog {
    return self::$instance ?? self::$instance = new self();
  }

  public function __call(string $method, array $args) {
    return($this->logger->{$method}(...$args));
  }
}