<?php

namespace BPM\Commands;

use SplitPHP\Cli;
use SplitPHP\Utils;
use SplitPHP\Database\Dao;

class Commands extends Cli
{
  public function init(): void
  {
    $this->addCommand('workflows:list', function (array $args) {
      $getRows = function ($params) {
        return $this->getService('bpm/workflow')->list($params);
      };

      $columns = [
        'id_bpm_workflow'          => 'ID',
        'ds_title'                 => 'Title',
        'ds_reference_entity_name' => 'Reference',
        'ds_tag'                   => 'Tag',
        'dt_created'               => 'Created At',
        'do_active'                => 'Active',
      ];

      $this->getService('utils/misc')->printDataTable("Workflows List", $getRows, $columns, $args);
    });

    $this->addCommand('workflows:create', function () {
      Utils::printLn("Welcome to the BPM Workflow Creation Command!");
      Utils::printLn("This command will help you create a new BPM workflow.");
      Utils::printLn();
      Utils::printLn(" >> Please follow the prompts to define your workflow informations, steps and properties.");
      Utils::printLn();
      Utils::printLn("  1. Let's defining the workflow's informations.");
      Utils::printLn("------------------------------------------------------");
      Utils::printLn("  >> New Workflow:");

      $workflow = $this->getService('utils/clihelper')->inputForm([
        'ds_title' => [
          'label' => 'Workflow Title',
          'required' => true,
          'length' => 60,
        ],
        'ds_reference_entity_name' => [
          'label' => 'Reference Entity Name',
          'required' => true,
          'length' => 60,
        ],
        'ds_tag' => [
          'label' => 'Workflow Tag',
          'required' => true,
          'length' => 15,
        ],
      ]);

      $workflow->ds_key = 'wrf-' . uniqid();

      $workflow = $this->getDao('BPM_WORKFLOW')->insert($workflow);
      Utils::printLn();
      Utils::printLn("  >> Workflow created successfully!");
      foreach ($workflow as $key => $value) {
        Utils::printLn("    -> {$key}: {$value}");
      }

      Utils::printLn();
      Utils::printLn("  2. Now, let's define the steps for your workflow.");
      $proceed = readline("  >> Do you want to proceed? (y/n): ");
      if (strtolower($proceed) !== 'y') {
        Utils::printLn("  >> No steps were created. At any moment, you can run 'bpm:create:steps' to create steps later.");
        return;
      }
      Utils::printLn("------------------------------------------------------");
      $this->createSteps($workflow);

      Dao::flush();

      Utils::printLn();
      Utils::printLn("  3. Now, let's define the transitions for your workflow.");
      $proceed = readline("  >> Do you want to proceed? (y/n): ");
      if (strtolower($proceed) !== 'y') {
        Utils::printLn("  >> No transitions were created. At any moment, you can run 'bpm:create:transitions' to create transitions later.");
        return;
      }
      Utils::printLn("------------------------------------------------------");
      $this->createTransitions($workflow);
    });

    $this->addCommand('workflows:remove', function () {
      Utils::printLn("Welcome to the BPM Workflow Removal Command!");
      Utils::printLn();
      $workflowId = readline("  >> Please, enter the Workflow ID you want to remove: ");
      $workflow = $this->getDao('BPM_WORKFLOW')
        ->filter('id_bpm_workflow', $workflowId)
        ->first();
      if (!$workflow) {
        Utils::printLn("  >> Workflow with ID {$workflowId} not found.");
        return;
      }

      $this->getDao('BPM_WORKFLOW')
        ->filter('id_bpm_workflow', $workflowId)
        ->delete();
      Utils::printLn("  >> Workflow with ID {$workflowId} removed successfully!");
    });

    $this->addCommand('steps:list', function (array $args) {
      $getRows = function ($params) {
        return $this->getService('bpm/step')->list($params);
      };

      $columns = [
        'id_bpm_step'              => 'ID',
        'ds_title'                 => 'Title',
        'nr_step_order'            => 'Order',
        'do_is_terminal'           => 'Is Terminal',
        'ds_tag'                   => 'Tag',
        'workflowTitle'            => 'Workflow',
      ];

      $this->getService('utils/misc')->printDataTable("Steps List", $getRows, $columns, $args);
    });

    $this->addCommand('steps:create', function () {
      Utils::printLn("Welcome to the BPM Step Creation Command!");
      Utils::printLn("This command will help you create a new BPM step.");
      Utils::printLn();
      Utils::printLn(" >> Please follow the prompts to define your steps informations.");
      Utils::printLn();
      Utils::printLn("  >> New Step:");
      Utils::printLn("------------------------------------------------------");
      $workflowId = readline("  >> Enter the Workflow ID to which this step belongs: ");
      $workflow = $this->getDao('BPM_WORKFLOW')
        ->filter('id_bpm_workflow', $workflowId)
        ->first();
      if (!$workflow) {
        Utils::printLn("  >> Workflow with ID {$workflowId} not found.");
        return;
      }

      $this->createSteps($workflow);
    });

    $this->addCommand('steps:remove', function () {
      Utils::printLn("Welcome to the BPM Step Removal Command!");
      Utils::printLn();
      $stepId = readline("  >> Please, enter the Step ID you want to remove: ");

      $this->getDao('BPM_STEP')
        ->filter('id_bpm_step', $stepId)
        ->delete();
      Utils::printLn("  >> Step with ID {$stepId} removed successfully!");
    });

    $this->addCommand('transitions:list', function (array $args) {
      $getRows = function ($params) {
        return $this->getService('bpm/transition')->list($params);
      };

      $columns = [
        'id_bpm_transition'        => 'ID',
        'ds_title'                 => 'Title',
        'ds_icon'                  => 'Icon',
        'stepOrigin'               => 'Step Origin',
        'stepDestination'          => 'Step Destination',
        'workflowTitle'            => 'Workflow',
      ];

      $this->getService('utils/misc')->printDataTable("Transitions List", $getRows, $columns, $args);
    });

    $this->addCommand('transitions:create', function () {
      Utils::printLn("Welcome to the BPM Transition Creation Command!");
      Utils::printLn("This command will help you create a new BPM transition.");
      Utils::printLn();
      Utils::printLn(" >> Please follow the prompts to define your transitions informations.");
      Utils::printLn();
      Utils::printLn("  >> New Transition:");
      Utils::printLn("------------------------------------------------------");
      $workflowId = readline("  >> Enter the Workflow ID to which this transition belongs: ");
      $workflow = $this->getDao('BPM_WORKFLOW')
        ->filter('id_bpm_workflow', $workflowId)
        ->first();
      if (!$workflow) {
        Utils::printLn("  >> Workflow with ID {$workflowId} not found.");
        return;
      }

      $this->createTransitions($workflow);
    });

    $this->addCommand('transitions:remove', function () {
      Utils::printLn("Welcome to the BPM Transition Removal Command!");
      Utils::printLn();
      $transitionId = readline("  >> Please, enter the Transition ID you want to remove: ");

      $this->getDao('BPM_TRANSITION')
        ->filter('id_bpm_transition', $transitionId)
        ->delete();
      Utils::printLn("  >> Transition with ID {$transitionId} removed successfully!");
    });

    $this->addCommand('help', function () {
      /** @var \Utils\Services\CliHelper $helper */
      $helper = $this->getService('utils/clihelper');
      Utils::printLn($helper->ansi(strtoupper("Welcome to the BPM Help Center!"), 'color: magenta; font-weight: bold'));

      // 1) Define metadata for each command
      $commands = [
        'workflows:list'   => [
          'usage' => 'bpm:workflows:list [--limit=<n>] [--sort-by=<field>] [--sort-direction=<dir>] [--page=<n>]',
          'desc'  => 'Page through existing workflows.',
          'flags' => [
            '--limit=<n>'          => 'Items per page (default 10)',
            '--sort-by=<field>'    => 'Field to sort by',
            '--sort-direction=<d>' => 'ASC or DESC (default ASC)',
            '--page=<n>'           => 'Page number (default 1)',
          ],
        ],
        'workflows:create' => [
          'usage' => 'bpm:workflows:create',
          'desc'  => 'Interactively create a new workflow.',
        ],
        'workflows:remove' => [
          'usage' => 'bpm:workflows:remove',
          'desc'  => 'Delete a workflow by its ID.',
        ],
        'steps:list'       => [
          'usage' => 'bpm:steps:list [--limit=<n>] [--sort-by=<field>] [--sort-direction=<dir>] [--page=<n>]',
          'desc'  => 'Page through steps of workflows.',
          'flags' => [], // same as workflows:list if you wish
        ],
        'steps:create'     => [
          'usage' => 'bpm:steps:create',
          'desc'  => 'Interactively add steps to a workflow.',
        ],
        'steps:remove'     => [
          'usage' => 'bpm:steps:remove',
          'desc'  => 'Remove a step by its ID.',
        ],
        'transitions:list' => [
          'usage' => 'bpm:transitions:list [--limit=<n>] [--sort-by=<field>] [--sort-direction=<dir>] [--page=<n>]',
          'desc'  => 'Page through workflow transitions.',
          'flags' => [], // same as workflows:list if you wish
        ],
        'transitions:create' => [
          'usage' => 'bpm:transitions:create',
          'desc'  => 'Interactively add transitions to a workflow.',
        ],
        'transitions:remove' => [
          'usage' => 'bpm:transitions:remove',
          'desc'  => 'Remove a transition by its ID.',
        ],
        'help'             => [
          'usage' => 'bpm:help',
          'desc'  => 'Show this help screen.',
        ],
      ];

      // 2) Summary table
      Utils::printLn($helper->ansi("\nAvailable commands:\n", 'color: cyan; text-decoration: underline'));

      $rows = [
        [
          'cmd'  => 'bpm:workflows:list',
          'desc' => 'Page through existing workflows',
          'opts' => '--limit, --sort-by, --sort-direction, --page',
        ],
        [
          'cmd'  => 'bpm:workflows:create',
          'desc' => 'Interactively create a new workflow',
          'opts' => '(no flags)',
        ],
        [
          'cmd'  => 'bpm:workflows:remove',
          'desc' => 'Delete a workflow by ID',
          'opts' => '(no flags)',
        ],
        [
          'cmd'  => 'bpm:steps:list',
          'desc' => 'Page through steps of workflows',
          'opts' => '--limit, --sort-by, --sort-direction, --page',
        ],
        [
          'cmd'  => 'bpm:steps:create',
          'desc' => 'Interactively add steps to a workflow',
          'opts' => '(no flags)',
        ],
        [
          'cmd'  => 'bpm:steps:remove',
          'desc' => 'Remove a step by ID',
          'opts' => '(no flags)',
        ],
        [
          'cmd'  => 'bpm:transitions:list',
          'desc' => 'Page through transitions',
          'opts' => '--limit, --sort-by, --sort-direction, --page',
        ],
        [
          'cmd'  => 'bpm:transitions:create',
          'desc' => 'Interactively add transitions to a workflow',
          'opts' => '(no flags)',
        ],
        [
          'cmd'  => 'bpm:transitions:remove',
          'desc' => 'Remove a transition by ID',
          'opts' => '(no flags)',
        ],
        [
          'cmd'  => 'bpm:help',
          'desc' => 'Show this help screen',
          'opts' => '(no flags)',
        ],
      ];

      $helper->table($rows, [
        'cmd'  => 'Command',
        'desc' => 'Description',
        'opts' => 'Options',
      ]);

      // 3) Detailed usage lists
      foreach ($commands as $cmd => $meta) {
        Utils::printLn($helper->ansi("\n{$cmd}", 'color: yellow; font-weight: bold'));
        Utils::printLn("  Usage:   {$meta['usage']}");
        Utils::printLn("  Purpose: {$meta['desc']}");

        if (!empty($meta['flags'])) {
          Utils::printLn("  Options:");
          $flagLines = [];
          foreach ($meta['flags'] as $flag => $explain) {
            $flagLines[] = "{$flag}  — {$explain}";
          }
          $helper->listItems($flagLines, false, '    •');
        }
      }

      Utils::printLn(''); // trailing newline
    });
  }

