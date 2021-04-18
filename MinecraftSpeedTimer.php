#!/usr/bin/php
<?php

namespace cklossek\MinecraftSpeedTimer;

use DateTime;
use DateInterval;

class MinecraftSpeedTimer {

  /** @var resource */
  private $stdin;

  /** @var int */
  private $current_timer_id = 0;

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
      $this->_outputDuration();

      $key = $this->_getKeyStroke();

      // Programm beenden
      if ($key == 113) {
        echo PHP_EOL;
        break;
      }

      if ($key == 10) {
        echo PHP_EOL;
        $this->_startNewTimer();
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
    $this->current_timer_id = 1;
    $this->_outputDuration();
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
   * Schreibt die Laufzeit auf die Standardausgabe
   *
   * @return void
   */
  private function _outputDuration() {
    $timer = $this->timer[$this->current_timer_id];
    $dt = new DateTime();
    $dt->add(new DateInterval('PT' . (time() - $timer['start']) . 'S'));
    $duration = $dt->diff(new DateTime());
    printf("\r" . $timer['name'] . " %s", $duration->format('%I:%S'));
  }

}

$class = new MinecraftSpeedTimer();
$class->run();