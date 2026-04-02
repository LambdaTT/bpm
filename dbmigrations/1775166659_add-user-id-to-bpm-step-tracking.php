<?php

namespace BPM\Migrations;

use SplitPHP\DbManager\Migration;

class AddUserIdToBpmStepTracking extends Migration
{
  public function apply()
  {
    $this->Table('BPM_STEP_TRACKING')
      ->int('id_iam_user')->nullable()->setDefaultValue(null)
    ;
  }
}
