<?php

// change the path for the operation
chdir(dirname(__FILE__) . '/../../../../../');

require 'app/Mage.php';

if (!Mage::isInstalled()) {
    echo 'Application is not installed yet, please complete install wizard first.' . PHP_EOL;
    exit;
}

// Only for urls
// Don't remove this
$_SERVER['SCRIPT_NAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_NAME']);
$_SERVER['SCRIPT_FILENAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_FILENAME']);

Mage::app('admin')->setUseSessionInUrl(false);

try {
    EGM_PlacetoPay_Model_Observer::resolvePendingTransactions();
} catch (Exception $e) {
    Mage::printException($e);
}
