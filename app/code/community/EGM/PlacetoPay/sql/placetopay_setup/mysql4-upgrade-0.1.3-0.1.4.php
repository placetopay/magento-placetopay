<?php
/**
 * PlacetoPay Connector for Magento
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @copyright  Copyright (c) 2009-2010 EGM Ingenieria sin fronteras S.A.S.
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * @var EGM_PlacetoPay_Model_Mysql4_Setup
 */
$this->startSetup();
$this->run("
INSERT INTO {$this->getTable('core_config_data')} (scope, scope_id, path, value)
SELECT scope, scope_id, REPLACE(path, 'payment/placetopay_standard', 'placetopay/gpg'), value
FROM {$this->getTable('core_config_data')}
WHERE scope = 'default' AND path IN (
	'payment/placetopay_standard/gpgpath', 'payment/placetopay_standard/gpghomedir',
	'payment/placetopay_standard/keyid', 'payment/placetopay_standard/passphrase',
	'payment/placetopay_standard/recipientkeyid'
);
DELETE FROM {$this->getTable('core_config_data')} WHERE path IN (
	'payment/placetopay_standard/gpgpath', 'payment/placetopay_standard/gpghomedir',
	'payment/placetopay_standard/keyid', 'payment/placetopay_standard/passphrase',
	'payment/placetopay_standard/recipientkeyid'
);
");
$this->endSetup();
