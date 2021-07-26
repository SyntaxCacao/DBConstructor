<?php

declare(strict_types=1);

namespace DBConstructor\Controllers;

use DBConstructor\Application;
use DBConstructor\Models\User;

class LoginController extends Controller
{
    const RESULT_CREDENTIALS_INCOMPLETE = "credentials-incomplete";

    const RESULT_CREDENTIALS_INCORRECT = "credentials-incorrect";

    const RESULT_LOGGED_OUT = "logged-out";

    const RESULT_LOCKED = "locked";

    public function isPublic(): bool
    {
        return true;
    }

    protected function do()
    {
        // Already logged in?
        if (Application::$instance->user != null) {
            if (isset($_REQUEST["logout"])) {
                session_destroy();
                return LoginController::RESULT_LOGGED_OUT;
            } else {
                // Already logged in, no logout intended
                Application::$instance->redirect();
                return null; // dead
            }
        }

        // Submitted?
        if (! isset($_REQUEST["username"])) {
            // Kicked?
            if (isset($_REQUEST["locked"])) {
                return LoginController::RESULT_LOCKED;
            }

            return null;
        }

        // Submission complete?
        if (empty($_REQUEST["username"]) || empty($_REQUEST["password"])) {
            return LoginController::RESULT_CREDENTIALS_INCOMPLETE;
        }

        // Credentials correct?
        $user = User::loadUsername($_REQUEST["username"]);

        if ($user == null || ! $user->verifyPassword($_REQUEST["password"])) {
            return LoginController::RESULT_CREDENTIALS_INCORRECT;
        }

        // Locked?
        if ($user->locked == true) {
            return LoginController::RESULT_LOCKED;
        }

        // Log in
        $user->logIn();

        if (empty($_REQUEST["return"])) {
            Application::$instance->redirect(null, "welcome");
        } else {
            header("Location: ".urldecode($_REQUEST["return"]));
        }
    }

    public function request(array $path)
    {
        if (count($path) != 1) {
            (new NotFoundController)->request($path);
            return;
        }

        $data["page"] = "login";
        $data["title"] = "Anmelden";
        $data["suppressNavbar"] = true;
        $data["result"] = $this->do();

        Application::$instance->callTemplate($data);
    }
}
