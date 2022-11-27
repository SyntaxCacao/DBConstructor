<?php

declare(strict_types=1);

namespace DBConstructor\Models;

use DBConstructor\SQL\MySQLConnection;

class AccessToken
{
    const COLUMNS = "t.`id` AS `token_id`, t.`token` AS `token_token`, t.`label` AS `token_label`, t.`expires` AS `token_expires`, (t.`expires` IS NOT NULL AND t.`expires` < CURRENT_TIMESTAMP) AS `token_expired`, t.`scope` AS `token_scope`, t.`disabled` AS `token_disabled`, t.`renewed` AS `token_renewed`, t.`created` AS `token_created`";

    const EXPIRATION_12_HOURS = "0";

    const EXPIRATION_3_DAYS = "1";

    const EXPIRATION_7_DAYS = "2";

    const EXPIRATION_14_DAYS = "3";

    const EXPIRATION_30_DAYS = "4";

    const EXPIRATION_90_DAYS = "5";

    const EXPIRATION_NEVER = "6";

    const EXPIRATION_INTERVALS = [
        self::EXPIRATION_12_HOURS => "TIMESTAMPADD(HOUR, 12, CURRENT_TIMESTAMP)",
        self::EXPIRATION_3_DAYS => "TIMESTAMPADD(DAY, 3, CURRENT_TIMESTAMP)",
        self::EXPIRATION_7_DAYS => "TIMESTAMPADD(DAY, 7, CURRENT_TIMESTAMP)",
        self::EXPIRATION_14_DAYS => "TIMESTAMPADD(DAY, 14, CURRENT_TIMESTAMP)",
        self::EXPIRATION_30_DAYS => "TIMESTAMPADD(DAY, 30, CURRENT_TIMESTAMP)",
        self::EXPIRATION_90_DAYS => "TIMESTAMPADD(DAY, 90, CURRENT_TIMESTAMP)",
        self::EXPIRATION_NEVER => "NULL"
    ];

    const EXPIRATION_LABELS = [
        self::EXPIRATION_12_HOURS => "12 Stunden",
        self::EXPIRATION_3_DAYS => "3 Tage",
        self::EXPIRATION_7_DAYS => "7 Tage",
        self::EXPIRATION_14_DAYS => "14 Tage",
        self::EXPIRATION_30_DAYS => "30 Tage",
        self::EXPIRATION_90_DAYS => "90 Tage",
        self::EXPIRATION_NEVER => "Unbegrenzt"
    ];

    const HASH_ALGO = PASSWORD_BCRYPT;

    const SCOPE_PROJECT_DELETE = 2;

    const SCOPE_PROJECT_STRUCTURE = 3;

    const SCOPE_PROJECT_UPLOAD = 1;

    const SCOPE_PROJECT_WRITE = 0;

    public static function checkTokenFormat(string $input, array &$matches): bool
    {
        return preg_match("/^(\d+)\/([\da-f]{48})$/", $input, $matches) === 1;
    }

    public static function create(string $userId, string $label = null, string $expirationInterval, array $scope): string
    {
        if (count($scope) > 0) {
            $scope = json_encode($scope);
        } else {
            $scope = null;
        }

        $token = self::generateToken();
        $hash = password_hash($token, self::HASH_ALGO);

        MySQLConnection::$instance->execute("INSERT INTO `dbc_accesstoken` (`user_id`, `token`, `label`, `expires`, `scope`) VALUES (?, ?, ?, ".self::EXPIRATION_INTERVALS[$expirationInterval].", ?)", [$userId, $hash, $label, $scope]);

        return self::formatToken(MySQLConnection::$instance->getLastInsertId(), $token);
    }

    public static function formatToken(string $id, string $token): string
    {
        return $id."/".$token;
    }

    public static function generateToken(): string
    {
        return bin2hex(openssl_random_pseudo_bytes(24));
    }

    /**
     * @return AccessToken|null
     */
    public static function load(string $id)
    {
        MySQLConnection::$instance->execute("SELECT u.*, ".self::COLUMNS." FROM `dbc_accesstoken` t LEFT JOIN `dbc_user` u ON u.`id` = t.`user_id` WHERE t.`id`=?", [$id]);
        $result = MySQLConnection::$instance->getSelectedRows();

        if (count($result) !== 1) {
            return null;
        }

        return new AccessToken($result[0]);
    }

    /**
     * @return array<AccessToken>
     */
    public static function loadList(string $userId): array
    {
        MySQLConnection::$instance->execute("SELECT ".self::COLUMNS." FROM `dbc_accesstoken` t WHERE t.`user_id`=? ORDER BY t.`expires` IS NOT NULL, t.`expires` DESC", [$userId]);
        $result = MySQLConnection::$instance->getSelectedRows();
        $list = [];

        foreach ($result as $row) {
            $list[] = new AccessToken($row);
        }

        return $list;
    }

    /** @var string */
    public $id;

    /** @var User|null */
    public $user;

    /** @var string */
    public $token;

    /** @var string|null */
    public $label;

    /** @var string|null */
    public $expires;

    /** @var bool|null */
    public $expired;

    /** @var string|null */
    public $scope;

    /** @var array|null */
    public $scopeDecoded;

    /** @var bool */
    public $disabled;

    /** @var string|null */
    public $renewed;

    /** @var string */
    public $created;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["token_id"];
        $this->token = $data["token_token"];
        $this->label = $data["token_label"];
        $this->expires = $data["token_expires"];
        $this->expired = $data["token_expired"] === "1";
        $this->scope = $data["token_scope"];
        $this->disabled = $data["token_disabled"] === "1";
        $this->renewed = $data["token_renewed"];
        $this->created = $data["token_created"];

        if (isset($data["id"])) {
            $this->user = new User($data);
        }
    }

    public function delete()
    {
        MySQLConnection::$instance->execute("DELETE FROM `dbc_accesstoken` WHERE `id`=?", [$this->id]);
    }

    public function edit(string $label = null, array $scope)
    {
        if (count($scope) > 0) {
            $scope = json_encode($scope);
        } else {
            $scope = null;
        }

        MySQLConnection::$instance->execute("UPDATE `dbc_accesstoken` SET `label`=?, `scope`=? WHERE `id`=?", [$label, $scope, $this->id]);
    }

    public function getScope(): array
    {
        if ($this->scopeDecoded === null) {
            if ($this->scope === null) {
                $this->scopeDecoded = [];
            } else {
                $this->scopeDecoded = json_decode($this->scope, true);
            }
        }

        return $this->scopeDecoded;
    }

    public function renew(string $expirationInterval): string
    {
        $token = self::generateToken();
        $hash = password_hash($token, self::HASH_ALGO);

        MySQLConnection::$instance->execute("UPDATE `dbc_accesstoken` SET `token`=?, `expires`=".self::EXPIRATION_INTERVALS[$expirationInterval].", `renewed`=CURRENT_TIMESTAMP WHERE `id`=?", [$hash, $this->id]);

        return self::formatToken($this->id, $token);
    }

    public function setDisabled(bool $disabled)
    {
        MySQLConnection::$instance->execute("UPDATE `dbc_accesstoken` SET `disabled`=? WHERE `id`=?", [intval($disabled), $this->id]);
    }

    public function verify(string $input): bool
    {
        return password_verify($input, $this->token);
    }
}
