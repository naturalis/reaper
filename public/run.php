<?php

use Reaper\Tentoonstelling as Tentoonstelling;
use Reaper\Topstukken as Topstukken;
use Reaper\Ttik as Ttik;
use Reaper\Iucn as Iucn;
use Reaper\Natuurwijzer as Natuurwijzer;
use Reaper\Crs as Crs;
use Reaper\Logger as Logger;

require_once __DIR__ . '/../vendor/autoload.php';

$opt = getopt("",["source:"]);

if (!isset($opt["source"]))
{
    $log = new Logger();
    $log->log("no source specified", 1);  
    exit(0);
}

switch ($opt["source"])
{
    case "crs":
        $import = new Crs();
        break;

    case "tentoonstelling":
        $import = new Tentoonstelling();
        break;

    case "topstukken":
        $import = new Topstukken();
        break;

    case "ttik":
        $import = new Ttik();
        break;

    case "iucn":
        $import = new Iucn();
        break;

    case "natuurwijzer":
        $import = new Natuurwijzer();
        break;

    default:
        $log = new Logger();
        $log->log(sprintf("unknown source: %s",$opt["source"]), 1);
}

if (isset($import))
{
    $import->import();    
}

