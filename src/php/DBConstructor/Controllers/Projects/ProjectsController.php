<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects;

use DBConstructor\Application;
use DBConstructor\Controllers\Controller;
use DBConstructor\Controllers\ForbiddenController;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\Projects\Exports\ExportsTab;
use DBConstructor\Controllers\Projects\Participants\ParticipantsTab;
use DBConstructor\Controllers\Projects\Routines\RoutinesTab;
use DBConstructor\Controllers\Projects\Settings\SettingsTab;
use DBConstructor\Controllers\Projects\Tables\TablesTab;
use DBConstructor\Controllers\Projects\Wiki\WikiTab;
use DBConstructor\Controllers\TabRouter;
use DBConstructor\Models\Project;

class ProjectsController extends Controller
{
    public function request(array $path)
    {
        // /projects/
        if (count($path) == 1) {
            $data["participatingProjects"] = Project::loadParticipating(Application::$instance->user->id);

            if (Application::$instance->hasAdminPermissions()) {
                $data["allProjects"] = Project::loadAll();
            }

            $data["page"] = "projects_list";
            $data["title"] = "Projekte";

            Application::$instance->callTemplate($data);
            return;
        }

        // /projects/create/
        if (count($path) == 2 && $path[1] == "create") {
            if (! Application::$instance->hasAdminPermissions()) {
                (new ForbiddenController)->request($path);
                return;
            }

            $form = new ProjectForm();
            $form->init();
            $form->process();
            $data["form"] = $form;

            $data["page"] = "projects_create";
            $data["title"] = "Projekt anlegen";

            Application::$instance->callTemplate($data);
            return;
        }

        // project pages - identify project
        if (intval($path[1]) == 0) {
            // => not int
            // TODO: possibly redundant, string is inserted in Project::load anyway
            (new NotFoundController())->request($path);
            return;
        }

        $project = Project::load($path[1]);
        $data["project"] = $project;

        if (is_null($project)) {
            (new NotFoundController())->request($path);
            return;
        }

        // tabs
        $tabRouter = new TabRouter();
        $tabRouter->register(new TablesTab(), true);
        $tabRouter->register(new WikiTab());
        $tabRouter->register(new RoutinesTab());
        $tabRouter->register(new ExportsTab());
        $tabRouter->register(new ParticipantsTab());
        $tabRouter->register(new SettingsTab());

        if ($tabRouter->route($path, 2, $data)) {
            $data["project-tabs"] = $tabRouter;

            $data["page"] = "projects_view";

            if (isset($data["title"])) {
                $data["title"] .= " · ".$project->label;
            } else {
                $data["title"] = $tabRouter->current->label." · ".$project->label;
            }

            Application::$instance->callTemplate($data);
        }
    }
}
