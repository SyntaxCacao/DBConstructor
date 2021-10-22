<?php

declare(strict_types=1);

namespace DBConstructor\SQL;

use PDO;
use PDOException;
use PDOStatement;

/**
 * On closing the connection:
 *
 * The connection remains active for the lifetime of that PDO object. To close the connection,
 * you need to destroy the object by ensuring that all remaining references to it are deleted--
 * you do this by assigning null to the variable that holds the object. If you don't do this
 * explicitly, PHP will automatically close the connection when your script ends.
 * -- https://www.php.net/manual/en/pdo.connections.php
 */
class MySQLConnection
{
    /** @var MySQLConnection */
    public static $instance;

    /** @var string */
    protected $database;

    /** @var string */
    protected $hostname;

    /** @var string */
    protected $username;

    /** @var string */
    protected $password;

    /** @var PDO */
    protected $connection;

    /** @var PDOStatement */
    protected $statement;

    public function __construct(string $hostname, string $database, string $username, string $password)
    {
        $this->database = $database;
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * A connection will be established automatically when (and only if) execute() is called for the first time.
     */
    protected function connect()
    {
        try {
            $this->connection = new PDO("mysql:host=$this->hostname;dbname=$this->database;charset=utf8", $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            error_log("Could not connect to MySQL-server at $this->hostname. Message: ".$exception->getMessage());
            die("Database connection could not be established.");
        }
    }

    /**
     * @param array<string, mixed> $parameters
     * @throws PDOException
     */
    public function execute(string $sql, array $parameters = [])
    {
        // Establishes connection, if not yet connected
        if ($this->connection == null) {
            $this->connect();
        }

        // Closes opened statement, if one exists
        $this->statement = null;

        // Prepares a new statement
        $this->statement = $this->connection->prepare($sql);

        // Executes statement after binding parameters
        $this->statement->execute($parameters);
    }

    /**
     * To be used with {@link MySQLConnection::prepare()}
     *
     * @param array<string, mixed> $parameters
     */
    public function executePrepared(array $parameters = [])
    {
        $this->statement->execute($parameters);
    }

    public function getLastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * @return array<array<string, string>>
     */
    public function getSelectedRows(): array
    {
        $result = [];
        $statement = $this->statement;
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        foreach ($statement as $row) {
            $result[] = $row;
        }

        return $result;
    }

    public function prepare(string $sql)
    {
        // Establishes connection, if not yet connected
        if ($this->connection == null) {
            $this->connect();
        }

        // Closes opened statement, if one exists
        $this->statement = null;

        $this->statement = $this->connection->prepare($sql);
    }
}
