<?php

namespace PiggyAuth\Converter;

use pocketmine\utils\Config;

/**
 * Class SimpleAuthConverter
 * @package PiggyAuth\Converter
 */
class SimpleAuthConverter implements Converter
{
    private $plugin;

    /**
     * SimpleAuthConverter constructor.
     * @param $plugin
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param $file
     * @param $table
     * @param null $otherinfo
     * @return bool
     */
    public function convertFromSQLite3($file, $table, $otherinfo = null)
    {
        if (file_exists($this->plugin->getDataFolder() . "convert/" . $file)) {
            if (strpos($file, ".db") !== false) {
                $db = new \SQLite3($this->plugin->getDataFolder() . "convert/" . $file, SQLITE3_OPEN_READWRITE);
                $result = $db->query("SELECT * from " . $table);
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    if ($this->plugin->database->getOfflinePlayer($row["name"])) {
                        $this->plugin->getLogger()->info(str_replace("{player}", $row["name"], $this->plugin->getLanguageManager()->getMessageFromLanguage($this->plugin->getLanguageManager()->getDefaultLanguage(), "player-account-already-exists")));
                        continue;
                    }
                    $this->plugin->database->insertDataWithoutPlayerObject($row["name"], $row["hash"], "none", mt_rand(1000, 9999), "SimpleAuth");
                }
            } else {
                $this->plugin->getLogger()->info(str_replace("{file}", $file, $this->plugin->getLanguageManager()->getMessageFromLanguage($this->plugin->getLanguageManager()->getDefaultLanguage(), "file-not-db")));
                return false;
            }
        } else {
            $this->plugin->getLogger()->info(str_replace("{file}", $file, $this->plugin->getLanguageManager()->getMessageFromLanguage($this->plugin->getLanguageManager()->getDefaultLanguage(), "invalid-file")));
            return false;
        }
        return true;
    }

    /**
     * @param $host
     * @param $user
     * @param $password
     * @param $name
     * @param $port
     * @param $table
     * @param null $otherinfo
     * @return bool
     */
    public function convertFromMySQL($host, $user, $password, $name, $port, $table, $otherinfo = null)
    {
        $credentials = $this->plugin->getConfig()->get("mysql");
        $database = null;
        if ($credentials["host"] == $host && $credentials["user"] == $user && $credentials["password"] == $password && $credentials["name"] == $name && $credentials["port"] == $port) {
            $database = $this->plugin->database->db;
        } else {
            $database = new \mysqli($credentials["host"], $credentials["user"], $credentials["password"], $credentials["name"], $credentials["port"]);
        }
        $result = $database->query("SELECT * FROM " . $table);
        if ($result instanceof \mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                if ($this->plugin->database->getOfflinePlayer($row["name"])) {
                    $this->plugin->getLogger()->info(str_replace("{player}", $row["name"], $this->plugin->getLanguageManager()->getMessageFromLanguage($this->plugin->getLanguageManager()->getDefaultLanguage(), "player-account-already-exists")));
                    continue;
                }
                $this->plugin->database->insertDataWithoutPlayerObject($row["name"], $row["hash"], "none", mt_rand(1000, 9999), "SimpleAuth");
            }
            $result->free();
            return true;
        }
        return false;
    }

    /**
     * @param $directoryname
     * @param null $otherinfo
     * @return bool
     */
    public function convertFromYML($directoryname, $otherinfo = null)
    {
        if (is_dir($this->plugin->getDataFolder() . "convert/" . $directoryname . "/")) {
            $directories = scandir($this->plugin->getDataFolder() . "convert/" . $directoryname . "/");
            foreach ($directories as $directory) {
                $files = scandir($this->plugin->getDataFolder() . "convert/" . $directoryname . "/" . $directory . "/");
                if ($directory !== "." && $directory !== "..") {
                    foreach ($files as $file) {
                        if ($file !== "." && $file !== "..") {
                            if (strpos($file, ".yml") !== false) {
                                $yml = new Config($this->plugin->getDataFolder() . "convert/" . $directoryname . "/" . $directory . "/" . $file);
                                $data = $yml->getAll();
                                $name = str_replace(".yml", "", $file);
                                if ($this->plugin->database->getOfflinePlayer($name)) {
                                    $this->plugin->getLogger()->info(str_replace("{player}", $name, $this->plugin->getLanguageManager()->getMessageFromLanguage($this->plugin->getLanguageManager()->getDefaultLanguage(), "player-account-already-exists")));
                                    continue;
                                }
                                $this->plugin->database->insertDataWithoutPlayerObject($name, $data["hash"], "none", mt_rand(1000, 9999), "SimpleAuth");
                            } else {
                                $this->plugin->getLogger()->info(str_replace("{file}", $file, $this->plugin->getLanguageManager()->getMessageFromLanguage($this->plugin->getLanguageManager()->getDefaultLanguage(), "file-not-yml")));
                            }
                        }
                    }
                }
            }
        } else {
            $this->plugin->getLogger()->info(str_replace("{file}", $directoryname, $this->plugin->getLanguageManager()->getMessageFromLanguage($this->plugin->getLanguageManager()->getDefaultLanguage(), "invalid-directory")));
            return false;
        }
        return true;
    }
}