<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Workflows;

use DBConstructor\Controllers\ForbiddenController;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\Projects\Workflows\Executions\ExecutionsController;
use DBConstructor\Controllers\Projects\Workflows\Steps\StepsController;
use DBConstructor\Controllers\TabController;
use DBConstructor\Models\Workflow;
use DBConstructor\Util\JsonException;

class WorkflowTab extends TabController
{
    public function __construct()
    {
        parent::__construct("Eingaberoutinen", "workflows", "pencil");
    }

    /**
     * @throws JsonException
     */
    public function request(array $path, array &$data): bool
    {
        if (count($path) === 3) {
            // list
            $workflows = Workflow::loadList($data["project"]->id, $data["isManager"] === false);

            if (count($workflows) === 0) {
                $data["tabpage"] = "blank";
                return true;
            }

            $data["list"] = $workflows;
            $data["tabpage"] = "list";
            return true;
        }

        if (count($path) === 4 && $path[3] === "create") {
            // create
            if (! $data["isManager"]) {
                (new ForbiddenController())->request($path);
                return false;
            }

            $form = new WorkflowForm();
            $form->init($data["project"]->id);
            $form->process();
            $data["form"] = $form;

            $data["tabpage"] = "form";
            $data["title"] = "Eingaberoutine anlegen";
            return true;
        }

        if (! ctype_digit($path[3])) {
            (new NotFoundController())->request($path);
            return false;
        }

        $workflow = Workflow::load($path[3], true);
        $data["workflow"] = $workflow;
        $data["title"] = $workflow->label;

        if ($workflow === null) {
            (new NotFoundController())->request($path);
            return false;
        }

        if (count($path) === 4 || (count($path) > 4 && $path[4] === "executions")) {
            // execute
            return (new ExecutionsController())->request($path, $workflow, $data);
        }

        if (count($path) >= 5 && $path[4] === "steps") {
            // steps
            return (new StepsController())->request($path, $workflow, $data);
        }

        if (count($path) === 5 && $path[4] === "edit") {
            // edit
            if (! $data["isManager"]) {
                (new ForbiddenController())->request($path);
                return false;
            }

            $form = new WorkflowForm();
            $form->init($data["project"]->id, $workflow);
            $form->process();
            $data["form"] = $form;

            $data["tabpage"] = "form";
            $data["title"] = "Eingaberoutine bearbeiten";
            return true;
        }

        (new NotFoundController())->request($path);
        return false;
    }
}
