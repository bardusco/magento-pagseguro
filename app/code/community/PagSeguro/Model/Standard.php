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
 * @copyright  Copyright (c) 2008 WebLibre (http://www.weblibre.com.br) - Guilherme Dutra (godutra@gmail.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 *
 * PagSeguro Checkout Module
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class PagSeguro_Model_Standard extends Mage_Payment_Model_Method_Abstract {
    //changing the payment to different from cc payment type and pagseguro payment type
    const PAYMENT_TYPE_AUTH = 'AUTHORIZATION';
    const PAYMENT_TYPE_SALE = 'SALE';
    protected $_code  = 'pagseguro_standard';
    protected $_formBlockType = 'pagseguro/standard_form';

    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;
    protected $_canCapture = true;

    /**
     * Get pagseguro session namespace
     *
     * @return PagSeguro_Model_Session
     */
    public function getSession() {
        return Mage::getSingleton('pagseguro/session');
    }
    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }
    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote() {
        return $this->getCheckout()->getQuote();
    }
    public function createFormBlock($name) {
        $block = $this->getLayout()->createBlock('pagseguro/standard_form', $name)
                ->setMethod('pagseguro_standard')
                ->setPayment($this->getPayment())
                ->setTemplate('PagSeguro/standard/form.phtml');
        return $block;
    }
    public function onOrderValidate(Mage_Sales_Model_Order_Payment $payment) {
        return $this;
    }
    public function onInvoiceCreate(Mage_Sales_Model_Invoice_Payment $payment) {
    }
    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('pagseguro/standard/redirect', array('_secure' => true));
    }
    function formatNumber ($number) {
        return sprintf('%.2f', (double) $number) * 100;
    }
    function _trataTelefone($tel) {
        $numeros = preg_replace('/\D/','',$tel);
        $tel     = substr($numeros,sizeof($numeros)-9);
        $ddd     = substr($numeros,sizeof($numeros)-11,2);
        return array($ddd, $tel);
    }
    private function _endereco($endereco) {
        require_once(dirname(__FILE__).'/trata_dados.php');
        return TrataDados::trataEndereco($endereco);
    }
    public function getStandardCheckoutFormFields() {
        $orderId = $this->getCheckout()->getLastOrderId();
        $order   = Mage::getModel('sales/order')->load($orderId);

        $a = $order->getIsNotVirtual() ? $order->getShippingAddress() : $order->getShippingAddress();

        //$a = $this->getQuote()->getShippingAddress();

        // Fazendo o telefone
        list($ddd, $telefone) = $this->_trataTelefone($a->getTelephone());
        // Dados de endereço (Endereço)
        list($endereco, $numero, $complemento) = $this->_endereco($a->getStreet(1).' '.$a->getStreet(2));
        // Dados de endereço (CEP)
        $cep = preg_replace('@[^\d]@', '', $a->getPostcode());
        // Montando os dados para o formulário
        $sArr = array(
                'encoding'          => 'UTF-8',
                'email_cobranca'    => $this->getConfigData('emailID'),
                'Tipo'              => "CP",
                'Moeda'             => "BRL",
                'ref_transacao'     => $order->getRealOrderId(),
                'cliente_nome'      => $a->getFirstname().' '.$a->getLastname(),
                'cliente_cep'       => $cep,
                'cliente_end'       => $endereco,
                'cliente_num'       => $numero,
                'cliente_compl'     => $complemento,
                'cliente_bairro'    => "?",
                'cliente_cidade'    => $a->getCity(),
                'cliente_uf'        => $a->getRegionCode(),
                'cliente_pais'      => $a->getCountry(),
                'cliente_ddd'       => $ddd,
                'cliente_tel'       => $telefone,
                'cliente_email'     => $order->getCustomerEmail(),
        );

        $items = $order->getAllVisibleItems();

        $shipping_amount = $order->getBaseShippingAmount();
        $tax_amount = $order->getBaseTaxAmount();
        $discount_amount = $order->getBaseDiscountAmount();

        if ($items) {
            $i = 1;
            foreach($items as $item) {
                $item_price = 0;
                $item_qty = $item->getQtyToShip();

                if ($children = $item->getChildrenItems()) {
                    foreach ($children as $child) {
                        $item_price += $child->getBasePrice() * $child->getQtyOrdered() / $item_qty;
                    }
                    $item_price = $this->formatNumber($item_price);
                }
                if (!$item_price) {
                    $item_price = $this->formatNumber($item->getBasePrice());
                }
                $sArr = array_merge($sArr, array(
                        'item_descr_'.$i   => $item->getName(),
                        'item_id_'.$i      => $item->getSku(),
                        'item_quant_'.$i   => $item_qty,
                        'item_peso_'.$i    => round($item->getWeight()),
                        'item_valor_'.$i   => $item_price,
                ));
                $i++;
            }

            if ($tax_amount > 0) {
                $tax_amount = $this->formatNumber($tax_amount);
                $sArr = array_merge($sArr, array(
                    'item_descr_'.$i   => "Taxa",
                    'item_id_'.$i      => "taxa",
                    'item_quant_'.$i   => 1,
                    'item_valor_'.$i   => $tax_amount,
                ));
                $i++;
            }

            if ($discount_amount != 0) {
                $discount_amount = $this->formatNumber($discount_amount);
                if (preg_match("/^1\.[23]/i", Mage::getVersion())) {
                    $discount_amount = -$discount_amount;
                }
                $sArr = array_merge($sArr, array(
                    'extras'   => $discount_amount,
                ));
            }

            $module = new PagSeguro_Model_Carrier_ShippingMethod();
            $active = $module->getConfigData('active');

            if(!$active){
                if ($shipping_amount > 0) {
                    $shipping_amount = $this->formatNumber($shipping_amount);
                    // passa o valor do frete como um produto
                    $sArr = array_merge($sArr, array(
                        'item_descr_'.$i   => substr($order->getShippingDescription(), 0, 100),
                        'item_id_'.$i      => "frete",
                        'item_quant_'.$i   => 1,
                        'item_valor_'.$i   => $shipping_amount,
                    ));
                    $i++;
                }
            }
        }

        $transaciton_type = $this->getConfigData('transaction_type');
        $totalArr = $a->getTotals();
        $shipping = sprintf('%.2f', $order->getBaseShippingAmount());

        if ($active) {
            // passa o valor do frete total em uma única variavel para o pagseguro, utilizado junto com o modulo de correio
            $sArr = array_merge($sArr, array('item_frete_1' => str_replace(".", ",", $shipping*100) ));
            $e='EN';
            if($order->_data['shipping_method']=='pagseguro_pagseguro:Sedex')$e='SD';
            $sArr = array_merge($sArr, array('tipo_frete' => $e ));
        }
        
        $sReq = '';
        $rArr = array();
        foreach ($sArr as $k=>$v) {
            /*
               replacing & char with and. otherwise it will break the post
            */
            $value =  str_replace("&","and",$v);
            $rArr[$k] =  $value;
            $sReq .= '&'.$k.'='.$value;
        }
       
        return $rArr;
    }
    //  define a url do pagseguro
    public function getPagSeguroUrl() {
        $url='https://pagseguro.uol.com.br/checkout/checkout.jhtml';
        return $url;
    }

    public function getDebug() {
        return Mage::getStoreConfig('pagseguro/wps/debug_flag');
    }

    public function ipnPostSubmit() {
        $sReq = '';
        foreach($this->getIpnFormData() as $k=>$v) {
            $sReq .= '&'.$k.'='.urlencode(stripslashes($v));
        }
        //append ipn commdn
        $sReq .= "&cmd=_notify-validate";
        $sReq = substr($sReq, 1);
        if ($this->getDebug()) {
            $debug = Mage::getModel('pagseguro/api_debug')
                    ->setApiEndpoint($this->getPagSeguroUrl())
                    ->setRequestBody($sReq)
                    ->save();
        }
        $http = new Varien_Http_Adapter_Curl();
        $http->write(Zend_Http_Client::POST,$this->getPagSeguroUrl(), '1.1', array(), $sReq);
        $response = $http->read();
        $response = preg_split('/^\r?$/m', $response, 2);
        $response = trim($response[1]);
        if ($this->getDebug()) {
            $debug->setResponseBody($response)->save();
        }
        //when verified need to convert order into invoice
        $id = $this->getIpnFormData('invoice');
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($id);
        if ($response=='VERIFIED') {
            if (!$order->getId()) {
                /*
                 * need to have logic when there is no order with the order id from pagseguro
                */
            } else {
                if ($this->getIpnFormData('mc_gross')!=$order->getGrandTotal()) {
                    //when grand total does not equal, need to have some logic to take care
                    $order->addStatusToHistory(
                            $order->getStatus(),//continue setting current order status
                            Mage::helper('pagseguro')->__('Order total amount does not match pagseguro gross total amount')
                    );
                } else {
                    /*
                    //quote id
                    $quote_id = $order->getQuoteId();
                    //the customer close the browser or going back after submitting payment
                    //so the quote is still in session and need to clear the session
                    //and send email
                    if ($this->getQuote() && $this->getQuote()->getId()==$quote_id) {
                    $this->getCheckout()->clear();
                    $order->sendNewOrderEmail();
                    }
                    */
                    /*
                       if payer_status=verified ==> transaction in sale mode
                       if transactin in sale mode, we need to create an invoice
                       otherwise transaction in authorization mode
                    */
                    if ($this->getIpnFormData('payment_status')=='Completed') {
                        if (!$order->canInvoice()) {
                            //when order cannot create invoice, need to have some logic to take care
                            $order->addStatusToHistory(
                                    $order->getStatus(),//continue setting current order status
                                    Mage::helper('pagseguro')->__('Error in creating an invoice')
                            );
                        } else {
                            //need to save transaction id
                            $order->getPayment()->setTransactionId($this->getIpnFormData('txn_id'));
                            //need to convert from order into invoice
                            $invoice = $order->prepareInvoice();
                            $invoice->register()->capture();
                            Mage::getModel('core/resource_transaction')
                                    ->addObject($invoice)
                                    ->addObject($invoice->getOrder())
                                    ->save();
                            $order->addStatusToHistory(
                                    'processing',//update order status to processing after creating an invoice
                                    Mage::helper('pagseguro')->__('Invoice '.$invoice->getIncrementId().' was created')
                            );
                        }
                    } else {
                        $order->addStatusToHistory(
                                $order->getStatus(),
                                Mage::helper('pagseguro')->__('Received IPN verification'));
                    }
                }//else amount the same and there is order obj
                //there are status added to order
                $order->save();
            }
        }else {
            /*
               Canceled_Reversal
               Completed
               Denied
               Expired
               Failed
               Pending
               Processed
               Refunded
               Reversed
               Voided
            */
            $payment_status= $this->getIpnFormData('payment_status');
            $comment = $payment_status;
            if ($payment_status == 'Pending') {
                $comment .= ' - ' . $this->getIpnFormData('pending_reason');
            } elseif ( ($payment_status == 'Reversed') || ($payment_status == 'Refunded') ) {
                $comment .= ' - ' . $this->getIpnFormData('reason_code');
            }
            //response error
            if (!$order->getId()) {
                /*
                 * need to have logic when there is no order with the order id from pagseguro
                */
            } else {
                $order->addStatusToHistory(
                        $order->getStatus(),//continue setting current order status
                        Mage::helper('pagseguro')->__('PagSeguro IPN Invalid.'.$comment)
                );
                $order->save();
            }
        }
    }
}
