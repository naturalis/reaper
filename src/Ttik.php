<?php

namespace Reaper;

use Curl\Curl;

class Ttik extends AbstractClass
{
    private $curl;
    private $url;
    private $rows = 500;
    private $offset = 0;
    private $pid = 1;
    private $from = '19000101';
    private $names = [];
    private $taxa = [];
    private $totalTaxa = 0;
    private $currentTaxonId;

    const TABLE = 'ttik';

    public function __construct ()
    {
        parent::__construct();

        $this->url = $this->setPath($this->config->getEnv('REAPER_URL_TTIK')) . 'names.php';

        $this->curl = new Curl();
        $this->curl->setOpt(CURLOPT_TIMEOUT, 5);
    }

    public function __destruct ()
    {
        $this->log('Ready! Inserted ' . $this->imported . ' out of ' . $this->totalTaxa . ' taxa');
        $this->curl->close();
    }

    public function import ()
    {
        $this->emptyTable(self::TABLE);
        $this->setTotal();
        $this->log("Retrieving data for " . $this->total . " scientific and common names");
        // Run in batches
        for ($this->offset = 0; $this->offset < $this->total; $this->offset += $this->rows) {
            $this->setNames();
            $this->setTaxa();
            $this->insertData();
        }
        $this->insertData(true);
    }

    /* Keep the taxa array as lean as possible by removing taxa that have been inserted. However!
    The data for a taxon may be incomplete, as we're looping over names, not taxa. Therefore,
    always keep the skip the last taxon in the array. Only when the parameter all has been set,
    all taxa should be inserted.
    */
    private function insertData ($all = false)
    {
        foreach ($this->taxa as $key => $taxon) {
            $this->totalTaxa++;
            $scientificName = trim($taxon['uninomial'] . ' ' . $taxon['specific_epithet'] . ' ' .
                $taxon['infra_specific_epithet']);
            end($this->taxa);
            if ($key === key($this->taxa) && !$all) {
                break;
            }
            if ($this->pdo->insertRow(self::TABLE, $taxon)) {
                $this->imported++;
                $this->log("Inserted data for '$scientificName'");
                unset($this->taxa[$key]);
            } else {
                $this->log("Could not insert data for '$scientificName'", 1);
            }
        }
    }

    private function setNames ()
    {
        $this->curl->get($this->url, [
            'pid' => $this->pid,
            'from' => $this->from,
            'rows' => $this->rows,
            'offset' => $this->offset,
        ]);
        if ($this->curl->error) {
            $this->log("Cannot retrieve species names, aborting import", 1);
            exit();
        }
        $data = json_decode($this->curl->response);
        $this->names = $data->names;
    }

    private function setTaxa ()
    {
        foreach ($this->names as $name) {
            $id = $name->taxon_id;
            // Add just once for the first iteration of a taxon
            if ($name->taxon_id != $this->currentTaxonId) {
                $this->taxa[$id]['description'] = $this->getTaxonDescription($id);
                $this->taxa[$id]['classification'] = $this->getTaxonClassification($id);
            }
            if ($name->language == 'Scientific') {
                $this->taxa[$id] = array_merge($this->taxa[$id], $this->stripNameData((array)$name));
            } else if (in_array($name->language, ['English', 'Dutch'])) {
                $this->taxa[$id][strtolower($name->language)] = $name->name;
            }
            $this->currentTaxonId = $id;
        }
    }

    private function stripNameData ($name)
    {
        $fields = [
            'name_id',
            'name',
            'language',
            'language_iso3',
            'last_change',
            'nametype',
            'taxon_valid_name_id',
            'url',
        ];
        foreach ($fields as $field) {
            if (isset($name[$field])) {
                unset($name[$field]);
            }
        }
        return $name;
    }

    private function setTotal ()
    {
        if ($this->total == 0) {
            $this->curl->get($this->url, [
                'pid' => $this->pid,
                'from' => $this->from,
                'count' => 1,
            ]);
            $data = json_decode($this->curl->response);
            $this->total = $data->count;
        }
        return $this->total;
    }

    private function getTaxonDescription ($taxonId)
    {
        $description = '';
        foreach ([1, 4, 5] as $cat) {
            $url = $this->setPath($this->config->getEnv('REAPER_URL_TTIK')) . 'taxon_page.php';
            $this->curl->get($url, [
                'pid' => $this->pid,
                'taxon' => $taxonId,
                'cat' => $cat,
            ]);
            $data = json_decode($this->curl->response);
            if (!empty($data->page->body)) {
                $description .= '<h4>' . $data->page->title . "</h4>\n" . $data->page->body . "\n";
            }
        }
        return $description;
    }

    private function getTaxonClassification ($taxonId)
    {
        $url = $this->setPath($this->config->getEnv('REAPER_URL_TTIK')) . 'taxonomy.php';
        $this->curl->get($url, [
            'pid' => $this->pid,
            'taxon' => $taxonId,
        ]);
        $data = json_decode($this->curl->response);
        if (!empty($data->classification) && count($data->classification) > 0) {
            return json_encode($data->classification);
        }
        return null;
    }

}