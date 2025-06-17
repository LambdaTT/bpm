<?php

namespace application\services\bpm;

use \engine\Service;

class Transition extends Service
{

  public function list($params = [])
  {
    return $this->getDao('BPM_TRANSITION')
      ->bindParams($params)
      ->find();
  }

  public function get($params = [])
  {
    return $this->getDao('BPM_TRANSITION')
      ->bindParams($params)
      ->first();
  }
}
