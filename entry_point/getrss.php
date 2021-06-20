#!/usr/bin/php

<?php
include_once __DIR__ . '/../vendor/autoload.php';

define("DEVELOPMENT_MODE", true);

$start = new initialization\start_application();
$arguments = $argv;
$start->Begin($arguments);

