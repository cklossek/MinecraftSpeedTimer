#!/usr/bin/env php
<?php

namespace cklossek\MinecraftSpeedTimer;

use DateTime;
use DateInterval;

class MinecraftSpeedTimer {

  /** @var resource */
  private $stdin;

  /** @var int */
  private $current_timer_id = 0;

  /** @var int */
  private $pause_timestamp = null;

  /** @var array */
  private $timer = array(
    1 => ['name' => 'Gesamt'],
    2 => ['name' => 'Overworld'],
    3 => ['name' => 'Nether'],
    4 => ['name' => 'Fortress'],
    5 => ['name' => 'Enderpearl'],
    6 => ['name' => 'Stronghold'],
    7 => ['name' => 'End']
  );



  /**
   * Startet den Timer und wartet auf "Return" um eine neue Rundenzeit anzuzeigen, bzw. auf "q" um 
   * den Timer zu beenden
   *
   * @return void
   */
  public function run() {
    $this->_init();

    // erster "Runden"-Timer
    $this->_startNewTimer();

    while (1) {
      $this->_outputDuration($this->current_timer_id);

      $key = $this->_getKeyStroke();

      // Programm beenden
      if ($key == 113) {
        echo PHP_EOL;
        break;
      }

      // new Timer
      if ($key == 10 && is_null($this->pause_timestamp)) {
        echo PHP_EOL;
        $this->_startNewTimer();
      }

      // pause
      if ($key == 32) {
        $this->_toggleTimer();
      }
    }
  }



  /**
   * Initialisiert den Kommandozeilen Stream
   *
   * @return void
   */
  private function _init() {
    echo "Minecraft Speedtimer (für Levi von Dad)" . PHP_EOL . PHP_EOL;
    system('stty cbreak -echo');
    $this->stdin = fopen('php://stdin', 'r');
    stream_set_blocking($this->stdin, false);

    // Gesamttimer
    $this->_startNewTimer();
  }



  /**
   * Schließt die Standardausgabe und bereinigt die Terminalkonfiguration
   *
   * @return void
   */
  public function __destruct() {
    fclose($this->stdin);
    system('stty sane');

    echo PHP_EOL;
    $this->_outputDuration(1);
    echo PHP_EOL;
  }



  /**
   * Wartet auf einen Tastendruck und gibt diesen als Integer zurück
   *
   * @return int|null
   */
  private function _getKeyStroke(): ?int {
    $char = fgetc($this->stdin);
    if (!$char) {
      return null;
    } else {
      return ord($char);
    }
  }



  /**
   * Startet einen neuen Timer
   *
   * @return void
   */
  private function _startNewTimer() {
    if (!isset($this->timer[$this->current_timer_id + 1])) {
      return;
    }
    $this->current_timer_id++;
    $this->timer[$this->current_timer_id]['start'] = time();
  }



  /**
   * Pause or unpause timer
   * 
   * @return void
   */
  private function _toggleTimer() {
    if (is_null($this->pause_timestamp)) {
      $this->pause_timestamp = time();
      return;
    }

    $paused_duration = time() - $this->pause_timestamp;

    // current timer
    $this->_addPausedDuration($this->current_timer_id, $paused_duration);

    // total timer
    $this->_addPausedDuration(1, $paused_duration);

    $this->pause_timestamp = null;
  }



  /**
   * Add paused duration to timer
   *
   * @param int $timer_id Timer Id
   * @param int $duration Duration
   *
   * @return void
   */
  private function _addPausedDuration(int $timer_id, int $duration) {
    if (!isset($this->timer[$timer_id]['paused_duration'])) {
      $this->timer[$timer_id]['paused_duration'] = 0;
    }

    $this->timer[$timer_id]['paused_duration'] += $duration;
  }



  /**
   * Write timer duration to standard output
   *
   * @param int $timer_id Timer Id
   * @return void
   */
  private function _outputDuration(int $timer_id) {
    if (!is_null($this->pause_timestamp)) {
      $duration = $this->pause_timestamp - $this->timer[$timer_id]['start'];
      $post_output_text = ' paused';

    } else {
      $duration = time() - $this->timer[$timer_id]['start'];
      $post_output_text = '';
    }

    if (isset($this->timer[$timer_id]['paused_duration'])) {
      $duration -= $this->timer[$timer_id]['paused_duration'];
    }

    $time1 = new DateTime();
    $time2 = clone $time1;
    $time2->add(new DateInterval('PT' . $duration . 'S'));

    printf("\r\033[K" . $this->timer[$timer_id]['name'] . " %s" . $post_output_text, $time1->diff($time2)->format('%H:%I:%S'));
  }

}

(new MinecraftSpeedTimer())->run();