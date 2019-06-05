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

    protected function getSpeciesNames ($bySource = true)
    {
        $data = $this->pdo->getSpeciesNames();
        if (isset($data) && !$bySource) {
            $flat = [];
            foreach (array_keys($data) as $source) {
                $flat = $flat + $data[$source];
            }
            sort($flat);
        }
        return isset($flat) ? $flat : (isset($data) ? $data : []);
    }

}