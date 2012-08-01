<?php
class Training_Gift_Model_Quote_Total_Gift extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);

        $items = $this->_getAddressItems($address);
        $quote = $address->getQuote();

        foreach ($items as $item) {
            if ($item->getIsGift()) {
                $address->setBaseTotalAmount(
                    'subtotal', $address->getBaseSubtotal() - $item->getBaseRowTotal()
                );
                $address->setTotalAmount(
                    'subtotal', $address->getSubtotal() - $item->getRowTotal()
                );
                $item->setPrice(0)
                    ->setBaseOriginalPrice(0)
                    ->calcRowTotal();
            }
        }

        return $this;
    }
}
