<?php

namespace Reaper;

use Reaper\Config as Config;

class AbstractClass
{
    protected $config;
    protected $pdo;

    protected $imported = 0;
    protected $total = 0;

    public function __construct ()
    {
        $this->config = new Config();
        $this->config->setEnvs();
        $this->pdo = new Database();
    }

    public function setPath ($path)
    {
        return rtrim($path, '/') . '/';
    }

    public function getResults ()
    {
        return [
            'total' => $this->total,
            'imported' => $this->imported,
        ];
    }

    protected function emptyTable ($table)
    {
        $this->pdo->query('TRUNCATE TABLE `' . $table . '`');
        $this->log("Truncated table '" . $table . "'");
    }

    // Workaround for bug in SimpleHtmlDom
    protected function fixSimpleHtmlDomReplacements ($str)
    {
        $domReplaceHelper = [
            'orig' => ['&', '|', '+', '%', '@'],
            'tmp'  => [
                '____SIMPLE_HTML_DOM__VOKU__AMP____',
                '____SIMPLE_HTML_DOM__VOKU__PIPE____',
                '____SIMPLE_HTML_DOM__VOKU__PLUS____',
                '____SIMPLE_HTML_DOM__VOKU__PERCENT____',
                '____SIMPLE_HTML_DOM__VOKU__AT____',
            ],
        ];
        return str_replace($domReplaceHelper['tmp'], $domReplaceHelper['orig'], $str);
    }

    public function getTaxonNames ($bySource = true)
    {
        $data = $this->pdo->getTaxonNames();
        if (isset($data) && !$bySource) {
            $flat = [];
            foreach (array_keys($data) as $source) {
                $flat = $flat + $data[$source];
            }
            sort($flat);
        }
        return isset($flat) ? $flat : (isset($data) ? $data : []);
    }

    public function getSpeciesNames ()
    {
        foreach ($this->getTaxonNames(false) as $taxon) {
            $tmp = array_filter(explode(' ', $taxon));
            if (count($tmp) >= 2) {
                $species[] = ucfirst($tmp[0]) . ' ' . $tmp[1];
            }
        }
        natcasesort($species);
        return array_filter(array_unique($species));
    }

    public function log ($message, $level = 3)
    {
        $levels = [
            1 => 'Error',
            2 => 'Warning',
            3 => 'Info',
            4 => 'Debug',
        ];
        echo date('d-M-Y H:i:s') . ' - ' . get_class($this) . ' - ' .
            $levels[$level] . ' - ' . $message . "\n";
    }

    protected function setReadyMessage ()
    {
        $this->log('Ready! Inserted ' . $this->imported . ' out of ' . $this->total . ' names');
    }

}