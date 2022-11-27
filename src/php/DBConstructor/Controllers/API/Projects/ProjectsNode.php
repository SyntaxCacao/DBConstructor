<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects;

use DBConstructor\Application;
use DBConstructor\Controllers\API\APIController;
use DBConstructor\Controllers\API\InternalNode;
use DBConstructor\Controllers\API\NotFoundException;
use DBConstructor\Controllers\API\Projects\Participants\ParticipantsNode;
use DBConstructor\Controllers\API\Projects\Tables\TablesNode;
use DBConstructor\Models\Participant;
use DBConstructor\Models\Project;

class ProjectsNode extends InternalNode
{
    /** @var Participant|null */
    public static $participant;

    /** @var Project|null */
    public static $project;

    public function process($path): array
    {
        if (count($path) === 2) {
            return (new ProjectsListLeaf())->process($path);
        }

        if (intval($path[2]) === 0 || (self::$project = Project::load($path[2])) === null) {
            throw new NotFoundException();
        }

        if ((self::$participant = Participant::loadFromUser(self::$project->id, Application::$instance->user->id)) === null) {
            throw new NotFoundException();
        }

        APIController::$instance->requireScope(self::$project->id);

        if (count($path) === 3) {
            return (new ProjectsElementLeaf())->process($path);
        }

        if ($path[3] === "participants") {
            return (new ParticipantsNode())->process($path);
        }

        if ($path[3] === "tables") {
            return (new TablesNode())->process($path);
        }

        throw new NotFoundException();
    }
}
