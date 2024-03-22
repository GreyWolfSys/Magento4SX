<?php

namespace Altitude\SX\Model;

use Magento\Framework\Event\ObserverInterface;

class GWCart implements ObserverInterface
{
    protected $sx;

    public function __construct(
        \Altitude\SX\Model\SX $sx
    ) {
        $this->sx = $sx;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $moduleName = $this->sx->getModuleName(get_class($this));
        $sendtoerpinv = $this->sx->getConfigValue('sendtoerpinv');

        if ($sendtoerpinv == "1") {
            $invoice = $observer->getEvent()->getInvoice();
            $this->sx->SendToGreyWolf($invoice, $moduleName);
        }

        return true;
    }
}
