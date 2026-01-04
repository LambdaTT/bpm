<?php

namespace BPM\Routes;

use SplitPHP\WebService;
use SplitPHP\Exceptions\Unauthorized;

class Bpm extends WebService
{

  public function init(): void
  {
    $this->setAntiXsrfValidation(false);

    //------------- Execution Info Endpoint -------------//
    $this->addEndpoint('GET', '/v1/execution-info/?key?', function ($key) {
      $this->auth([
        'BPM_EXECUTION' => 'R',
        'BPM_STEP' => 'R',
      ]);

      // Busca as informações da execução:
      $execInfo = $this->getDao('BPM_EXECUTION')
        ->filter('ds_key')->equalsTo($key)
        ->first(
          "SELECT 
                    exe.*, 
                    wfl.ds_title AS workflowTitle,
                    stp.ds_title AS stepName
                  FROM `BPM_EXECUTION` exe
                  JOIN `BPM_WORKFLOW` wfl ON wfl.id_bpm_workflow = exe.id_bpm_workflow
                  JOIN `BPM_STEP` stp ON stp.id_bpm_step = exe.id_bpm_step_current
                  WHERE exe.ds_key = ?ds_key?",
        );

      return $this->response
        ->withStatus(200)
        ->withData($execInfo);
    });

    //------------- Available Transitions Endpoint -------------//
    $this->addEndpoint('GET', '/v1/available-transitions/?key?', function ($key) {
      $this->auth([
        'BPM_EXECUTION' => 'R',
        'BPM_STEP' => 'R',
        'BPM_TRANSITION' => 'R',
      ]);

      // Busca a lista de transições disponíveis com base no ID da execução:
      $availableTransitions = $this->getService('bpm/wizard')
        ->availableTransitions($key);

      // Retorna a lista encontrada com Status 200:
      return $this->response
        ->withStatus(200)
        ->withData($availableTransitions);
    });

    //------------- Transition / First Step -------------//
    $this->addEndpoint('PUT', '/v1/transition/?executionKey?/?transitionKey?', function ($executionKey, $transitionKey) {
      $this->auth([
        'BPM_EXECUTION' => 'U',
      ]);

      // Call Transition Service:
      $this->getService('bpm/wizard')->transition($executionKey, $transitionKey);

      // Response 204:
      return $this->response->withStatus(204);
    });

    //------------- BPM STEP TRACKING Record -------------//
    $this->addEndpoint('GET', '/v1/step-tracking/?key?', function ($key) {
      $this->auth([
        'BPM_STEP' => 'R',
        'BPM_STEP_TRACKING' => 'R',
      ]);

      $exec = $this->getDao('BPM_EXECUTION')
        ->filter('ds_key')->equalsTo($key)
        ->first();

      if (!$exec) {
        return $this->response->withStatus(404);
      }

      // Call Service:
      $data = $this->getService('bpm/step')->trackRecord(['id_bpm_execution' => $exec->id_bpm_execution]);

      // Response 200 com o Track Record:
      return $this->response
        ->withStatus(200)
        ->withData($data);
    });

    //------------- BPM Step details -------------//
    $this->addEndpoint('GET', '/v1/step/?key?', function ($key) {
      $this->auth([
        'BPM_STEP' => 'R',
      ]);

      // Call Service:
      $data = $this->getService('bpm/step')->get(['ds_key' => $key]);

      // Response 200 com o step atual;
      return $this->response
        ->withStatus(200)
        ->withData($data);
    });

    //------------- BPM Step List -------------//
    $this->addEndpoint('GET', '/v1/step', function ($input) {
      $this->auth([
        'BPM_STEP' => 'R',
      ]);

      // Call Service:
      $data = $this->getService('bpm/step')->list($input);

      // Response 200 com a lista de steps:
      return $this->response
        ->withStatus(200)
        ->withData($data);
    });
  }

  private function auth(array $permissions)
  {
    if (!$this->getService('modcontrol/control')->moduleExists('iam')) return;

    // Auth user login:
    if (!$this->getService('iam/session')->authenticate())
      throw new Unauthorized("Não autorizado.");

    // Validate user permissions:
    $this->getService('iam/permission')
      ->validatePermissions($permissions);
  }
}
