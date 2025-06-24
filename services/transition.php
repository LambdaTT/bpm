<?php

namespace BPM\Services;

use SplitPHP\Service;

class Transition extends Service
{

  public function list($params = [])
  {
    return $this->getDao('BPM_TRANSITION')
      ->bindParams($params)
      ->find(
        "SELECT 
            trn.*, 
            stpo.ds_title AS stepOrigin,
            stpd.ds_title AS stepDestination,
            wfl.ds_title AS workflowTitle
          FROM `BPM_TRANSITION` trn
          LEFT JOIN `BPM_WORKFLOW` wfl ON wfl.id_bpm_workflow = trn.id_bpm_workflow
          LEFT JOIN `BPM_STEP` stpo ON stpo.id_bpm_step = trn.id_bpm_step_origin
          LEFT JOIN `BPM_STEP` stpd ON stpd.id_bpm_step = trn.id_bpm_step_destination
        "
      );
  }

  public function get($params = [])
  {
    return $this->getDao('BPM_TRANSITION')
      ->bindParams($params)
      ->first();
  }
}
