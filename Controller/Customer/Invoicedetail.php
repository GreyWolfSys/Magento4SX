<?php

namespace Altitude\SX\Controller\Customer;

class Invoicedetail extends \Altitude\SX\Controller\CustomerAbstract
{
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
