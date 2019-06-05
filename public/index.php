<?php

use Reaper\Tentoonstelling as Tentoonstelling;
use Reaper\Topstukken as Topstukken;
use Reaper\Ttik as Ttik;
use Reaper\Iucn as Iucn;
use Reaper\Natuurwijzer as Natuurwijzer;

require_once __DIR__ . '/../vendor/autoload.php';

//$import = new Tentoonstelling();
//$import->import();

//$import = new Topstukken();
//$import->import();

//$import = new Ttik();
//$import->import();

$import = new Iucn();
print_r($import->getSpeciesNames());
//$import->import();

//$import = new Natuurwijzer();
//$import->import();