<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Settings;

use DBConstructor\Application;
use DBConstructor\Controllers\Controller;
use DBConstructor\Controllers\ForbiddenController;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\Settings\Tokens\TokenCreateForm;
use DBConstructor\Controllers\Settings\Tokens\TokenEditForm;
use DBConstructor\Controllers\Settings\Tokens\TokenEnableForm;
use DBConstructor\Controllers\Settings\Tokens\TokenRenewForm;
use DBConstructor\Models\AccessToken;

class UserSettingsController extends Controller
{
    public function request(array $path)
    {
        if (count($path) === 1) {
            $nameForm = new NameChangeForm();
            $nameForm->init();
            $data["name-success"] = $nameForm->process();

            $passwordForm = new PasswordChangeForm();
            $passwordForm->init();
            $data["password-success"] = $passwordForm->process();

            $data["page"] = "user_settings";
            $data["title"] = "Einstellungen";
            $data["name-form"] = $nameForm;
            $data["password-form"] = $passwordForm;

            Application::$instance->callTemplate($data);
            return;
        }

        if (count($path) >= 2 && $path[1] === "tokens") {
            if (! (Application::$instance->user->isAdmin || Application::$instance->user->hasApiAccess)) {
                (new ForbiddenController())->request($path);
                return;
            }

            if (count($path) === 2) {
                // list
                $data["tokens"] = AccessToken::loadList(Application::$instance->user->id);
                $data["page"] = "user_settings_tokens_list";
                $data["title"] = "Personal Access Tokens";

                Application::$instance->callTemplate($data);
                return;
            }

            if (count($path) === 3 && $path[2] === "create") {
                // create
                $data["form"] = new TokenCreateForm();
                $data["form"]->init();
                $data["form"]->process();

                $data["page"] = "user_settings_tokens_form";
                $data["title"] = "Personal Access Token ".$data["form"]->getVerb();
                Application::$instance->callTemplate($data);
                return;
            }

            if (count($path) !== 4 || ! intval($path[2]) > 0 || ($token = AccessToken::load($path[2])) === null) {
                (new NotFoundController())->request($path);
                return;
            }

            $data["token"] = $token;

            if ($token->user->id !== Application::$instance->user->id) {
                (new ForbiddenController())->request($path);
                return;
            }

            if ($path[3] === "edit" || $path[3] === "renew" || ($path[3] === "enable" && $token->disabled)) {
                // edit, renew and enable
                if ($path[3] === "edit") {
                    $data["form"] = new TokenEditForm();
                } else if ($path[3] === "renew") {
                    $data["form"] = new TokenRenewForm();
                } else {
                    $data["form"] = new TokenEnableForm();
                }

                $data["form"]->init($token);
                $data["form"]->process();

                $data["page"] = "user_settings_tokens_form";
                $data["title"] = "Personal Access Token ".$data["form"]->getVerb();
                Application::$instance->callTemplate($data);
                return;
            }

            if ($path[3] === "disable" && ! $token->disabled) {
                // disable
                $token->setDisabled(true);
                Application::$instance->redirect("settings/tokens", "saved");
                return;
            }

            if ($path[3] === "delete") {
                // delete
                $token->delete();
                Application::$instance->redirect("settings/tokens", "saved");
                return;
            }
        }

        (new NotFoundController())->request($path);
    }
}
