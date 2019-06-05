<?php

namespace Reaper;

use voku\helper\HtmlDomParser;
use Curl\Curl;

class Topstukken extends AbstractClass
{
    private $url;
    private $curl;
    private $objects = [];

    const TABLE = 'topstukken';

    public function __construct ()
    {
        parent::__construct();

        $this->url = $this->config->getEnv('REAPER_URL_TOPSTUKKEN');

        $this->curl = new Curl();
        $this->curl->get($this->url);
        if ($this->curl->error) {
            throw new \Exception('Error fetching ' . $this->url . ': ' . $this->curl->error_code);
        }
        unset($this->curl);
    }

    public function import ()
    {
        $index = $this->extractJson($this->url);
        foreach ($index->grid->items as $item) {
            $this->objects[$item->id] = $item->slug;
        }
        $this->total = count($index);
        $this->emptyTable(self::TABLE);
        foreach ($this->objects as $id => $slug) {
            $url = $this->setPath($this->url) . 'object/' . $slug;
            $this->insertData($this->extractJson($url));
        }
    }

    /**
     * This assumes that a script with the variable INITIAL_DATA is present containing the
     * complete dataset.
     */
    private function extractJson ($url)
    {
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
            throw new \Exception('Cannot parse data from ' . $url);
        }
        $first = strpos($var, '{');
        $json = substr($var, $first, strrpos($var, '}') - $first + 1);
        $data = json_decode($json);
        return $data ?: null;
    }

    private function insertData ($object)
    {
        $description = '';
        $data = (array)$object->specimen->info;
        if (isset($object->specimen->blocks)) {
            foreach ($object->specimen->blocks as $block) {
                $description .= '<h4>' . $block->title . "</h4>\n";
                $description .= $block->body;
            }
        }
        $data['description'] = $description;
        if ($this->pdo->insertRow(self::TABLE, $data)) {
            $this->imported++;
        }
    }
}