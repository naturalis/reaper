<?php

use Reaper\Tentoonstelling as Tentoonstelling;
use Reaper\Topstukken as Topstukken;
use Reaper\Ttik as Ttik;
use Reaper\Iucn as Iucn;
use Reaper\Natuurwijzer as Natuurwijzer;
use Reaper\Crs as Crs;
use Reaper\Export as Export;

require_once __DIR__ . '/../vendor/autoload.php';

//$import = new Tentoonstelling();
//$import->import();

//$import = new Topstukken();
//$import->import();

//$import = new Ttik();
//$import->import();

//$import = new Iucn();
//$import->import();

//$import = new Natuurwijzer();
//$import->import();

$import = new Crs();
$import->import();

//$export = new Export();
//$export->export();