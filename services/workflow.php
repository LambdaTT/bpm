<?php

namespace BPM\Services;

use SplitPHP\Service;

class Workflow extends Service
{

  public function list($params = [])
  {
    return $this->getDao('BPM_WORKFLOW')
      ->bindParams($params)
      ->find();
  }

  public function get($params = [])
  {
    return $this->getDao('BPM_WORKFLOW')
      ->bindParams($params)
      ->first();
  }
}
