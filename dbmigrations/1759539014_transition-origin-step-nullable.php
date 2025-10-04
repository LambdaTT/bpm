<?php

namespace Bpm\Migrations;

use SplitPHP\DbManager\Migration;
use SplitPHP\Database\DbVocab;

class TransitionOriginStepNullable extends Migration
{
  public function apply()
  {
    $this->Table('BPM_TRANSITION', 'BPM Transition')
      ->string('id_bpm_step_origin')->nullable()->setDefaultValue(null);
  }
}
