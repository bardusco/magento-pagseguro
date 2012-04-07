<?php

class PagSeguro_Block_Standard_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $standard = Mage::getModel('pagseguro/standard');

        $form = new Varien_Data_Form();
        $form->setAction($standard->getPagSeguroUrl())
            ->setId('pagseguro_standard_checkout')
            ->setName('pagseguro_standard_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);
        foreach ($standard->getStandardCheckoutFormFields() as $field=>$value) {
            $form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value));
        }
        $html = '<html><body>';
        $html.= $this->__('Você será redirecionado para o PagSeguro em alguns instantes.');
        $html.= $form->toHtml();
        $html.= '<script type="text/javascript">document.getElementById("pagseguro_standard_checkout").submit();</script>';
        $html.= '</body></html>';

        return $html;
    }
}
