<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Workflows\Executions;

use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\Workflow;
use DBConstructor\Models\WorkflowExecution;
use DBConstructor\Models\WorkflowExecutionRow;
use DBConstructor\Models\WorkflowStep;

class ExecutionsController
{
    public function request(array $path, Workflow $workflow, array &$data): bool
    {
        if (count($path) === 4) {
            // execute
            if (! $workflow->active) {
                (new NotFoundController())->request($path);
                return false;
            }

            $steps = WorkflowStep::loadList($workflow->id);

            // TODO: what if no steps exist?

            $relationalColumns = [];
            $textualColumns = [];

            foreach ($steps as $step) {
                // TODO: Don't run SQL in foreach

                if (! isset($relationalColumns[$step->tableId])) {
                    $relationalColumns[$step->tableId] = RelationalColumn::loadList($step->tableId);
                }

                if (! isset($textualColumns[$step->tableId])) {
                    $textualColumns[$step->tableId] = TextualColumn::loadList($step->tableId);
                }
            }

            $form = new ExecutionForm();
            $form->init($workflow->id, $steps, $relationalColumns, $textualColumns);
            $form->process();
            $data["form"] = $form;

            $data["tabpage"] = "executions_form";
            return true;
        }

        if (count($path) === 5 && $path[4] === "executions") {
            // executions list
            $data["steps"] = WorkflowStep::loadList($workflow->id);

            // determine page
            $data["count"] = WorkflowExecution::count($workflow->id);
            $data["pages"] = WorkflowExecution::calcPages($data["count"]);

            if ($data["pages"] === 0) {
                $data["tabpage"] = "executions_blank";
                return true;
            }

            $data["currentPage"] = 1;

            if (isset($_GET["page"]) && intval($_GET["page"]) > 0 && intval($_GET["page"]) <= $data["pages"]) {
                $data["currentPage"] = intval($_GET["page"]);
            }

            $data["executions"] = WorkflowExecution::loadList($workflow->id, $data["currentPage"]);
            $data["executionRows"] = WorkflowExecutionRow::loadList($data["executions"]);

            $data["tabpage"] = "executions_list";
            return true;
        }

        (new NotFoundController())->request($path);
        return false;
    }
}
