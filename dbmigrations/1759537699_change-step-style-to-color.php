<?php

namespace Bpm\Migrations;

use SplitPHP\DbManager\Migration;
use SplitPHP\Database\DbVocab;

class ChangeStepStyleToColor extends Migration
{
  public function apply()
  {
    $this->Table('BPM_STEP', 'BPM Step')
      ->string('ds_style')->drop()
      ->string('ds_color', 100)->nullable()->setDefaultValue(null);
  }
}
