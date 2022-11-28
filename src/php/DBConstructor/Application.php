<?php

declare(strict_types=1);

namespace DBConstructor;

use DBConstructor\Controllers\API\APIController;
use DBConstructor\Controllers\Exports\ExportsController;
use DBConstructor\Controllers\FindController;
use DBConstructor\Controllers\LoginController;
use DBConstructor\Controllers\NotFoundController;
use DBConstructor\Controllers\Projects\ProjectsController;
use DBConstructor\Controllers\Settings\UserSettingsController;
use DBConstructor\Controllers\Users\UsersController;
use DBConstructor\Controllers\XHRController;
use DBConstructor\Models\User;
use DBConstructor\SQL\Migration\MigrationTool;
use DBConstructor\SQL\MySQLConnection;
use Exception;

class Application
{
    /** @var Application */
    public static $instance;

    /** @var array<string, string> */
    public $config = [];

    /** @var array<string> */
    public $modals = [];

    /** @var array<string> */
    public $path;

    /** @var User|null */
    public $user;

    /** @var string */
    public $version;

    /**
     * @throws Exception
     */
    public function run()
    {
        // Make sure all required directories exist
        try {
            $this->checkDir("tmp");
            $this->checkDir("tmp/attachments");
            $this->checkDir("tmp/exports");
            $this->checkDir("tmp/sessions");
        } catch (Exception $exception) {
            die($exception->getMessage());
        }

        // Load up version number
        if (! file_exists("../version.txt")) {
            die("Version file not found.");
        }

        $this->version = file_get_contents("../version.txt");

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

        // Establish MySQL connection
        MySQLConnection::$instance = new MySQLConnection($cfg["mysql"]["hostname"], $cfg["mysql"]["database"], $cfg["mysql"]["username"], $cfg["mysql"]["password"]);

        // Update database scheme
        if (count($path) == 1 && $path[0] == "migrate" && isset($_REQUEST["key"]) && isset($this->config["migratekey"])) {
            if ($_REQUEST["key"] === $this->config["migratekey"]) {
                MigrationTool::run();
            } else {
                echo "<p>Wrong key.</p>";
            }

            exit;
        }

        // API access?
        if ($path[0] == "api") {
            (new APIController())->request($path);
            return;
        }

        // Initalize session
        session_cache_limiter("nocache");
        session_name($cfg["cookies"]["prefix"]."session");
        session_set_cookie_params(30 * 24 * 60 * 60, $cfg["cookies"]["path"]);
        session_save_path("../tmp/sessions");
        session_start();

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
            // TODO: Remove after implementation of installer
            if (User::countAll() === 0) {
                User::create(null, "admin", "Vorname", "Nachname", "admin", true, false);
            }

            $controller = new LoginController();
        } else if ($path[0] == "projects") {
            $controller = new ProjectsController();
        } else if ($path[0] == "users") {
            $controller = new UsersController();
        } else if ($path[0] == "exports") {
            $controller = new ExportsController();
        } else if ($path[0] == "settings") {
            $controller = new UserSettingsController();
        } else if ($path[0] == "find") {
            $controller = new FindController();
        } else if ($path[0] == "xhr") {
            $controller = new XHRController();
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
        $data["version"] = $this->version;

        require "templates/base.tpl.php";
    }

    /**
     * @throws Exception
     */
    public function checkDir(string $path)
    {
        if ((! file_exists("../$path") || ! is_dir("../$path")) && ! mkdir("../$path")) {
            throw new Exception("<code>$path</code> directory not found and could not be created.");
        }

        if (! is_writable("../$path")) {
            throw new Exception("<code>$path</code> is not writable.");
        }
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
