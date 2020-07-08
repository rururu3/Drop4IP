<?php
namespace App\Inotify;

interface IInotifyEvent {
  public function IN_ACCESS() : void;
  public function IN_MODIFY() : void;
  public function IN_ATTRIB() : void;
  public function IN_CLOSE_WRITE() : void;
  public function IN_CLOSE_NOWRITE() : void;
  public function IN_OPEN() : void;
  public function IN_MOVED_FROM() : void;
  public function IN_MOVED_TO() : void;
  public function IN_CREATE() : void;
  public function IN_DELETE() : void;
  public function IN_DELETE_SELF() : void;
  public function IN_MOVE_SELF() : void;
  public function IN_UNMOUNT() : void;
  public function IN_Q_OVERFLOW() : void;
  public function IN_IGNORED() : void;
  public function IN_CLOSE() : void;
  public function IN_MOVE() : void;
}