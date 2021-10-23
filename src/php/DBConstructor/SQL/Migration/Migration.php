<?php

declare(strict_types=1);

namespace DBConstructor\SQL\Migration;

use DBConstructor\SQL\MySQLConnection;
use PDOException;

class Migration
{
    public static function apply(string $id, string $sql)
    {
        try {
            MySQLConnection::$instance->execute($sql);
            MySQLConnection::$instance->verifyMultiple();
            Migration::register($id, true);
        } catch (PDOException $exception) {
            Migration::register($id, false);
            throw $exception;
        }
    }

    public static function createTable()
    {
        // @formatter:off
        MySQLConnection::$instance->execute("CREATE TABLE IF NOT EXISTS `dbc_migration` (".
                                                   "`id` INT UNSIGNED NOT NULL PRIMARY KEY,".
                                                   "`success` BOOLEAN NOT NULL,".
                                                   "`time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP".
                                                 ")");
        // @formatter:on
    }

    /**
     * @return Migration|null
     */
    public static function loadLast()
    {
        MySQLConnection::$instance->execute("SELECT * FROM `dbc_migration` ORDER BY `id` DESC LIMIT 1");
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) == 0) {
            return null;
        }

        return new Migration($result[0]);
    }

    public static function register(string $id, bool $success)
    {
        MySQLConnection::$instance->execute("INSERT INTO `dbc_migration` (`id`, `success`) VALUES (?, ?)", [$id, intval($success)]);
    }

    /** @var string */
    public $id;

    /** @var bool */
    public $success;

    /** @var string */
    public $time;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->success = $data["success"] === "1";
        $this->time = $data["time"];
    }
}
