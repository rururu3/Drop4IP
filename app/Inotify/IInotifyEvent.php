<?php
namespace App\Inotify;

interface IInotifyEvent {
  public function IN_ACCESS();
  public function IN_MODIFY();
  public function IN_ATTRIB();
  public function IN_CLOSE_WRITE();
  public function IN_CLOSE_NOWRITE();
  public function IN_OPEN();
  public function IN_MOVED_FROM();
  public function IN_MOVED_TO();
  public function IN_CREATE();
  public function IN_DELETE();
  public function IN_DELETE_SELF();
  public function IN_MOVE_SELF();
  public function IN_UNMOUNT();
  public function IN_Q_OVERFLOW();
  public function IN_IGNORED();
  public function IN_CLOSE();
  public function IN_MOVE();
}