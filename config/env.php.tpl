<?php

/**
 * Use this file to load the config settings when used outside a docker environment.
 * The values should exactly match the ones in the .env file!
 */

    $envs = [
        // Database
        'MYSQL_HOST' => 'localhost',
        'MYSQL_USER' => 'root',
        'MYSQL_PASSWORD' => '',
        'MYSQL_DATABASE' => 'reaper',
        // File storage
        'REAPER_FILE_BASE_PATH' => '/path/to/reaper/files/',
        // Tentoonstelling
        'REAPER_FILE_TENTOONSTELLING_CSV' => 'tentoonstelling.csv',
        // Topstukken
        'REAPER_URL_TOPSTUKKEN' => 'https://topstukken.naturalis.nl/',
        // TTIK
        'REAPER_URL_TTIK' => 'https://ttik.linnaeus.naturalis.nl/linnaeus_ng/app/views/webservices/',
        // IUCN
        'REAPER_URL_IUCN' => 'https://apiv3.iucnredlist.org/api/v3/species/%s',
        'REAPER_KEY_IUCN' => '',
        // Natuurwijzer
        'REAPER_URL_NATUURWIJZER' => 'https://natuurwijzer-acc.naturalis.nl/api/v2/',
        'REAPER_USER_NATUURWIJZER' => '',
        'REAPER_PASSWORD_NATUURWIJZER' => '',
        'REAPER_TOKEN_NATUURWIJZER' => '',

    ];