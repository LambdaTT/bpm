<?php

namespace BPM\Services;

use Exception;
use SplitPHP\Service;
use SplitPHP\Database\Dao;
use SplitPHP\Exceptions\FailedValidation;

class Wizard extends Service
{
  public function availableTransitions($executionKey)
  {
    //Identifica a Etapa da execução atual usando executionKey:
    $execution = $this->getDao('BPM_EXECUTION')
      ->filter('ds_key')->equalsTo($executionKey)
      ->first();

    if (empty($execution)) {
      throw new Exception("It was not possible to find the execution with the provided key");
    }

    $currentStep = $this->getService('bpm/step')->get(['id_bpm_step' => $execution->id_bpm_step_current]);
    if ($currentStep->do_is_terminal == 'Y') return [];

    return $this->getDao('BPM_TRANSITION')
      ->filter('id_bpm_step_current')->equalsTo($execution->id_bpm_step_current)
      ->and('id_bpm_workflow')->equalsTo($execution->id_bpm_workflow)
      ->find(
        "SELECT *
          FROM `BPM_TRANSITION`
          WHERE (id_bpm_step_origin = ?id_bpm_step_current? OR id_bpm_step_origin IS NULL)
          AND id_bpm_workflow = ?id_bpm_workflow?
        "
      );
  }

  public function startWorkflow($workflowTag, $referenceEntityID)
  {
    //Capta as informações do Workflow:
    $workflow = $this->getDao('BPM_WORKFLOW')
      ->filter('ds_tag')->equalsTo($workflowTag)
      ->first();

    if (empty($workflow)) {
      throw new Exception("There's no workflow with the tag: " . $workflowTag);
    }

    //Inicia um novo Workflow (execution) baseado no ID e preenche suas informações:
    $data['id_bpm_workflow'] = $workflow->id_bpm_workflow;
    $data['ds_key'] = 'exe-' . uniqid();
    $data['ds_reference_entity_name'] = $workflow->ds_reference_entity_name;
    $data['id_reference_entity_id'] = $referenceEntityID;

    //Preciso fazer a chamada de updExecStep depois disso pq ela usa a entidade criada aqui.
    $newExec = $this->getDao('BPM_EXECUTION')->insert($data);

    Dao::flush();

    //Chama a função updExecutionStep para dar o primeiro passo:
    $newExec->id_bpm_step_current = $this->updExecutionStep($data['ds_key'])->id_bpm_step;

    //Retorna o Objeto com os dados que acabaram de ser inseridos:
    return $newExec;
  }

  public function transition($executionKey, $transitionKey)
  {
    //Pegando as informações necessárias:
    $transition = $this->getService('bpm/transition')->get(['ds_key' => $transitionKey]);
    $execution = $this->getDao('BPM_EXECUTION')
      ->filter('ds_key')->equalsTo($executionKey)
      ->first();
    $workflowSteps = $this->getDao('BPM_STEP')
      ->filter('id_bpm_workflow')->equalsTo($execution->id_bpm_workflow)
      ->find();


    //Verificação Step Origin
    $validatedOrigin = false;
    $validatedDestiny = false;

    foreach ($workflowSteps as $value) {
      if ($value->id_bpm_step == $transition->id_bpm_step_origin || $transition->id_bpm_step_origin == null) {
        $validatedOrigin = true;
      }
      if ($value->id_bpm_step == $transition->id_bpm_step_destination) {
        $validatedDestiny = true;
      }
    }

    if ($validatedOrigin != true || $validatedDestiny != true) {
      throw new FailedValidation("O step de origem e/ou de destino da transição não pertence ao workflow identificado");
    }

    //Verificando se a Etapa de origem da transição é a mesma que a etapa atual da execução:
    if ($transition->id_bpm_step_origin != $execution->id_bpm_step_current && $transition->id_bpm_step_origin != null) {
      throw new FailedValidation("Não foi possível passar para a próxima etapa pois a Etapa de origem da transição não é igual à etapa atual da execução (transição:" . $transition->id_bpm_step_origin . ", execução:" . $execution->id_bpm_step_current . ")");
    }

    $currentStep = $this->getService('bpm/step')->get(['id_bpm_step' => $execution->id_bpm_step_current]);

    if (empty($currentStep)) throw new Exception("Current step is invalid.");

    if ($currentStep->do_is_terminal === 'Y') throw new FailedValidation("O processo já está finalizado.");

    // Executa as regras de transição:
    eval($transition->tx_rules);

    $this->updExecutionStep($executionKey, $transition->id_bpm_step_destination);
  }

  public function updExecutionStep($executionKey, $newStepId = null)
  {

    //Pegando informações necessárias:
    $execution = $this->getDao('BPM_EXECUTION')
      ->filter('ds_key')->equalsTo($executionKey)
      ->first();
    $workflowId = $execution->id_bpm_workflow;
    $currentStep = $this->getDao('BPM_STEP')
      ->filter('id_bpm_step')->equalsTo($execution->id_bpm_step_current)
      ->and('id_bpm_workflow')->equalsTo($workflowId)
      ->first();
    $newStep = null;


    //Verificar se o Novo step é nulo e caso seja, atribuir o ID do primeiro step do workflow a ele:
    if ($newStepId == null) {
      $newStep = $this->getDao('BPM_STEP')
        ->bindParams([
          'id_bpm_workflow' => $workflowId,
          '$sort_by' => 'nr_step_order'
        ])
        ->first();
      $newStepId = $newStep->id_bpm_step;
    } else {
      $newStep = $this->getDao('BPM_STEP')
        ->filter('id_bpm_step')->equalsTo($newStepId)
        ->and('id_bpm_workflow')->equalsTo($workflowId)
        ->first();
    }

    // Executa as regras de saída (tx_out_rules):
    if (!empty($currentStep->tx_out_rules)) {
      eval($currentStep->tx_out_rules);
    }
    $loggedUser = $this->getService('iam/session')->getLoggedUser();
    //Atualiza o step atual para o ID do step novo:
    $this->getDao('BPM_EXECUTION')
      ->filter('ds_key')->equalsTo($executionKey)
      ->update([
        'dt_updated' => date('Y-m-d H:i:s'),
        'id_bpm_step_current' => $newStepId,
      ]);

    //Atualiza o Tracking de Steps:
    $this->getService('bpm/step')->track($execution->id_bpm_execution, $newStepId);

    // Executa as Regras de Entrada no novo step:
    if (!empty($newStep->tx_in_rules)) {
      eval($newStep->tx_in_rules);
    }

    return $newStep;
  }

  public function removeExecution($params)
  {
    return $this->getDao('BPM_EXECUTION')
      ->bindParams($params)
      ->delete();
  }
}
