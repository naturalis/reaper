<?php

namespace Reaper;

use Curl\Curl;

class Iucn extends AbstractClass
{
    private $curl;
    private $url;
    private $species;

    const TABLE = 'iucn';

    public function __construct ()
    {
        parent::__construct();

        $this->url = $this->config->getEnv('REAPER_URL_IUCN') . '?token=' .
            $this->config->getEnv('REAPER_KEY_IUCN');
        $this->curl = new Curl();
    }

    public function __destruct ()
    {
        $this->curl->close();
    }

    public function import ()
    {
        $this->emptyTable(self::TABLE);
        foreach ($this->getSpecies() as $i => $name) {
            $this->curl->get(sprintf($this->url, $name));
            if ($this->curl->error) {
                throw new \Exception('Error fetching ' . sprintf($this->url, $name) . ': ' .
                    $this->curl->error_code);
            }
            $data = json_decode($this->curl->response);
            $this->insertData($data);
        }
    }

    private function getSpecies ()
    {
        if (empty($this->species)) {
            $this->species = $this->getTaxonNames(false);
        }
        return $this->species;
    }

    private function insertData ($row)
    {
        if (empty($row->result)) {
            return false;
        }
        $data = (array)$row->result[0];
        $intFields = [
            'marine_system',
            'freshwater_system',
            'terrestrial_system',
            'errata_flag',
            'amended_flag'
        ];
        foreach ($intFields as $field) {
            if (empty($data[$field])) {
                $data[$field] = 0;
            }
        }
        //print_r($data); die();
        if ($this->pdo->insertRow(self::TABLE, $data)) {
            $this->imported++;
        }
    }

}