<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API;

use DBConstructor\Application;
use DBConstructor\Controllers\API\Projects\ProjectsNode;
use DBConstructor\Controllers\Controller;
use DBConstructor\Models\AccessToken;
use Throwable;

class APIController extends Controller
{
    /** @var APIController */
    public static $instance;

    /** @var array|null */
    public $scope;

    public function inScope(string $projectId, int $action = null): bool
    {
        if ($action === null) {
            return array_key_exists($projectId, $this->scope);
        } else {
            return isset($this->scope[$projectId]) && in_array($action, $this->scope[$projectId]);
        }
    }

    public function request(array $path)
    {
        try {
            header("X-DBC-Version: ".Application::$instance->version);

            // Authorization
            if (! isset($_SERVER["HTTP_X_AUTHORIZATION"])) {
                $this->respond(401, ["message" => "No access token provided"]);
                return;
            }

            $token = $_SERVER["HTTP_X_AUTHORIZATION"];
            $matches = [];

            if (! AccessToken::checkTokenFormat($token, $matches)) {
                $this->respond(401, ["message" => "Access token has invalid format"]);
                return;
            }

            if (($token = AccessToken::load($matches[1])) === null) {
                $this->respond(401, ["message" => "Unknown access token (was it deleted?)"]);
                return;
            }

            if (! $token->verify($matches[2])) {
                $this->respond(401, ["message" => "Access token is wrong (was it renewed?)"]);
                return;
            }

            if ($token->expires === null) {
                header("X-Token-Expires: Unlimited");
            } else {
                header("X-Token-Expires: ".$token->expires);
            }

            header("X-User-ID: ".$token->user->id);
            Application::$instance->user = $token->user;
            $this->scope = $token->getScope();

            if ($token->disabled) {
                $this->respond(403, ["message" => "Token is disabled"]);
                return;
            }

            if ($token->expired) {
                $this->respond(403, ["message" => "Token is expired"]);
                return;
            }

            if ($token->user->locked) {
                $this->respond(403, ["message" => "User is locked"]);
                return;
            }

            if (! ($token->user->isAdmin || $token->user->hasApiAccess)) {
                $this->respond(403, ["message" => "User is not permitted to use the API"]);
                return;
            }

            // Routing
            APIController::$instance = $this;

            if (count($path) === 1) {
                $this->respond(200, [
                    "greeting" => "Hello, ".Application::$instance->user->firstname."!"
                ]);
            } else if (count($path) === 2 && $path[1] === "user") {
                $this->respond(200, [
                    "id" => intval(Application::$instance->user->id),
                    "firstName" => Application::$instance->user->firstname,
                    "lastName" => Application::$instance->user->lastname,
                    "isAdmin" => Application::$instance->user->isAdmin,
                    "firstLogin" => Application::$instance->user->firstLogin,
                    "lastLogin" => Application::$instance->user->lastLogin
                ]);
            } else if ($path[1] === "projects") {
                $node = new ProjectsNode();
            }

            try {
                if (isset($node)) {
                    $this->respond(200, $node->process($path));
                } else {
                    $this->respond(404);
                }
            } catch (ForbiddenException $exception) {
                $this->respond(403, $exception->getMessage() === "" ? null : ["message" => $exception->getMessage()]);
            } catch (NotFoundException $exception) {
                $this->respond(404);
            } catch (MethodNotAllowedException $exception) {
                $this->respond(405, [
                    "message" => "Method ".$_SERVER["REQUEST_METHOD"]." is not allowed for this node"
                ]);
            } catch (UnprocessableException $exception) {
                $this->respond(422, $exception->getMessage() === "" ? null : ["message" => $exception->getMessage()]);
            }
        } catch (Throwable $throwable) {
            error_log("Unhandled ".get_class($throwable)." in ".$throwable->getFile()." on line ".$throwable->getLine().": ".$throwable->getMessage()." – while processing ".$_SERVER["REQUEST_METHOD"]." ".$_SERVER["REQUEST_URI"]);
            http_response_code(500);

            if (Application::$instance->config["development"]) {
                header("Content-Type: text/html; charset=utf-8");
                var_dump($throwable);
            }
        }
    }

    public function requireScope(string $projectId, int $action = null, string $message = null)
    {
        if (! $this->inScope($projectId, $action)) {
            $this->respond(403, $message === null ? null : ["message" => $message]);
        }
    }

    public function respond(int $status, array $body = null)
    {
        http_response_code($status);

        if (! empty($body)) {
            header("Content-Type: application/json; charset=utf-8");
            $json = json_encode($body);

            if ($json === false) {
                error_log("Could not encode request result to JSON");
                http_response_code(500);
                echo '{"message": "Could not encode request result to JSON"}';
                return;
            }

            echo $json;
        }

        exit;
    }
}