  private function createSteps(&$workflow)
  {
    $steps = [];
    while (true) {
      Utils::printLn("  >> New Step:");
      $step = $this->getService('utils/clihelper')->inputForm([
        'ds_title' => [
          'label' => 'Step Title',
          'required' => true,
          'length' => 60,
        ],
        'nr_step_order' => [
          'label' => 'Step Order',
          'required' => true,
          'type' => 'int',
        ],
        'do_is_terminal' => [
          'label' => 'Is the step terminal (\'Y\' or \'N\')?',
          'default' => 'N',
        ],
        'ds_style' => [
          'label' => 'Step Style(CSS rules)',
          'required' => false,
          'length' => 255,
        ],
        'ds_tag' => [
          'label' => 'Step Tag',
          'required' => false,
          'length' => 60,
        ],
      ]);

      $step->ds_key = 'stp-' . uniqid();
      $step->id_bpm_workflow = $workflow->id_bpm_workflow;

      $steps[] = $this->getDao('BPM_STEP')->insert($step);

      Utils::printLn("  >> Do you want to add another step? (y/n)");
      $response = trim(fgets(STDIN));
      if (strtolower($response) !== 'y') {
        break;
      }
    }

    if (!empty($steps)) {
      $workflow->steps = $steps;
    }

    Utils::printLn();
    Utils::printLn("  >> Steps created successfully!");
    foreach ($workflow->steps as $step) {
      foreach ($step as $key => $value) {
        Utils::printLn("    -> {$key}: {$value}");
      }
      Utils::printLn();
    }
  }

