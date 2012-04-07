<?php
/**
 * Magento PagSeguro Payment Modulo
 *
 * @category   Mage
 * @package    Mage_Pagseguro
 * @copyright  Author Ítalo Amorim Gomes (italoamorim@gmail.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 *
 * PagSeguro Payment Action Dropdown source
 *
 */
class Mage_Pagseguro_Model_Source_StandardAction
{
    public function toOptionArray()
    {
        return array(
            array('value' => Mage_Pagseguro_Model_Standard::PAYMENT_TYPE_AUTH, 'label' => Mage::helper('Pagseguro')->__('Authorization')),
            array('value' => Mage_Pagseguro_Model_Standard::PAYMENT_TYPE_SALE, 'label' => Mage::helper('Pagseguro')->__('Sale')),
        );
    }
}