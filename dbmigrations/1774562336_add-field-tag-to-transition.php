<?php

namespace Bpm\Migrations;

use SplitPHP\DbManager\Migration;
use SplitPHP\Database\DbVocab;

class AddFieldTagToTransition extends Migration
{
  public function apply()
  {
    $this->Table('BPM_TRANSITION', 'BPM Transition')
      ->string('ds_tag', 50);
  }
}
