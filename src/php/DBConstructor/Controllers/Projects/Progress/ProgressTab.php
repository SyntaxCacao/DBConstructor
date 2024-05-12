<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Progress;

use DBConstructor\Application;
use DBConstructor\Controllers\ForbiddenController;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\Projects\ProjectsController;
use DBConstructor\Controllers\TabController;
use DBConstructor\Models\Participant;
use DBConstructor\Models\RowProgressLoader;
use DBConstructor\Models\Table;
use Exception;

class ProgressTab extends TabController
{
    public function __construct()
    {
        // TODO: Icon seems to be called graph-up-arrow in more recent bootstrap-icons versions
        parent::__construct("Fortschritt", "progress", "graph-up");
    }

    /**
     * @throws Exception
     */
    public function request(array $path, array &$data): bool
    {
        $data["total"] = RowProgressLoader::loadTotal(ProjectsController::$projectId);
        $data["totalUser"] = RowProgressLoader::loadTotal(ProjectsController::$projectId, Application::$instance->user->id);

        if (count($path) === 3) {
            $data["currentProgressPage"] = "/";
            $data["title"] = "Fortschritt";

            $participants = Participant::loadList(ProjectsController::$projectId, true, true);

            if (! $data["isManager"]) {
                // Limit access for non-managers to their own progress data
                if (array_key_exists(Application::$instance->user->id, $participants)) {
                    $participants = [
                        Application::$instance->user->id => $participants[Application::$instance->user->id]
                    ];
                } else {
                    $participants = [];
                }
            }

            $filter = new TotalProgressFilterForm();
            $filter->init(Table::loadList($data["project"]->id, $data["project"]->manualOrder, true), $participants);
            $filter->process();
            $data["filter"] = $filter;

            $data["progress"] = RowProgressLoader::loadProgress(ProjectsController::$projectId, $filter->tableId, $filter->userId, $filter->includeApi, $filter->period);
            return true;
        }

        if (count($path) === 4 && $path[3] === "participants") {
            if (! ProjectsController::$isManager) {
                (new ForbiddenController())->request($path);
                return false;
            }

            $data["currentProgressPage"] = "/participants/";
            $data["title"] = "Fortschritt";

            $filter = new TotalProgressFilterForm();
            $filter->init(Table::loadList($data["project"]->id, $data["project"]->manualOrder, true));
            $filter->process();
            $data["filter"] = $filter;

            $data["progress"] = RowProgressLoader::loadProgressPerUser(ProjectsController::$projectId, $filter->tableId, $filter->includeApi, $filter->period);

            return true;
        }

        if (count($path) === 4 && $path[3] === "tabular") {
            if (! ProjectsController::$isManager) {
                (new ForbiddenController())->request($path);
                return false;
            }

            $data["currentProgressPage"] = "/tabular/";
            $data["title"] = "Fortschritt";

            $filter = new TotalUserProgressFilterForm();
            $filter->init(Table::loadList($data["project"]->id, $data["project"]->manualOrder, true));
            $filter->process();
            $data["filter"] = $filter;

            $data["totals"] = RowProgressLoader::loadTotalPerUser(ProjectsController::$projectId, $filter->tableId, $filter->includeApi, $filter->endDate);
            $data["allUsers"] = 0;

            foreach ($data["totals"] as $value) {
                $data["allUsers"] += $value;
            }

            $data["percentages"] = [];

            foreach ($data["totals"] as $userId => $value) {
                $data["percentages"][$userId] = round(($value / $data["allUsers"]) * 100, 1);
            }

            $data["participants"] = Participant::loadList(ProjectsController::$projectId, true);

            return true;
        }

        (new NotFoundController())->request($path);
        return false;
    }
}
