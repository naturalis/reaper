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
        foreach ($this->getRooms() as $this->currentRoom) {
            $this->setObjectsInRoom();
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
            if (!empty($this->rooms)) {
                $this->log('Retrieved ' . count($this->rooms) . ' rooms');
            } else {
                $this->log('Could not retrieve rooms; aborting', 1);
            }
        }
        return $this->rooms;
    }

    private function setObjectsInRoom ()
    {
        if (empty($this->currentRoom)) {
            return false;
        }
        $this->resetObjects();

        $url = $this->url . 'learningobjects';
        $this->curl->get($url, [
            'filter[exhibition_rooms][condition][path]' => 'field_exhibition_rooms.name',
            'filter[exhibition_rooms][condition][value]' => $this->currentRoom,
        ]);

        $data = json_decode($this->curl->response);
        foreach ($data->data as $i => $row) {
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
            $this->log("No objects found for room '" . $this->currentRoom . "'", 2);
            return false;
        }
        foreach ($this->objects as $object) {
            if ($this->pdo->insertRow(self::TABLE, $object)) {
                $this->log("Inserted data for '" . $object['title'] . "'");
                $this->imported++;
            } else {
                $this->log("Could not insert data for '" . $object['title'] . "'", 1);
            }
        }
    }

    private function resetObjects ()
    {
        $this->objects = [];
    }

}