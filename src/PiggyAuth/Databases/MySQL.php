<?php

namespace PiggyAuth\Databases;

use PiggyAuth\Tasks\MySQLTask;
use PiggyAuth\Main;

use pocketmine\Player;

/**
 * Class MySQL
 * @package PiggyAuth\Databases
 */
class MySQL implements Database
{
    private $plugin;
    public $db;

    /**
     * MySQL constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
        $credentials = $this->plugin->getConfig()->get("mysql");
        $this->db = new \mysqli($credentials["host"], $credentials["user"], $credentials["password"], $credentials["name"], $credentials["port"]);
        $task = new MySQLTask($credentials, "CREATE TABLE IF NOT EXISTS players (name VARCHAR(100) PRIMARY KEY, password VARCHAR(200), email VARCHAR(100), pin INT, ip VARCHAR(32), uuid VARCHAR(100), attempts INT, xbox BIT(1), language VARCHAR(3), auth VARCHAR(100));");
        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
        //Updater
        $result = $this->db->query("SELECT * FROM players");
        if ($result instanceof \mysqli_result) {
            $data = $result->fetch_assoc();
            if (!isset($data["ip"])) {
                $this->db->query("ALTER TABLE players ADD ip VARCHAR(32) NOT NULL");
            }
            if (!isset($data["language"])) {
                $this->db->query("ALTER TABLE players ADD language VARCHAR(3) NOT NULL");
            }
            if (!isset($data["auth"])) {
                $this->db->query("ALTER TABLE players ADD auth VARCHAR(10) NOT NULL");
            }
        }
    }

    /**
     * @param callable|null $callback
     * @param null $args
     */
    public function getRegisteredCount(callable $callback = null, $args = null)
    {
        $task = new MySQLTask($this->plugin->getConfig()->get("mysql"), "SELECT count(1) FROM players", $callback, $args);
        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
    }

    /**
     * @param $player
     * @return array|null
     */
    public function getOfflinePlayer($player)
    { //@S0F3, don't turn me into bacon for this
        $player = strtolower($player);
        $result = $this->db->query("SELECT * FROM players WHERE name = '" . $this->db->escape_string(strtolower($player)) . "'");
        if ($result instanceof \mysqli_result) {
            $data = $result->fetch_assoc();
            $result->free();
            return $data;
        }
        return null;
    }

    /**
     * @param $player
     * @param $callback
     * @param $args
     * @return mixed|void
     */
    public function getPlayer($player, callable $callback = null, $args = null)
    {
        $player = strtolower($player);
        $task = new MySQLTask($this->plugin->getConfig()->get("mysql"), "SELECT * FROM players WHERE name = '" . $this->db->escape_string($player) . "'", $callback, $args);
        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
    }

    /**
     * @param $player
     * @param $column
     * @param $arg
     * @param int $type
     * @param callable|null $callback
     * @param null $args
     * @return mixed|void
     */
    public function updatePlayer($player, $column, $arg, $type = SQLITE3_TEXT, callable $callback = null, $args = null)
    {
        if ($type == 0) {
            $arg = $this->db->escape_string($arg);
        } else {
            $arg = intval($arg);
        }
        $task = new MySQLTask($this->plugin->getConfig()->get("mysql"), "UPDATE players SET " . $column . " = '" . $arg . "' WHERE name = '" . $this->db->escape_string(strtolower($player)) . "'", $callback, $args);
        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
    }

    /**
     * @param Player $player
     * @param $password
     * @param $email
     * @param $pin
     * @param $xbox
     * @param callable|null $callback
     * @param null $args
     * @return mixed|void
     */
    public function insertData(Player $player, $password, $email, $pin, $xbox, callable $callback = null, $args = null)
    {
        $task = new MySQLTask($this->plugin->getConfig()->get("mysql"), "INSERT INTO players (name, password, email, pin, uuid, attempts, xbox, language, auth) VALUES ('" . $this->db->escape_string(strtolower($player->getName())) . "', '" . $this->db->escape_string($password) . "', '" . $this->db->escape_string($email) . "', '" . intval($pin) . "', '" . $player->getUniqueId()->toString() . "', '0', '" . $xbox . "', '" . $this->db->escape_string($this->plugin->getLanguageManager()->getDefaultLanguage()) . "', 'PiggyAuth')", $callback, $args);
        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
    }

    /**
     * @param $player
     * @param $password
     * @param $email
     * @param $pin
     * @param string $auth
     * @param callable|null $callback
     * @param null $args
     * @return mixed|void
     */
    public function insertDataWithoutPlayerObject($player, $password, $email, $pin, $auth = "PiggyAuth", callable $callback = null, $args = null)
    {
        $task = new MySQLTask($this->plugin->getConfig()->get("mysql"), "INSERT INTO players (name, password, email, pin, uuid, attempts, xbox, language, auth) VALUES ('" . $this->db->escape_string(strtolower($player)) . "', '" . $this->db->escape_string($password) . "', '" . $this->db->escape_string($email) . "', '" . intval($pin) . "', 'uuid', '0', 'false', '" . $this->db->escape_string($this->plugin->getLanguageManager()->getDefaultLanguage()) . "', '" . $this->db->escape_string($auth) . "')");
        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
    }

    /**
     * @param $player
     * @param callable|null $callback
     * @param null $args
     * @return mixed|void
     */
    public function clearPassword($player, callable $callback = null, $args = null)
    {
        $task = new MySQLTask($this->plugin->getConfig()->get("mysql"), "DELETE FROM players WHERE name = '" . $this->db->escape_string(strtolower($player)) . "'", $callback, $args);
        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
    }

}