  private function createTransitions(&$workflow)
  {
    $transitions = [];
    while (true) {
      Utils::printLn("  >> New Transition:");
      $transition = $this->getService('utils/clihelper')->inputForm([
        'ds_title' => [
          'label' => 'Transition Title',
          'required' => true,
          'length' => 60,
        ],
        'ds_icon' => [
          'label' => 'Transition Icon (FontAwesome class)',
          'required' => false,
          'length' => 60,
        ],
        'id_bpm_step_origin' => [
          'label' => 'Origin Step ID',
          'required' => true,
          'type' => 'int',
          'validator' => [
            'message' => 'There is no step with this ID in the workflow.',
            'fn' => function ($value) use ($workflow) {
              return (bool) $this->getDao('BPM_STEP')
                ->filter('id_bpm_workflow', $workflow->id_bpm_workflow)
                ->and('id_bpm_step', $value)
                ->first($value);
            },
          ]
        ],
        'id_bpm_step_destination' => [
          'label' => 'Destination Step ID',
          'required' => true,
          'type' => 'int',
          'validator' => [
            'message' => 'There is no step with this ID in the workflow.',
            'fn' => function ($value) use ($workflow) {
              return (bool) $this->getDao('BPM_STEP')
                ->filter('id_bpm_workflow', $workflow->id_bpm_workflow)
                ->and('id_bpm_step', $value)
                ->first($value);
            },
          ]
        ],
      ]);

      $transition->id_bpm_workflow = $workflow->id_bpm_workflow;
      $transition->ds_key = 'trn-' . uniqid();
      $transitions[] = $this->getDao('BPM_TRANSITION')->insert($transition);

      Utils::printLn("  >> Do you want to add another transition? (y/n)");
      $response = trim(fgets(STDIN));
      if (strtolower($response) !== 'y') {
        break;
      }
    }

    if (!empty($transitions)) {
      $workflow->transitions = $transitions;
    }
    Utils::printLn();
    Utils::printLn("  >> Workflow transitions created successfully!");
    foreach ($workflow->transitions as $transition) {
      foreach ($transition as $key => $value) {
        Utils::printLn("    -> {$key}: {$value}");
      }
    }
  }
}
