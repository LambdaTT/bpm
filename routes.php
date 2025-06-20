<?php

namespace Bpm\Routes;

use SplitPHP\WebService;

class Bpm extends WebService
{

  public function init()
  {
    $this->setAntiXsrfValidation(false);
    //------------- Available Transitions Endpoint -------------//
    $this->addEndpoint('GET', '/v1/available-transitions/?executionKey?', function ($input) {
      // Validate User: 
      // if (!$this->getService('iam/session')->authenticate()) {
      //   return $this->response->withStatus(401);
      // }

      // Validate permissions:
      // $this->getService('iam/permission')->validatePermissions([
      //   'BPM_EXECUTION' => 'R',
      //   'BPM_STEP' => 'R',
      //   'BPM_TRANSITION' => 'R',
      // ]);

      // Busca a lista de transições disponíveis com base no ID da execução:
      $availableTransitions = $this->getService('bpm/wizard')
        ->availableTransitions($input['executionKey']);

      // Retorna a lista encontrada com Status 200:
      return $this->response
        ->withStatus(200)
        ->withData($availableTransitions);
    });

    //------------- Transition / First Step -------------//
    $this->addEndpoint('PUT', '/v1/transition/?executionKey?/?transitionKey?', function ($input) {
      // Validate User: 
      // if (!$this->getService('iam/session')->authenticate()) {
      //   return $this->response->withStatus(401);
      // }

      // Validate permissions:
      // $this->getService('iam/permission')->validatePermissions([
      //   'BPM_EXECUTION' => 'U',
      // ]);

      // Call Transition Service:
      $data = $this->getService('bpm/wizard')->transition($input['executionKey'], $input['transitionKey']);

      // Response 204:
      return $this->response->withStatus(204);
    });

    //------------- BPM STEP TRACKING Record -------------//
    $this->addEndpoint('GET', '/v1/bpm/trackrecord/?executionId?', function ($input) {
      // Validate User: 
      // if (!$this->getService('iam/session')->authenticate()) {
      //   return $this->response->withStatus(401);
      // }

      // Validate permissions:
      // $this->getService('iam/permission')->validatePermissions([
      //   'BPM_EXECUTION' => 'R',
      // ]);

      // Call Service:
      $data = $this->getService('bpm/step')->trackRecord(['id_bpm_execution' => $input['executionId']]);

      // Response 200 com o Track Record:
      return $this->response->withStatus(200)->withData($data);
    });

    //------------- BPM Step details -------------//
    $this->addEndpoint('GET', '/v1/bpm/step/?stepKey?', function ($input) {
      // Validate User: 
      // if (!$this->getService('iam/session')->authenticate()) {
      //     return $this->response->withStatus(401);
      // }

      // Validate permissions:
      // $this->getService('iam/permission')->validatePermissions([
      //     'BPM_EXECUTION' => 'U',
      // ]);

      // Call Service:
      $data = $this->getService('bpm/step')->get(['ds_key' => $input['stepKey']]);

      // Response 200 com o step atual;
      return $this->response->withStatus(200)->withData($data);
    });

    //------------- BPM Step List -------------//
    $this->addEndpoint('GET', '/v1/bpm/step', function ($input) {
      // Validate User: 
      // if (!$this->getService('iam/session')->authenticate()) {
      //     return $this->response->withStatus(401);
      // }

      // Validate permissions:
      // $this->getService('iam/permission')->validatePermissions([
      //     'BPM_EXECUTION' => 'U', 
      // ]);

      // Call Service:
      $data = $this->getService('bpm/step')->list($input);

      // Response 200 com o Track Record:
      return $this->response->withStatus(200)->withData($data);
    });
  }
}
