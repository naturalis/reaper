<?php

namespace Reaper;

use Curl\Curl;

class Iucn extends AbstractClass
{
    private $curl;
    private $url;
    private $species;
    private $currentSpecies;
    private $rateLimit;

    const TABLE = 'iucn';

    public function __construct ()
    {
        parent::__construct();

        $this->url = $this->config->getEnv('REAPER_URL_IUCN') . '?token=' .
            $this->config->getEnv('REAPER_KEY_IUCN');
        $this->rateLimit = !is_null($this->config->getEnv('REAPER_RATE_LIMIT_IUCN')) ?
            (float)$this->config->getEnv('REAPER_RATE_LIMIT_IUCN') : 0;

        $this->curl = new Curl();
        $this->curl->setOpt(CURLOPT_TIMEOUT, 5);
    }

    public function __destruct ()
    {
        $this->setReadyMessage();
        $this->curl->close();
    }

    public function import ()
    {
        $this->emptyTable(self::TABLE);
        $this->species = $this->getSpeciesNames();
        $this->total = count($this->species);
        $this->logger->log("Retrieving data for " . $this->total . " species names");
        foreach ($this->species as $this->currentSpecies) {
            $this->curl->get(sprintf($this->url, rawurlencode($this->currentSpecies)));
            if ($this->curl->error) {
                $this->logger->log("Error retrieving data for " . $this->currentSpecies .
                    ': ' . $this->curl->error_message, 1);
            } else {
                $this->insertData(json_decode($this->curl->response));
            }
            sleep($this->rateLimit);
        }
    }

    private function insertData ($row)
    {
        if (empty($row->result) || !$this->currentSpecies) {
            $this->logger->log("No data found for '" . $this->currentSpecies . "'");
            return false;
        }
        $data = (array)$row->result[0];
        $intFields = [
            'marine_system',
            'freshwater_system',
            'terrestrial_system',
            'errata_flag',
            'amended_flag',
        ];
        foreach ($intFields as $field) {
            if (empty($data[$field])) {
                $data[$field] = 0;
            }
        }
        if ($this->pdo->insertRow(self::TABLE, $data)) {
            $this->logger->log("Inserted data for '" . $this->currentSpecies . "'");
            $this->imported++;
        } else {
            $this->logger->log("Could not insert data for '" . $this->currentSpecies . "'", 1);
        }
        $this->resetCurrentSpecies();
    }

    private function resetCurrentSpecies ()
    {
        $this->currentSpecies = false;
    }

}