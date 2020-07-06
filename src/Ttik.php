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
    const TABLE_TRANSLATIONS = 'ttik_translations';

    public function __construct ()
    {
        parent::__construct();

        $this->url = $this->setPath($this->config->getEnv('REAPER_URL_TTIK')) . 'names.php';

        $this->curl = new Curl();
        $this->curl->setOpt(CURLOPT_TIMEOUT, 5);
    }

    public function __destruct ()
    {
        $this->logger->log('Ready! Inserted ' . $this->imported . ' taxa');
        $this->curl->close();
    }

    public function import ()
    {
        $this->emptyTable(self::TABLE);
        $this->emptyTable(self::TABLE_TRANSLATIONS);
        $this->setTotal();
        $this->logger->log("Retrieving data for " . $this->total . " scientific and common names");

        // Fetch in batches
        for ($this->offset = 0; $this->offset < $this->total; $this->offset += $this->rows) {
            $this->setNames();
            $this->setTaxa();
            $this->logger->log("Fetched $this->rows new names (" . (count($this->taxa) - 1) . " taxa)");
            $this->insertData();
        }
        $this->insertData(true);
    }

    /* Keep the taxa array as lean as possible by removing taxa that have been inserted. However!
    The data for a taxon may be incomplete, as we're looping over names, not taxa. Therefore,
    always keep the last taxon in the array. Only when the parameter all has been set,
    all taxa should be inserted.
    */
    private function insertData ($all = false)
    {
        foreach ($this->taxa as $key => $taxon) {
            $scientificName = trim($taxon['uninomial'] . ' ' . $taxon['specific_epithet'] . ' ' .
                $taxon['infra_specific_epithet']);
            end($this->taxa);
            if ($key === key($this->taxa) && !$all) {
                break;
            }

            $these_descriptions = $taxon['description'];
            unset($taxon['description']);

            if ($this->pdo->insertRow(self::TABLE, $taxon))
            {

                $this->imported++;
                $this->logger->log("Inserted data for '$scientificName'");

                foreach ($these_descriptions as $lang => $this_description)
                {
                    if (empty($this_description))
                    {
                        continue;
                    }

                    if ($this->pdo->insertRow(self::TABLE_TRANSLATIONS, [
                        "taxon_id" => $taxon['taxon_id'],
                        "language_code" => $lang,
                        "description" => json_encode($this_description),
                        "verified" => "1"
                    ]))
                    {
                        $this->logger->log("Inserted '$lang' description for '$scientificName'");
                    }
                    else
                    {
                        $this->logger->log("Could not insert '$lang' description for '$scientificName'");
                    }
                }
                unset($this->taxa[$key]);
            } else {
                $this->logger->log("Could not insert data for '$scientificName'", 1);
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
            'all' => 1,
        ]);
        if ($this->curl->error) {
            $this->logger->log("Cannot retrieve species names, aborting import", 1);
            exit();
        }
        $data = json_decode($this->curl->response);
        $this->names = $data->names;
    }

    private function setTaxa ()
    {
        foreach ($this->names as $name) {
            $id = $name->taxon_id;
            $common = [];
            $synonyms = [];
            // Add just once for the first iteration of a taxon
            if ($id != $this->currentTaxonId) {
                $this->taxa[$id]['description'] = $this->getTaxonDescription($id);
                $this->taxa[$id]['classification'] = $this->getTaxonClassification($id);
                $this->taxa[$id]['synonyms'] = null;
            }
            // Valid name; add main data
            if ($name->language == 'Scientific' && $name->nametype == 'isValidNameOf') {
                $this->taxa[$id] = array_merge($this->taxa[$id], $this->stripNameData((array)$name));
                $this->totalTaxa++;
            // Synonyms
            } else if ($name->language == 'Scientific') {
                $synonyms[] = [
                    'uninomial' => $name->uninomial,
                    'specific_epithet' => $name->specific_epithet,
                    'infra_specific_epithet' => $name->infra_specific_epithet,
                    'authorship' => $name->authorship,
                    'nametype' => $name->nametype,
                ];
                if (!empty($this->taxa[$id]['synonyms'])) {
                    $synonyms[] = array_merge(json_decode($this->taxa[$id]['synonyms']), $synonyms);
                }
                $this->taxa[$id]['synonyms'] = json_encode($synonyms);
            // Common names in Dutch or English
            } else if (in_array($name->language, ['English', 'Dutch'])) {
                $common[] = ['name' => $name->name, 'nametype' => $name->nametype, 'remark' => $name->remark ];
                if (!empty($this->taxa[$id][strtolower($name->language)])) {
                    $common = array_merge(json_decode($this->taxa[$id][strtolower($name->language)]), $common);
                }
                $this->taxa[$id][strtolower($name->language)] = json_encode($common);
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

    private function setTotal()
    {
        if ($this->total == 0) {
            $this->curl->get($this->url, [
                'pid' => $this->pid,
                'from' => $this->from,
                'count' => 1,
                'all' => 1,
            ]);
            $data = json_decode($this->curl->response);
            $this->total = $data->count;
        }
        return $this->total;
    }

    private function getTaxonDescription ($taxonId)
    {
        $description=[];

        foreach (['nl', 'en'] as $lang)
        {
            $description[$lang]=[];
            foreach ([1, 4, 5] as $cat)
            {
                $url = $this->setPath($this->config->getEnv('REAPER_URL_TTIK')) . 'taxon_page.php';
                $this->curl->get($url, [
                    'pid' => $this->pid,
                    'taxon' => $taxonId,
                    'cat' => $cat,
                    'lang' => ($lang=='en' ? '26' : '24')
                ]);
                $data = json_decode($this->curl->response);
                if (!empty($data->page->body))
                {
                    if ($data->page->publish=='1')
                    {
                        $description[$lang][] = [
                            "title" => $data->page->title, 
                            "body" => $data->page->body
                        ];
                    }
                    else
                    {
                        
                    }

                }
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