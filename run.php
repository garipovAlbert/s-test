<?php
require(__DIR__ . '/vendor/autoload.php');

require_once(__DIR__ . '/Log.php');

$log = new Log($argv[1]);

$log->printReport();
