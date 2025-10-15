<?php
// setup.php
require 'actionlogger.php';

$logger = new ActionLogger();
$logger->initializeDatabase();

echo "Database and table have been initialized";
