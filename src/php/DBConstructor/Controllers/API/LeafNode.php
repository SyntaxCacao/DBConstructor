<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API;

use Exception;

abstract class LeafNode
{
    /**
     * @throws MethodNotAllowedException If method is not implemented by child class
     * @throws Exception If something goes wrong during execution
     */
    public function delete(array $path): array
    {
        throw new MethodNotAllowedException();
    }

    /**
     * @throws MethodNotAllowedException If method is not implemented by child class
     * @throws Exception If something goes wrong during execution
     */
    public function get(array $path): array
    {
        throw new MethodNotAllowedException();
    }

    /**
     * @throws MethodNotAllowedException If method is not implemented by child class
     * @throws Exception If something goes wrong during execution
     */
    public function patch(array $path): array
    {
        throw new MethodNotAllowedException();
    }

    /**
     * @throws MethodNotAllowedException If method is not implemented by child class
     * @throws Exception If something goes wrong during execution
     */
    public function post(array $path): array
    {
        throw new MethodNotAllowedException();
    }

    /**
     * @throws MethodNotAllowedException If method is not implemented by child class
     */
    public final function process(array $path): array
    {
        $method = $_SERVER["REQUEST_METHOD"];

        if ($method === "GET" || $method === "HEAD") {
            return $this->get($path);
        } else if ($method === "POST") {
            return $this->post($path);
        } else if ($method === "PUT") {
            return $this->put($path);
        } else if ($method === "PATCH") {
            return $this->patch($path);
        } else if ($method === "DELETE") {
            return $this->delete($path);
        }

        // TODO OPTIONS method?

        throw new MethodNotAllowedException();
    }

    protected final function processPayload()
    {
        $payload = json_decode(file_get_contents("php://input"), true);

        if ($payload === null) {
            APIController::$instance->respond(422, [
                "message" => "Request failed to provide valid JSON in payload"
            ]);
        }

        return $payload;
    }

    /**
     * @param array<string, array{type: string, default: mixed}> $params
     * @throws Exception
     */
    protected final function processPayloadParameters(array $params): array
    {
        $payload = self::processPayload();

        $result = [];
        $missing = [];

        foreach ($params as $name => $options) {
            if (isset($payload[$name])) {
                $value = $payload[$name];

                if ($options["type"] === "boolean") {
                    if (! is_bool($value)) {
                        APIController::$instance->respond(422, [
                            "message" => "Request failed to provide valid value for parameter $name of type {$options["type"]}"
                        ]);
                    }
                } else if ($options["type"] === "integer") {
                    if (! is_int($value)) {
                        APIController::$instance->respond(422, [
                            "message" => "Request failed to provide valid value for parameter $name of type {$options["type"]}"
                        ]);
                    }
                } else if ($options["type"] === "options") {
                    if (! isset($options["options"])) {
                        throw new Exception("Options not set for parameter $name");
                    }

                    if (! is_string($value)) {
                        APIController::$instance->respond(422, [
                            "message" => "Request failed to provide valid value for parameter $name of type {$options["type"]}"
                        ]);
                    }

                    if (array_key_exists($value, $options["options"])) {
                        $value = $options["options"][$value];
                    } else {
                        $message = "Request failed to provide valid option for parameter $name – options are: ";
                        $first = true;

                        foreach ($options["options"] as $option => $resultValue) {
                            if ($first) {
                                $first = false;
                            } else {
                                $message .= ", ";
                            }

                            $message .= $option;
                        }

                        APIController::$instance->respond(422, [
                            "message" => $message
                        ]);
                    }
                } else if ($options["type"] === "string") {
                    if (! is_string($value)) {
                        APIController::$instance->respond(422, [
                            "message" => "Request failed to provide valid value for parameter $name of type {$options["type"]}"
                        ]);
                    }
                } else {
                    throw new Exception("Illegal type for parameter $name");
                }

                $result[$name] = $value;
            } else {
                if (array_key_exists("default", $options)) {
                    $result[$name] = $options["default"];
                } else {
                    $missing[] = $name;
                }
            }
        }

        if (count($missing) > 0) {
            $missingStr = "";

            foreach ($missing as $i => $name) {
                if ($i > 0) {
                    $missingStr .= ", ";
                }

                $missingStr .= $name;
            }

            APIController::$instance->respond(422, [
                "message" => "Request failed to provide required parameter".(count($missing) > 1 ? "s" : "")." ".$missingStr
            ]);
        }

        return $result;
    }

    /**
     * @param array<string, array{type: string, default: mixed}> $params
     * @throws Exception
     */
    protected final function processQueryParameters(array $params): array
    {
        $result = [];
        $missing = [];

        foreach ($params as $name => $options) {
            if (isset($_GET[$name])) {
                $value = $_GET[$name];

                if ($options["type"] === "boolean") {
                    if ($value === "true" || $value === "1") {
                        $value = true;
                    } else if ($value === "false" || $value === "0") {
                        $value = false;
                    } else {
                        APIController::$instance->respond(422, [
                            "message" => "Request failed to provide valid value for parameter ".$name." of type ".$options["type"]
                        ]);
                    }
                } else if ($options["type"] === "integer") {
                    if ($value === "0" || intval($value) !== 0) {
                        $value = intval($value);
                    } else {
                        APIController::$instance->respond(422, [
                            "message" => "Request failed to provide valid value for parameter ".$name." of type ".$options["type"]
                        ]);
                    }
                } else if ($options["type"] === "integerString") {
                    if ($value === "0" || intval($value) !== 0) {
                        $value = (string) intval($value);
                    } else {
                        APIController::$instance->respond(422, [
                            "message" => "Request failed to provide valid value for parameter ".$name." of type ".$options["type"]
                        ]);
                    }
                } else if ($options["type"] === "options") {
                    if (! isset($options["options"])) {
                        throw new Exception("Options not set for parameter ".$name);
                    }

                    if (array_key_exists($value, $options["options"])) {
                        $value = $options["options"][$value];
                    } else {
                        $message = "Request failed to provide valid option for parameter ".$name." – options are: ";
                        $first = true;

                        foreach ($options["options"] as $option => $resultValue) {
                            if ($first) {
                                $first = false;
                            } else {
                                $message .= ", ";
                            }

                            $message .= $option;
                        }

                        APIController::$instance->respond(422, [
                            "message" => $message
                        ]);
                    }
                } else if ($options["type"] !== "string") {
                    throw new Exception("Illegal type for parameter ".$name);
                }

                $result[$name] = $value;
            } else {
                if (array_key_exists("default", $options)) {
                    $result[$name] = $options["default"];
                } else {
                    $missing[] = $name;
                }
            }
        }

        if (count($missing) > 0) {
            $missingStr = "";

            foreach ($missing as $i => $name) {
                if ($i > 0) {
                    $missingStr .= ", ";
                }

                $missingStr .= $name;
            }

            APIController::$instance->respond(422, [
                "message" => "Request failed to provide required parameter".(count($missing) > 1 ? "s" : "")." ".$missingStr
            ]);
        }

        return $result;
    }

    /**
     * @throws MethodNotAllowedException If method is not implemented by child class
     * @throws Exception If something goes wrong during execution
     */
    public function put(array $path): array
    {
        throw new MethodNotAllowedException();
    }
}
