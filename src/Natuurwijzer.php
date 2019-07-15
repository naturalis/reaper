<?php

namespace Reaper;

use Curl\Curl;

class Natuurwijzer extends AbstractClass
{
    private $curl;
    private $url;
    private $rooms = [];
    private $currentRoom;
    private $objects = [];
    private $offset = 0;

    const TABLE = 'natuurwijzer';

    public function __construct ()
    {
        parent::__construct();

        $this->url = $this->setPath($this->config->getEnv('REAPER_URL_NATUURWIJZER'));

        $this->curl = new Curl();

        if (
            !empty($this->config->getEnv('REAPER_USER_NATUURWIJZER')) && 
            !empty($this->config->getEnv('REAPER_PASSWORD_NATUURWIJZER'))
        )
        {
            $this->curl->setBasicAuthentication(
                $this->config->getEnv('REAPER_USER_NATUURWIJZER'),
                $this->config->getEnv('REAPER_PASSWORD_NATUURWIJZER')
            );
        }

        $this->curl->setHeader('nw-access-token',
            $this->config->getEnv('REAPER_TOKEN_NATUURWIJZER'));
        $this->curl->setHeader('Cache-Control', 'no-cache');
    }

    public function __destruct ()
    {
        $this->setReadyMessage();
        $this->curl->close();
    }

    public function import ()
    {
        $this->emptyTable(self::TABLE);

        $continue=true;
        while ($continue)
        {
            $this->setObjects();
            $this->insertData();
            $this->offset = $this->offset+50;
            $continue = !empty($this->objects);
        }
    }


    private function setObjects ()
    {
        $this->resetObjects();

        $url = str_replace('%OFFSET%', $this->offset, $this->url);

        $this->curl->get($url);

        $data = json_decode($this->curl->response);

        foreach ($data->data as $i => $row)
        {
            if (empty($row->attributes->taxon) && empty($row->attributes->exhibition_rooms))
            {
                continue;
            }

            $this->total++;
            $this->objects[$i]['title'] = $row->attributes->title;
            $this->objects[$i]['room'] = $this->currentRoom;
            $this->objects[$i]['url'] = $row->attributes->url;
            $this->objects[$i]['taxon'] = json_encode($row->attributes->taxon);
            $this->objects[$i]['exhibition_rooms'] = json_encode($row->attributes->exhibition_rooms);
            $this->objects[$i]['image_urls'] = json_encode($row->attributes->image->image->urls);
            $this->objects[$i]['author'] = $row->attributes->author;
            $this->objects[$i]['intro_text'] = $row->attributes->intro_text;
            $this->objects[$i]['langcode'] = $row->attributes->langcode;
        }
        return $this->objects;
    }

    private function insertData ()
    {
        if (empty($this->objects)) {
            return false;
        }
        foreach ($this->objects as $object) {
            if ($this->pdo->insertRow(self::TABLE, $object)) {
                $this->logger->log("Inserted data for '" . $object['title'] . "'");
                $this->imported++;
            } else {
                $this->logger->log("Could not insert data for '" . $object['title'] . "'", 1);
            }
        }
    }

    private function resetObjects ()
    {
        $this->objects = [];
    }

}