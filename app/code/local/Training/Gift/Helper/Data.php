<?php

class Training_Gift_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $targetItems = array();
    protected $_fakeIdsProcessingRequired = false;
    protected $_giftsPresent = array();
    protected $_giftsToDelete = array();
    protected $_checkoutSession = null;
    protected $_refusedGifts = array();

    public function init()
    {
        $this->_checkoutSession = Mage::getSingleton('checkout/session');
        $this->_refusedGifts = $this->_checkoutSession->getRefusedItems();

        if (! $this->_refusedGifts) {
            $this->_refusedGifts = array();
        }
    }

    public function processGift(Mage_Sales_Model_Quote_Item $item)
    {
        if ($item->getIsGift()) {
            $id = $item->getGiftRelatedToItem();
            $targetItem = $this->getQuote()->getItemById($id);
            if (! $targetItem) {
                $this->_giftsToDelete[] = $item;
            } else {
                $targetQty = $targetItem->getQty();
                if ($targetQty < $item->getQty()) {
                    $item->setQty($targetQty);
                }
                $this->_giftsPresent[] = $id;
            }
        }
    }

    protected function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    public function processRemoveGifts()
    {
        foreach ($this->_giftsToDelete as $item) {
            $this->getQuote()->removeItem($item->getId());
        }
    }

    public function checkIfAllowsGift(Mage_Sales_Model_Quote_Item $item)
    {
        if ($item->getSku() === 'ottoman') {
            $fakeId = mt_rand(1, 100) . '-' . uniqid();
            $item->setFakeId($fakeId);

            $this->targetItems[] = $item;
        }
    }

    public function processAddGifts()
    {
        if (0 == count($this->targetItems)) {
            return;
        }
        $gift = $this->getGift();
        foreach ($this->targetItems as $targetItem) {
            if (! in_array($targetItem->getId(), $this->_giftsPresent)
                && ! in_array($gift->getId(), $this->_refusedGifts)
            ) {
                $item = Mage::getSingleton('checkout/session')->getQuote()
                    ->addProduct($gift, $targetItem->getQty());
                $item->setIsGift(true);
                $item->setGiftRelatedToItem($targetItem->getId());
                $item->setRelatedFakeId($targetItem->getFakeId());

                /* it is not recommended to use custom price */
                //$item->setOriginalCustomPrice(0)
                //    ->setCustomPrice(0);

                $this->_fakeIdsProcessingRequired = true;
            }
        }
    }

    public function getFakeIdsProcessingIsRequired()
    {
        return $this->_fakeIdsProcessingRequired;
    }

    public function processFakeIds()
    {
        $items = Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection();
        if (! $items) {
            return;
        }
        $items->save();
        $fakeIds = array();
        foreach ($items as $item) {
            if ($item->getFakeId()) {
                $fakeIds[$item->getFakeId()] = $item->getId();
            }
        }
        if (! $fakeIds) {
            return;
        }
        foreach ($items as $item) {
            if ($item->getIsGift() && $relatedFakeId = $item->getRelatedFakeId()) {
                $relatedId = $fakeIds[$relatedFakeId];
                $item->setGiftRelatedToItem($relatedId);
                $item->save();
            }
        }
    }

    protected function getGift()
    {
        $giftProductId = Mage::getModel('catalog/product')->getIdBySku('aufkleber');
        $gift = Mage::getModel('catalog/product')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($giftProductId);
        $gift->addCustomOption('gift_product', true);
        return $gift;
    }

    public function processDeletedItem($item)
    {
        if (! $item->isDeleted()) {
            return false;
        }
        if ($item->getIsGift()) {
            $this->_refusedGifts[] = $item->getProductId();
            $this->_checkoutSession->setRefusedGifts($this->_refusedGifts);
        }
        return true;
    }
}
