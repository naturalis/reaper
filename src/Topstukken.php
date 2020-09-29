<?php

namespace Reaper;

use voku\helper\HtmlDomParser;
use Curl\Curl;

class Topstukken extends AbstractClass
{
    private $url;
    private $objectUrl;
    private $curl;
    private $objects = [];
    private $table_columns = [
        'description',
        'title',
        'registrationNumber',
        'collection',
        'country',
        'scientificName',
        'year',
        'expedition',
        'collector',
        'url',
        'image',
    ];

    const TABLE = 'topstukken';

    public function __construct ()
    {
        parent::__construct();

        $this->url = $this->config->getEnv('REAPER_URL_TOPSTUKKEN');

        $this->curl = new Curl();
        $this->curl->get($this->url);
        if ($this->curl->error) {
            $this->logger->log('Cannot connect to ' .  $this->url . ': ' . $this->curl->error_message, 1);
            exit();
        }
        unset($this->curl);
    }

    public function __destruct ()
    {
        $this->logger->log('Ready! Inserted ' . $this->imported . ' out of ' .
            ($this->total - 1) . ' registration numbers');
    }

    public function import ()
    {
        $index = $this->extractJson($this->url);
        if (!empty($index->grid->items)) {
            foreach ($index->grid->items as $item) {
                $this->objects[$item->id] = $item->slug;
            }
            $this->total = count($index);
            $this->emptyTable(self::TABLE);
            foreach ($this->objects as $id => $slug) {
                $this->objectUrl = $this->setPath($this->url) . 'object/' . $slug;
                $this->insertData($this->extractJson());
            }
        } else {
            $this->logger->log('Cannot extract');
        }
    }

    /**
     * This assumes that a script with the variable INITIAL_DATA is present containing the
     * complete dataset.
     */
    private function extractJson ($url = false)
    {
        if (!$url) {
            $url = $this->objectUrl;
        }
        $html = HtmlDomParser::file_get_html($url);
        foreach ($html->find('script') as $script) {
            if (strpos($script->nodeValue, 'INITIAL_DATA')) {
                $lines = explode("\n", trim($script->nodeValue));
                foreach ($lines as $line) {
                    if (strpos($line, 'INITIAL_DATA')) {
                        $var = trim($this->fixSimpleHtmlDomReplacements($line));
                    }
                }
            }
        }
        if (empty($var)) {
            $this->logger->log('Cannot parse data from ' . $this->objectUrl, 1);
            return false;
        }
        $first = strpos($var, '{');
        $json = substr($var, $first, strrpos($var, '}') - $first + 1);
        return json_decode($json);
    }

    private function insertData ($object)
    {
        $this->total++;
        $title = $object->specimen->title;
        $description = [];
        $data = (array)$object->specimen->info;
        if (isset($object->specimen->blocks)) {
            foreach ($object->specimen->blocks as $block) {
                $description[] = [$block->title, $block->body];
            }
        }
        $data['title'] = $title;
        $data['description'] = json_encode($description);
        $data['url'] = $this->objectUrl;
        $data['image'] = rtrim($this->url, '/') . $object->specimen->image->srcSet->{'1920'};

        // however, i'd rather have a less literal insert statement
        $strict_data=[];
        foreach ($data as $key => $val)
        {
            if (in_array($key, $this->table_columns))
            {
                $strict_data[$key]=$val;
            }
        }

        $data = $strict_data;

        if ($this->pdo->insertRow(self::TABLE, $data)) {
            $this->imported++;
            $this->logger->log("Inserted data for '" . $data['registrationNumber'] . "'");
        } else {
            $this->logger->log("Could not insert data for '" . $data['registrationNumber'] . "'", 1);
        }
    }
}
