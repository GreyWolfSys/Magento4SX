<?php

namespace Altitude\SX\Controller\ProductShipping;

class ProductShipping extends \Magento\Framework\App\Action\Action
{
     public function execute()
     {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}