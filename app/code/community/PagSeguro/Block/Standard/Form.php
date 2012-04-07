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


class PagSeguro_Block_Standard_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        //$this->setTemplate('PagSeguro/standard/form.phtml');
        parent::_construct();
    }
    
    protected function _toHtml()
    {
	$_code = $this->getMethodCode();
	$_message = $this->__('Você será redirecionado para o PagSeguro após a confirmação do pedido.');
	$htmlString = '<fieldset class="form-list">';
	$htmlString .= '<ul id="payment_form_' . $_code . '" style="display:none">';
	$htmlString .= '<li>';
       	$htmlString .= $_message;
	$htmlString .= '</li>';
	$htmlString .= '</ul>';
	$htmlString .= '</fieldset>';

        return($htmlString);
    }
}
