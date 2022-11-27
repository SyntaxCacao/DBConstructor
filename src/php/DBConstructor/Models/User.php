<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class User
{
    const SESSION_USERID = "user_id";

    const HASH_ALGO = PASSWORD_BCRYPT;

    public static function countAdministrators(): int
    {
        MySQLConnection::$instance->execute("SELECT COUNT(*) AS `count` FROM `dbc_user` WHERE `admin` = TRUE");
        return intval(MySQLConnection::$instance->getSelectedRows()[0]["count"]);
    }

    public static function countAll(): int
    {
        MySQLConnection::$instance->execute("SELECT COUNT(*) AS `count` FROM `dbc_user`");
        return intval(MySQLConnection::$instance->getSelectedRows()[0]["count"]);
    }

    public static function countNotParticipating(string $projectId): int
    {
        MySQLConnection::$instance->execute("SELECT COUNT(*) AS `count` FROM `dbc_user` u LEFT JOIN `dbc_participant` p ON u.`id` = p.`user_id` WHERE u.`locked` = FALSE AND (SELECT COUNT(*) FROM `dbc_participant` p WHERE p.`user_id` = u.`id` AND p.`project_id` = ? AND p.`removed` IS NULl) = 0", [$projectId]);
        return intval(MySQLConnection::$instance->getSelectedRows()[0]["count"]);
    }

    public static function create(string $creatorId = null, string $username, string $firstname, string $lastname, string $password, bool $admin, bool $apiAccess): string
    {
        MySQLConnection::$instance->execute("INSERT INTO `dbc_user` (`creator_id`, `username`, `firstname`, `lastname`, `password`, `admin`, `apiaccess`) VALUES (?, ?, ?, ?, ?, ?, ?)", [$creatorId, $username, $firstname, $lastname, password_hash($password, User::HASH_ALGO), intval($admin), intval($apiAccess)]);

        return MySQLConnection::$instance->getLastInsertId();
    }

    public static function isUsernameAvailable(string $username): bool
    {
        MySQLConnection::$instance->execute("SELECT COUNT(*) AS `count` FROM `dbc_user` WHERE `username`=?", [$username]);
        $result = MySQLConnection::$instance->getSelectedRows();
        return $result[0]["count"] === "0";
    }

    /**
     * @return User|null
     */
    public static function loadId(string $id)
    {
        return User::loadSingle("SELECT * FROM `dbc_user` WHERE `id`=?", $id);
    }

    /**
     * @return array<array{obj: User, projects: string}>
     */
    public static function loadList(): array
    {
        MySQLConnection::$instance->execute("SELECT u.*, (SELECT COUNT(*) FROM `dbc_participant` p WHERE p.`user_id` = u.`id` AND p.`removed` IS NULL) AS `count` FROM `dbc_user` u ORDER BY u.`lastname`, u.`firstname`");
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $list[] = ["obj" => new User($row), "projects" => $row["count"]];
        }

        return $list;
    }

    /**
     * @return array<User>
     */
    public static function loadNotParticipatingList(string $projectId): array
    {
        MySQLConnection::$instance->execute("SELECT DISTINCT u.* FROM `dbc_user` u LEFT JOIN `dbc_participant` p ON u.`id` = p.`user_id` WHERE u.`locked` = FALSE AND (SELECT COUNT(*) FROM `dbc_participant` p WHERE p.`user_id` = u.`id` AND p.`project_id` = ? AND p.`removed` IS NULL) = 0 ORDER BY u.`lastname`, u.`firstname`", [$projectId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $list[] = new User($row);
        }

        return $list;
    }

    /**
     * @return User|null
     */
    protected static function loadSingle(string $sql, string $param)
    {
        MySQLConnection::$instance->execute($sql, [$param]);
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) != 1) {
            return null;
        }

        return new User($result[0]);
    }

    /**
     * @return User|null
     */
    public static function loadUsername(string $username)
    {
        return User::loadSingle("SELECT * FROM `dbc_user` WHERE `username`=?", $username);
    }

    /**
     * @return User|null
     */
    public static function loadWithCreator(string $id)
    {
        // @formatter:off
        return User::loadSingle("SELECT u.*, ".
                                            "c.`firstname` AS `creator_firstname`, ".
                                            "c.`lastname` AS `creator_lastname` ".
                                     "FROM `dbc_user` u ".
                                     "LEFT JOIN `dbc_user` c ON u.`creator_id` = c.`id` ".
                                     "WHERE u.`id`=?", $id);
        // @formatter:on
    }

    /** @var string */
    public $id;

    /** @var string|null */
    public $creatorFirstName;

    /** @var string|null */
    public $creatorLastName;

    /** @var string */
    public $username;

    /** @var string */
    public $firstname;

    /** @var string */
    public $lastname;

    /** @var string */
    public $password;

    /** @var bool */
    public $isAdmin;

    /** @var bool */
    public $hasApiAccess;

    /** @var bool */
    public $locked;

    /** @var string|null */
    public $firstLogin;

    /** @var string|null */
    public $lastLogin;

    /** @var string */
    public $created;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->username = $data["username"];
        $this->firstname = $data["firstname"];
        $this->lastname = $data["lastname"];
        $this->password = $data["password"];
        $this->isAdmin = $data["admin"] == "1";
        $this->hasApiAccess = $data["apiaccess"] == "1";
        $this->locked = $data["locked"] == "1";
        $this->firstLogin = $data["firstlogin"];
        $this->lastLogin = $data["lastlogin"];
        $this->created = $data["created"];

        if (isset($data["creator_firstname"])) {
            $this->creatorFirstName = $data["creator_firstname"];
        }

        if (isset($data["creator_lastname"])) {
            $this->creatorLastName = $data["creator_lastname"];
        }
    }

    public function edit(string $username, string $firstname, string $lastname, bool $admin, bool $apiAccess, bool $locked)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_user` SET `username`=?, `firstname`=?, `lastname`=?, `admin`=?, `apiaccess`=?, `locked`=? WHERE `id`=?", [$username, $firstname, $lastname, intval($admin), intval($apiAccess), intval($locked), $this->id]);
    }

    /**
     * lastLogin is being updated, but not changed locally
     */
    public function logIn()
    {
        if (is_null($this->firstLogin)) {
            MySQLConnection::$instance->execute("UPDATE `dbc_user` SET `firstlogin`=CURRENT_TIMESTAMP, `lastlogin`=CURRENT_TIMESTAMP WHERE `id`=?", [$this->id]);
        } else {
            MySQLConnection::$instance->execute("UPDATE `dbc_user` SET `lastlogin`=CURRENT_TIMESTAMP WHERE `id`=?", [$this->id]);
        }

        $_SESSION[User::SESSION_USERID] = $this->id;
    }

    public function setName(string $firstname, string $lastname)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_user` SET `firstname`=?, `lastname`=? WHERE `id`=?", [$firstname, $lastname, $this->id]);
        $this->firstname = $firstname;
        $this->lastname = $lastname;
    }

    /**
     * password is being updated, but not changed locally
     */
    public function setPassword(string $password)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_user` SET `password`=? WHERE `id`=?", [password_hash($password, User::HASH_ALGO), $this->id]);
    }

    public function setUsername(string $username)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_user` SET `username`=? WHERE `id`=?", [$username, $this->id]);
        $this->username = $username;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }
}
