<?php

declare(strict_types=1);

namespace DBConstructor;

use DBConstructor\Controllers\Exports\ExportsController;
use DBConstructor\Controllers\LoginController;
use DBConstructor\Controllers\MarkdownController;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\Projects\ProjectsController;
use DBConstructor\Controllers\Settings\UserSettingsController;
use DBConstructor\Controllers\Users\UsersController;
use DBConstructor\Controllers\ValidationController;
use DBConstructor\Models\User;
use DBConstructor\SQL\MySQLConnection;

class Application
{
    /** @var Application */
    public static $instance;

    /** @var array<string, string> */
    public $config = [];

    /** @var array<string> */
    public $path;

    /** @var User|null */
    public $user;

    public function run()
    {
        // Check for tmp dir
        if ((! file_exists("../tmp") || ! is_dir("../tmp")) && ! mkdir("../tmp")) {
            die("<code>tmp</code> directory not found and could not be created.");
        }

        if (! is_writable("../tmp")) {
            die("<code>tmp</code> is not writable.");
        }

        if ((! file_exists("../tmp/sessions") || ! is_dir("../tmp/sessions")) && ! mkdir("../tmp/sessions")) {
            die("<code>tmp/sessions</code> directory not found and could not be created.");
        }

        if (! is_writable("../tmp/sessions")) {
            die("<code>tmp/sessions</code> is not writable.");
        }

        if ((! file_exists("../tmp/exports") || ! is_dir("../tmp/exports")) && ! mkdir("../tmp/exports")) {
            die("<code>tmp/exports</code> directory not found and could not be created.");
        }

        if (! is_writable("../tmp/exports")) {
            die("<code>tmp/exports</code> is not writable.");
        }

        // Load up configuration file
        if (! file_exists("../tmp/config.php")) {
            die("Configuration file not found.");
        }

        $cfg = [];
        require "../tmp/config.php";

        $cfg["baseurl"] = trim($cfg["baseurl"], "/");
        $cfg["development"] = $cfg["development"] === true;
        $GLOBALS["developmentMode"] = $cfg["development"]; // exception handler
        $this->config = $cfg;

        // Reporting mode
        if ($cfg["development"]) {
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
        }

        // Process path
        $path = $_GET["path"];

        if ($path == "") {
            $path = "projects";
        }

        $path = strtolower($path);
        $path = trim($path, "/");
        $path = explode("/", $path);
        $this->path = $path;

        // Initalize session
        session_cache_limiter("nocache");
        session_name($cfg["cookies"]["prefix"]."session");
        session_set_cookie_params(30 * 24 * 60 * 60, $cfg["cookies"]["path"]);
        session_save_path("../tmp/sessions");
        session_start();

        // Establish MySQL connection
        MySQLConnection::$instance = new MySQLConnection($cfg["mysql"]["hostname"], $cfg["mysql"]["database"], $cfg["mysql"]["username"], $cfg["mysql"]["password"]);

        // Load User
        if (isset($_SESSION[User::SESSION_USERID])) {
            $this->user = User::loadId($_SESSION[User::SESSION_USERID]);

            if (! isset($this->user)) {
                session_destroy();
            }

            if ($this->user->locked == true) {
                session_destroy();
                $this->redirect("login", "locked");
            }
        }

        // Routing
        if ($path[0] == "login") {
            $controller = new LoginController();
        } else if ($path[0] == "projects") {
            $controller = new ProjectsController();
        } else if ($path[0] == "users") {
            $controller = new UsersController();
        } else if ($path[0] == "exports") {
            $controller = new ExportsController();
        } else if ($path[0] == "settings") {
            $controller = new UserSettingsController();
        } else if ($path[0] == "markdown") {
            $controller = new MarkdownController();
        } else if ($path[0] == "validation") {
            $controller = new ValidationController();
        }

        if (! isset($controller)) {
            if ($this->user == null) {
                $this->redirectToLogin();
                return;
            } else {
                $controller = new NotFoundController();
            }
        }

        if (! $controller->isPublic() && $this->user == null) {
            $this->redirectToLogin();
        }

        $controller->request($this->path);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function callTemplate(array $data = [])
    {
        $data["baseurl"] = $this->config["baseurl"];
        $data["user"] = $this->user;
        $data["isAdmin"] = $this->hasAdminPermissions();
        $data["request"] = $_REQUEST;

        require "templates/base.tpl.php";
    }

    public function hasAdminPermissions(): bool
    {
        return isset($this->user) && $this->user->isAdmin;
    }

    public function redirect(string $page = null, string $get = "")
    {
        if (! empty($get)) {
            $get = "?$get";
        }

        if ($page == null) {
            header("Location: ".$this->config["baseurl"]."/$get");
        } else {
            header("Location: ".$this->config["baseurl"]."/$page/$get");
        }

        exit;
    }

    public function redirectToLogin()
    {
        $this->redirect("login", "return=".urlencode($_SERVER["REQUEST_URI"]));
    }
}
