<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Mage
 * @package    PagSeguro
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


$installer = $this;
/* @var $installer PagSeguro_Model_Mysql4_Setup */

$installer->startSetup();
/*
$installer->run("

-- DROP TABLE IF EXISTS `{$this->getTable('pagseguro_api_debug')}`;
CREATE TABLE `{$this->getTable('pagseguro_api_debug')}` (
  `debug_id` int(10) unsigned NOT NULL auto_increment,
  `debug_at` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `request_body` text,
  `response_body` text,
  PRIMARY KEY  (`debug_id`),
  KEY `debug_at` (`debug_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");
*/

$installer->addAttribute('quote_payment', 'pagseguro_payer_id', array());
$installer->addAttribute('quote_payment', 'pagseguro_payer_status', array());
$installer->addAttribute('quote_payment', 'pagseguro_correlation_id', array());

$installer->endSetup();
