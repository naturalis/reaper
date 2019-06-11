<?php

namespace Reaper;

class Crs extends AbstractClass
{
    private $columns = [
        'REGISTRATIONNUMBER',
        'FULLSCIENTIFICNAME',
        'URL',
    ];

    private $basePath;
    private $csvFile;
    private $csvPath;
    private $csvMapping;
    private $startRow;

    const TABLE = 'crs';

    public function __construct ()
    {
        parent::__construct();
        $this->basePath = $this->config->getEnv('REAPER_FILE_BASE_PATH');
        $this->csvFile = $this->config->getEnv('REAPER_FILE_CRS_CSV');

        if (!$this->basePath || !$this->csvFile) {
            $this->log('No path settings for CRS csv file!', 1);
            exit();
        }

        $this->csvPath = $this->setPath($this->basePath) . $this->csvFile;
    }

    public function __destruct ()
    {
        $this->log('Ready! Inserted ' . $this->imported . ' out of ' .
            $this->total . ' image paths');
    }

    public function import ()
    {
        ini_set("auto_detect_line_endings", true);
        if (!($fh = fopen($this->csvPath, "r"))) {
            $this->log('Cannot read ' . $this->csvPath, 1);
            exit();
        }
        $i = 0;
        $this->emptyTable(self::TABLE);
        while ($row = fgetcsv($fh, 1000, "\t")) {
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
        if (!empty($data['REGISTRATIONNUMBER'])) {
            $this->total++;
            if ($this->pdo->insertRow(self::TABLE, $data)) {
                $this->log("Inserted data for '" . $data['REGISTRATIONNUMBER'] . "'");
                $this->imported++;
            } else {
                $this->log("Could not insert data for '" . $data['REGISTRATIONNUMBER'] . "'", 1);
            }
        }
    }
}