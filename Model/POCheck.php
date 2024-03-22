<?php

namespace Altitude\SX\Model;

use Magento\Framework\Event\ObserverInterface;

class POCheck implements ObserverInterface
{
    protected $sx;

    protected $resourceConnection;

    public function __construct(
        \Altitude\SX\Model\SX $sx,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->sx = $sx;
        $this->resourceConnection = $resourceConnection;
        $this->_url = $url;
        $this->_responseFactory = $responseFactory;
        $this->_messageManager = $messageManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
       // $configs = $this->sx->getConfigValue(['cono', 'sxcustomerid','autoinvoice','forceuniquepo']);
       // extract($configs);
        $cono = $this->sx->getConfigValue('cono');
        $sxcustomerid = $this->sx->getConfigValue('sxcustomerid');
        $autoinvoice = $this->sx->getConfigValue('autoinvoice');
        $forceuniquepo = $this->sx->getConfigValue('forceuniquepo');
        
        if ($forceuniquepo==0) {
            return true;
        }
        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "starting event for dupe po check");
        $moduleName = $this->sx->getModuleName(get_class($this));
        $sendtoerpinv = $this->sx->getConfigValue('sendtoerpinv');

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "dupe1.1");
        $customerSession2 = $objectManager->get('Magento\Customer\Model\Session');
        //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "dupe1.2");
        $customerData = $customerSession2->getCustomer();
        //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "dupe1.3");
        $bDuplicate=true;
        $order = $observer->getEvent()->getOrder();
        if ($order->getCustomerIsGuest()) {
            //  $this->sx->gwLog ("customer is guest");
            $custno = $sxcustomerid;
        } else {
           
        //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "dupe1.31");
            $customer = $objectManager->create('Magento\Customer\Model\Customer')->load($order->getCustomerId());
       // $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "dupe1.32");
            $custno = $customer->getData('sx_custno');
        //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "dupe1.33");
            if (!$custno) {
                
        //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "dupe1.34");
                // Not Logged In
                $custno = $sxcustomerid;
                //	$this->sx->gwLog ("sx custno is default");
                
            }
        }
        
        //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "dupe1.4");
        if ( $custno == $sxcustomerid) {
            // don't need to block these
            return true;
        }
        //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "dupe1.5");
        //$orderids = $observer->getEvent()->getOrderIds();
        
        //$dbConnection = $this->resourceConnection->getConnection();
        //$customerBeforeAuthUrl = $this->_url->getUrl('checkout/cart/index');
        $customerBeforeAuthUrl = $this->_url->getUrl('checkout', ['_fragment' => 'payment']);
        
        //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "dupe1");
        try {
            
            $payment = $order->getPayment();
            //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "dupe2");
              try{
                if (isset($payment)) {
                    $poNumber = $payment->getPoNumber();
                } else {
                    $poNumber = "";
                }
            } catch (\Exception $ePO) {
                $poNumber ="";
            }
            //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "dupe3");
             if (empty($poNumber)) {
                // don't need to block these
                return true;
            }
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "PO = " . $poNumber . "; custno = " . $custno);
            $gcnl = $this->sx->SalesOrderList($cono, $custno, "", "", "", $poNumber , "", "", 0, 0, 0, "", "", "", "", "", "", "");
            if (isset($gcnl["errorcd"])) {
                if ($gcnl["errorcd"]=="045-001"){
                    $bDuplicate=false;
                }
            }
          //SalesOrderList($cono, $custno, $shipto, $transtype, $whse, $custpo, $beginscustpo, $prod, $vendno, $bstagecd, $estagecd, $benterdt, $eenterdt, $binvoicedt, $einvoicedt, $bmoddate, $emoddate, $floorplancustfl, $moduleName = "")
        } catch (\Exception $e) {
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Dupe check error: " . $e->getMessage());
        }
        if ($bDuplicate){
            try {
                $message=__("Purchase order number has already been used and must be unique.");
                $this->_messageManager->addError($message);
                throw new \Magento\Framework\Exception\LocalizedException(__($message));
    
            } catch (\Exception $e) {
    
                //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "dupe7" . $customerBeforeAuthUrl);
                $this->_responseFactory->create()->setRedirect($customerBeforeAuthUrl)->sendResponse();
                   throw new \Magento\Framework\Exception\LocalizedException(               __($e->getMessage())             );
                return;
                exit;
                 // \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->critical($exception);
            }
        }
        return true;
    }
}
