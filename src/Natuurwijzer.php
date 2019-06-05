<?php

namespace Reaper;

use Curl\Curl;

class Natuurwijzer extends AbstractClass
{
    private $curl;
    private $url;
    private $rooms = [];
    private $objects = [];

    const TABLE = 'natuurwijzer';

    public function __construct ()
    {
        parent::__construct();

        $this->url = $this->setPath($this->config->getEnv('REAPER_URL_NATUURWIJZER'));

        $this->curl = new Curl();
        $this->curl->setBasicAuthentication(
            $this->config->getEnv('REAPER_USER_NATUURWIJZER'),
            $this->config->getEnv('REAPER_PASSWORD_NATUURWIJZER')
        );
        $this->curl->setHeader('nw-access-token', $this->config->getEnv('REAPER_TOKEN_NATUURWIJZER'));
        $this->curl->setHeader('Cache-Control', 'no-cache');
    }

    public function __destruct ()
    {
        $this->curl->close();
    }

    public function import ()
    {
        $this->emptyTable(self::TABLE);
        foreach ($this->getRooms() as $room) {
            $this->getObjectsInRoom($room);
            $this->insertData();
        }
    }

    private function getRooms ()
    {
        if (empty($this->rooms)) {
            $url = $this->url . 'taxonomy_term/exhibition_rooms';
            $this->curl->get($url);
            $data = json_decode($this->curl->response);
            foreach ($data->data as $room) {
                $this->rooms[] = $room->attributes->name;
            }
        }
        return $this->rooms;
    }

    private function getObjectsInRoom ($room)
    {
        $this->resetObjects();
        $url = $this->url . 'learningobjects';
        $this->curl->get($url, [
            'filter[exhibition_rooms][condition][path]' => 'field_exhibition_rooms.name',
            'filter[exhibition_rooms][condition][value]' => $room,
        ]);

        $data = json_decode($this->curl->response);
        foreach ($data->data as $i => $row) {
            $this->objects[$i]['title'] = $row->attributes->title;
            $this->objects[$i]['room'] = $room;
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

    private function resetObjects ()
    {
        $this->objects = [];
    }

    private function insertData ()
    {
        if (empty($this->objects)) {
            return false;
        }
        foreach ($this->objects as $object) {
            if ($this->pdo->insertRow(self::TABLE, (array)$object)) {
                $this->imported++;
            }
        }
    }


}