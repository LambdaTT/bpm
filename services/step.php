<?php

namespace application\services\bpm;

use \engine\Service;

class Step extends Service
{

  public function list($params = [])
  {
    return $this->getDao('BPM_STEP')
      ->bindParams($params)
      ->find();
  }

  public function get($params = [])
  {
    return $this->getDao('BPM_STEP')
      ->bindParams($params)
      ->first();
  }

  public function trackRecord($params)
  {   
    return $this->getDao('BPM_STEP_TRACKING')
      ->bindParams($params)
      ->find(
        "SELECT
          trk.*,
          DATE_FORMAT(trk.dt_track, '%d/%m/%Y %T') as dtTracking,
          stp.ds_title as stepName
        FROM BPM_STEP_TRACKING trk
        JOIN BPM_STEP stp ON trk.id_bpm_step = stp.id_bpm_step"
      );
  }

  public function track($executionId, $stepId)
  {

    $data['id_bpm_execution'] = $executionId;
    $data['id_bpm_step'] = $stepId;

    return $this->getDao('BPM_STEP_TRACKING')->insert($data);
  }
}
