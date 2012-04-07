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

/**
 * PagSeguro Standard Checkout Controller
 *
 * @author      Michael Granados <mike@visie.com.br>
 */
class PagSeguro_StandardController
    extends Mage_Core_Controller_Front_Action
{
    /**
     * Order instance
     */
    protected $_order;

    /**
     *  Get order
     *
     *  @param    none
     *  @return	  Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if ($this->_order == null) {
        }
        return $this->_order;
    }

    /**
     * Get singleton with pagseguro strandard order transaction information
     *
     * @return PagSeguro_Model_Standard
     */
    public function getStandard()
    {
        return Mage::getSingleton('pagseguro/standard');
    }

    /**
     * When a customer chooses Paypal on Checkout/Payment page
     *
     */
    public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setPaypalStandardQuoteId($session->getQuoteId());
        $this->getResponse()->setBody($this->getLayout()->createBlock('pagseguro/standard_redirect')->toHtml());
        $session->unsQuoteId();
    }

    /**
     * Retorno dos dados feito pelo PagSeguro
     */
    public function obrigadoAction()
    {
        $standard = $this->getStandard();
        # É um $_GET, trate normalmente
        if (!$this->getRequest()->isPost()) {
            $session = Mage::getSingleton('checkout/session');
            $session->setQuoteId($session->getPaypalStandardQuoteId(true));
            /**
             * set the quote as inactive after back from pagseguro
             */
            Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
            /**
             * send confirmation email to customer
             */
            $order = Mage::getModel('sales/order');
            $order->load(Mage::getSingleton('checkout/session')->getLastOrderId());
            if($order->getId()){
                $order->sendNewOrderEmail();
            }

            $url = $standard->getConfigData('retorno');
            $this->_redirect($url);
        } else {
            // Vamos ao retorno automático
            if (!defined('RETORNOPAGSEGURO_NOT_AUTORUN')) {
                define('RETORNOPAGSEGURO_NOT_AUTORUN', true);
                define('PAGSEGURO_AMBIENTE_DE_TESTE', true);
            }
            // Incluindo a biblioteca escrita pela Visie
            include_once(dirname(__FILE__).'/retorno.php');
            // Brincanco com a biblioteca
            RetornoPagSeguro::verifica($_POST, false, array($this, 'retornoPagSeguro'));
        }
    }
    
    public function retornoPagSeguro($referencia, $status, $valorFinal, $produtos, $post)
    {
        $salesOrder = Mage::getSingleton('sales/order');
        $order = $salesOrder->loadByIncrementId($referencia);

        if ($order->getId()) {
            // Verificando o Status passado pelo PagSeguro
            if (in_array(strtolower($status), array('completo', 'aprovado'))) {
                if (!$order->canInvoice()) {
                    //when order cannot create invoice, need to have some logic to take care
                    $order->addStatusToHistory(
                        $order->getStatus(), // keep order status/state
                        'Error in creating an invoice',
                        $notified = false
                    );
                } else {
                    $order->getPayment()->setTransactionId($post->TransacaoID);
                    $invoice = $order->prepareInvoice();
                    $invoice->register()->pay();
                    $changeTo = Mage_Sales_Model_Order::STATE_COMPLETE;                    
                    Mage::getModel('core/resource_transaction')
                       ->addObject($invoice)
                       ->addObject($invoice->getOrder())
                       ->save();
                    $comment = sprintf('Invoice #%s created. Pago com %s.', $invoice->getIncrementId(), "PagSeguro");
                    $order->addStatusToHistory(
                       $changeTo,
                       $comment,
                       $notified = true
                    );
                }
            } else {
                // Não está completa, vamos processar...
                $comment = $status;
                if ( strtolower(trim($status))=='cancelado' ) {
                    $changeTo = Mage_Sales_Model_Order::STATE_CANCELED;
                } else {
                    // Esquecer o Cancelado e o Aprovado/Concluído
                    $changeTo = Mage_Sales_Model_Order::STATE_HOLDED;
                    $comment .= ' - ' . $post->TipoPagamento;
                }

                $order->addStatusToHistory(
                    $changeTo,
                    $comment,
                    $notified = false
                );
            }
            $order->save();
            // Enviar o e-mail assim que receber a confirmação
            if (in_array(strtolower($status), array('completo', 'aprovado'))) {
                $order->sendNewOrderEmail();
            }
        }
        
    }
}
