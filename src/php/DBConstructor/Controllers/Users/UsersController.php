<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Users;

use DBConstructor\Application;
use DBConstructor\Controllers\Controller;
use DBConstructor\Controllers\ForbiddenController;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Models\User;

class UsersController extends Controller
{
    public function request(array $path)
    {
        if (count($path) == 1) {
            $data["page"] = "users_list";
            $data["title"] = "Benutzer";
            $data["users"] = User::loadList();

            Application::$instance->callTemplate($data);
        } else if (count($path) == 2 && $path[1] == "create") {
            if (! Application::$instance->hasAdminPermissions()) {
                (new ForbiddenController())->request($path);
                return;
            }

            $form = new UserForm();
            $form->init();
            $form->process();

            $data["page"] = "users_form";
            $data["title"] = "Benutzer erstellen";
            $data["form"] = $form;

            Application::$instance->callTemplate($data);
        } else if (count($path) == 3 && $path[2] == "edit") {
            $id = intval($path[1]);

            if ($id == 0) {
                (new NotFoundController())->request($path);
                return;
            }

            $user = User::loadId($path[1]);

            if ($user == null) {
                (new NotFoundController())->request($path);
                return;
            }

            if (! Application::$instance->hasAdminPermissions()) {
                (new ForbiddenController())->request($path);
                return;
            }

            $form = new UserForm();
            $form->init($user);
            $form->process();

            $data["page"] = "users_form";
            $data["title"] = "Benutzer bearbeiten";
            $data["form"] = $form;

            Application::$instance->callTemplate($data);
        } else {
            (new NotFoundController())->request($path);
            return;
        }
    }
}
