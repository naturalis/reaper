<?php

namespace Reaper;

class Tentoonstelling extends AbstractClass
{
    private $columns = [
        'Registratienummer',
        'Zaal',
        'Zaaldeel',
        'SCname',
    ];

    private $basePath;
    private $csvFile;
    private $csvPath;
    private $csvMapping;
    private $startRow;

    const TABLE = 'tentoonstelling';

    public function __construct ()
    {
        parent::__construct();
        $this->basePath = $this->config->getEnv('REAPER_FILE_BASE_PATH');
        $this->csvFile = $this->config->getEnv('REAPER_FILE_TENTOONSTELLING_CSV');

        if (!$this->basePath || !$this->csvFile) {
            throw new \Exception('No path settings for Tentoonstelling csv file!');
        }

        $this->csvPath = $this->setPath($this->basePath) . $this->csvFile;
    }

    public function import ()
    {
        ini_set("auto_detect_line_endings", true);
        if (!($fh = fopen($this->csvPath, "r"))) {
            throw new \Exception('Cannot read ' . $this->csvPath);
        }
        $i = 0;
        $this->emptyTable(self::TABLE);
        while ($row = fgetcsv($fh)) {
            $i++;
            // Make sure we get the mapping right; it's not necessarily in the first row
            if ($row[0] == $this->columns[0]) {
                $this->csvMapping = $row;
                $this->startRow = $i + 1;
            }
            if ($this->startRow && $i >= $this->startRow) {
                $this->insertData($row);
            }
        }
        $this->total = $i;
        fclose($fh);
    }

    private function extractData ($row)
    {
        $data = [];
        foreach ($row as $i => $value) {
            if (in_array($this->csvMapping[$i], $this->columns)) {
                $data[$this->csvMapping[$i]] = $value;
            }
        }
        return $data;
    }

    private function insertData ($row)
    {
        $data = $this->extractData($row);
        if ($this->pdo->insertRow(self::TABLE, $data)) {
            $this->imported++;
        }
    }
}