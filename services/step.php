<?php

namespace BPM\Services;

use SplitPHP\Service;

class Step extends Service
{
  public function list($params = [])
  {
    return $this->getDao('BPM_STEP')
      ->bindParams($params)
      ->find(
        "SELECT 
            stp.*, 
            wfl.ds_title AS workflowTitle,
            wfl.ds_tag AS workflowTag
          FROM `BPM_STEP` stp
          LEFT JOIN `BPM_WORKFLOW` wfl ON wfl.id_bpm_workflow = stp.id_bpm_workflow"
      );
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
          stp.ds_title as stepName,
          stp.ds_tag as stepTag,
          CONCAT(usr.ds_first_name, ' ', usr.ds_last_name) as userName
        FROM BPM_STEP_TRACKING trk
        JOIN BPM_STEP stp ON trk.id_bpm_step = stp.id_bpm_step
        LEFT JOIN IAM_USER usr ON usr.id_iam_user = trk.id_iam_user
        ORDER BY trk.dt_track ASC"
      );
  }

  public function track($executionId, $stepId)
  {
    $data['id_bpm_execution'] = $executionId;
    $data['id_bpm_step']      = $stepId;
    if ($this->getService('modcontrol/control')->moduleExists('iam'))
      $data['id_iam_user'] = $this->getService('iam/session')->getLoggedUser()?->id_iam_user;

    return $this->getDao('BPM_STEP_TRACKING')->insert($data);
  }
}
