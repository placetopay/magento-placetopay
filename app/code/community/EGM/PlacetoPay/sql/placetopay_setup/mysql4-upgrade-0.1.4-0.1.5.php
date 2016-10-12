<?php
/**
 * PlacetoPay Connector for Magento
 *
 * @category   EGM
 * @package    EGM_PlacetoPay
 * @copyright  Copyright (c) 2009-2011 EGM Ingenieria sin fronteras S.A.S.
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * @var EGM_PlacetoPay_Model_Mysql4_Setup
 */
$this->startSetup();
$this->run("
INSERT INTO {$this->getTable('core_config_data')} (scope, scope_id, path, value)
SELECT scope, scope_id, REPLACE(path, 'payment/placetopay_standard', 'placetopay'), value
FROM {$this->getTable('core_config_data')}
WHERE path IN (
	'payment/placetopay_standard/merchantname', 'payment/placetopay_standard/merchantdocument'
);
DELETE FROM {$this->getTable('core_config_data')} WHERE path IN (
	'payment/placetopay_standard/merchantname', 'payment/placetopay_standard/merchantdocument',
	'payment/placetopay_promotion/merchantname', 'payment/placetopay_promotion/merchantdocument',
	'payment/placetopay_standard/gpgpath', 'payment/placetopay_standard/gpghomedir',
	'payment/placetopay_standard/keyid', 'payment/placetopay_standard/passphrase',
	'payment/placetopay_standard/recipientkeyid'
);
REPLACE INTO {$this->getTable('sales_order_status')} (status, label) VALUES ('APPROVED', 'APROBADA');
REPLACE INTO {$this->getTable('sales_order_status')} (status, label) VALUES ('ERROR', 'FALLIDA');
REPLACE INTO {$this->getTable('sales_order_status')} (status, label) VALUES ('DECLINED', 'DECLINADA');
REPLACE INTO {$this->getTable('sales_order_status')} (status, label) VALUES ('UNKNOWN', 'PENDIENTE');
REPLACE INTO {$this->getTable('sales_order_status_label')} (status, store_id, label) VALUES ('APPROVED', 1, 'APPROVED');
REPLACE INTO {$this->getTable('sales_order_status_label')} (status, store_id, label) VALUES ('ERROR', 1, 'FAILED');
REPLACE INTO {$this->getTable('sales_order_status_label')} (status, store_id, label) VALUES ('DECLINED', 1, 'DECLINED');
REPLACE INTO {$this->getTable('sales_order_status_label')} (status, store_id, label) VALUES ('UNKNOWN', 1, 'UNKNOWN');
");
$this->run("
		INSERT INTO {$this->getTable('core_config_data')} (scope, scope_id, path, value)
		SELECT scope, scope_id, REPLACE(path, 'payment/placetopay_standard', 'placetopay/gpg'), value
		FROM {$this->getTable('core_config_data')}
WHERE scope = 'default' AND path IN ('payment/placetopay_standard/hascifin');
DELETE FROM {$this->getTable('core_config_data')} WHERE path IN (
'payment/placetopay_standard/hascifin');

INSERT INTO {$this->getTable('core_config_data')} (scope, scope_id, path, value)
SELECT scope, scope_id, REPLACE(path, 'payment/placetopay_promotion', 'placetopay/gpg'), value
FROM {$this->getTable('core_config_data')}
		WHERE scope = 'default' AND path IN ('payment/placetopay_promotion/hascifin');
		DELETE FROM {$this->getTable('core_config_data')} WHERE path IN (
		'payment/placetopay_promotion/hascifin');

		REPLACE INTO {$this->getTable('sales_order_status')} (status, label) VALUES ('processing', 'APROBADA');
		REPLACE INTO {$this->getTable('sales_order_status_label')} (status, store_id, label) VALUES ('processing', 1, 'APPROVED');
		");
$this->endSetup();
