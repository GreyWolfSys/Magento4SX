<?php

namespace Altitude\SX\Controller\Customer;

class Quote extends \Altitude\SX\Controller\CustomerAbstract
{
    public function execute()
    {
        $this->_view->loadLayout();

        $this->_view->renderLayout();
    }
}
