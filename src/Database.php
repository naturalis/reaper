<?php

namespace Reaper;

use PDO;

class Database
{
    public $pdo;

    private $host;
    private $user;
    private $password;
    private $database;
    private $table;

    public function __construct ()
    {
        $this->config = new Config();
        $this->config->setEnvs();

        $this->host = $this->config->getEnv('REAPER_DB_HOST');
        $this->user = $this->config->getEnv('REAPER_DB_USERNAME');
        $this->password = $this->config->getEnv('REAPER_DB_PASSWORD');
        $this->database = $this->config->getEnv('REAPER_DB_NAME');

        if (!$this->host || !$this->user || !$this->password || !$this->database) {
            throw new Exception('Incomplete database credentials, check .env settings!');
        }

        $this->connect();
    }

    /**
     * Dynamically map incoming calls to PDO method
     */
    public function __call ($method, $arguments)
    {
         if ($this->pdo instanceof PDO && method_exists('PDO', $method)) {
             return $this->pdo->{$method}($arguments[0]);
         }
    }

    public function isInitialized ()
    {
        return $this->pdo instanceof PDO;
    }

    public function insertRow ($table, $data)
    {
        $data = array_map('trim', $data);
        $prepare = '
            INSERT INTO `' . $table . '`
            ( `' . implode('`, `', array_keys($data)) . '`)
            VALUES
            (' . substr(str_repeat('?, ', count($data)), 0, -2) . ')';
        $stmt = $this->pdo->prepare($prepare);
        return $stmt->execute(array_values($data));
    }

    public function getTaxonNames ($bySource = true)
    {
        $query = "
            select scientificName as scientific_name, 'topstukken' as source
                from topstukken 
                where scientificName != ''
            union select SCName as scientific_name, 'tentoonstelling' as source 
                from tentoonstelling
                where SCName != ''
            union select trim(concat(uninomial, ' ', specific_epithet, ' ', infra_specific_epithet)) 
                as scientific_name, 'ttik' as source 
                from ttik
                where specific_epithet != ''
            order by scientific_name";
        $stmt = $this->pdo->query($query);
        while ($row = $stmt->fetch()) {
            $data[$row['source']][] = $row['scientific_name'];
        }

        $stmt = $this->pdo->query("select taxon from natuurwijzer");
        $natuurwijzer = [];
        while ($row = $stmt->fetch()) {
            if (!empty($row['taxon'])) {
                 $natuurwijzer = array_merge($natuurwijzer, (array)json_decode($row['taxon'], true));
            }
        }
        natcasesort($natuurwijzer);
        $data['natuurwijzer'] = array_unique($natuurwijzer);
        return isset($data) ? $data : [];
}

    private function connect ()
    {
        if (!$this->pdo) {
            $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->database . ';';
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'"
            ];
            $this->pdo = new PDO($dsn, $this->user, $this->password, $options);
        }
    }



}