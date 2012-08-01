<?php
class Training_Gift_Model_Observer
{
    public function giftProcessing($event)
    {
        $quote = $event->getQuote();
        $giftHelper = $this->getHelper();

        try {
            $items = $quote->getItemsCollection();
            foreach ($items as $item) {
                if ($giftHelper->processDeletedItem($item)) {
                    continue;
                }
                $giftHelper->checkIfAllowsGift($item);
                $giftHelper->processGift($item);
            }
            $giftHelper->processRemoveGifts();
            $giftHelper->processAddGifts();
        } catch (Exception $e) {
            Mage::logException($e);
            throw $e;
        }
    }

    public function fakeIdsProcessing($event)
    {
        $helper = $this->getHelper();
        if ($helper->getFakeIdsProcessingIsRequired()) {
        }
    }

    protected function getHelper()
    {
        return Mage::helper('training_gift');
    }
}
