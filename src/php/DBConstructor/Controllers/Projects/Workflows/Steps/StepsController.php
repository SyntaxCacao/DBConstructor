<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Workflows\Steps;

use DBConstructor\Application;
use DBConstructor\Controllers\ForbiddenController;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\Workflow;
use DBConstructor\Models\WorkflowStep;
use DBConstructor\Util\JsonException;

class StepsController
{
    /**
     * @throws JsonException
     */
    public function request(array $path, Workflow $workflow, array &$data): bool
    {
        if (! $data["isManager"]) {
            (new ForbiddenController())->request($path);
            return false;
        }

        if (count($path) === 5) {
            // list
            if (isset($_REQUEST["activate"]) && ! $workflow->active) {
                $workflow->activate();
            } else if (isset($_REQUEST["deactivate"]) && $workflow->active) {
                $workflow->deactivate();
            }

            $data["list"] = WorkflowStep::loadList($workflow->id);

            if (count($data["list"]) < 1) {
                $data["tabpage"] = "steps_blank";
                return true;
            }

            $data["tabpage"] = "steps_list";
            return true;
        }

        if (count($path) === 6 && $path[4] === "steps" && $path[5] === "create") {
            // create
            $form = new StepCreateForm();
            $form->init($workflow, $data["project"]->manualOrder);
            $form->process();
            $data["form"] = $form;

            $data["tabpage"] = "steps_form";
            $data["title"] = "Eingabeschritt anlegen";
            return true;
        }

        if (count($path) === 7 && $path[4] === "steps" && $path[6] === "edit") {
            // edit
            if (! intval($path[5]) > 0) {
                (new NotFoundController())->request($path);
                return false;
            }

            $step = WorkflowStep::load($path[5]);
            $data["step"] = $step;

            if ($step === null) {
                (new NotFoundController())->request($path);
                return false;
            }

            if (isset($_REQUEST["delete"])) {
                $step->delete($workflow, Application::$instance->user->id);
                Application::$instance->redirect("projects/".$data["project"]->id."/workflows/".$workflow->id."/steps", "deleted");
                return false;
            }

            $steps = WorkflowStep::loadList($workflow->id);
            $relationalColumns = RelationalColumn::loadList($step->tableId);
            $textualColumns = TextualColumn::loadList($step->tableId);

            $form = new StepEditForm();
            $form->init($workflow, $steps, $step, $relationalColumns, $textualColumns);
            $form->process();
            $data["form"] = $form;

            $data["tabpage"] = "steps_form";
            $data["title"] = "Eingabeschritt bearbeiten";
            return true;
        }

        (new NotFoundController())->request($path);
        return false;
    }
}
