<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects;

use DBConstructor\Application;
use DBConstructor\Controllers\API\APIController;
use DBConstructor\Controllers\API\LeafNode;
use DBConstructor\Models\Project;

class ProjectsListLeaf extends LeafNode
{
    public function get(array $path): array
    {
        $projects = Project::loadParticipating(Application::$instance->user->id);
        $result = [];

        foreach ($projects as $project) {
            if (! APIController::$instance->inScope($project->id)) {
                continue;
            }

            $result[] = [
                "id" => intval($project->id),
                "label" => $project->label,
                "description" => $project->description,
                "created" => $project->created
            ];
        }

        return $result;
    }
}
