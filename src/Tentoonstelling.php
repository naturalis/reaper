<?php

namespace Reaper;

class Tentoonstelling extends AbstractClass
{
    private $columns = [
        'Registratienummer',
        'Zaal',
        'Zaaldeel',
	'SCname',
	'SCname controle'
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
            $this->logger->log('No path settings for Tentoonstelling csv file!', 1);
            exit();
        }

        $this->csvPath = $this->setPath($this->basePath) . $this->csvFile;

        if (!file_exists($this->csvPath))
        {
            $this->logger->log(sprintf("csv file %s not found",$this->csvPath),1);
            exit();
        }
    }

    public function __destruct ()
    {
        $this->logger->log('Ready! Inserted ' . $this->imported . ' out of ' .
            $this->total . ' registration numbers');
    }

    public function import ()
    {
        ini_set("auto_detect_line_endings", true);
        if (!($fh = fopen($this->csvPath, "r"))) {
            $this->logger->log('Cannot read ' . $this->csvPath, 1);
            exit();
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
        if (!empty($data['Registratienummer'])) {
            $this->total++;
            if ($this->pdo->insertRow(self::TABLE, $data)) {
                $this->logger->log("Inserted data for '" . $data['Registratienummer'] . "'");
                $this->imported++;
            } else {
                $this->logger->log("Could not insert data for '" . $data['Registratienummer'] . "'", 1);
            }
        }
    }
}
