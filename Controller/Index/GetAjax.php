<?php

namespace Altitude\SX\Controller\Index;

use Magento\Framework\Controller\ResultFactory;

class GetAjax extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $coreSession;
    protected $customerSession;
    protected $_resultJsonFactory;
    protected $_storeManager;
    private $sx;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Altitude\SX\Model\SX $sx
    ) {
        $this->_pageFactory = $pageFactory;
        $this->coreSession = $coreSession;
        $this->customerSession = $customerSession;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_storeManager = $storeManager;
        $this->sx = $sx;

        return parent::__construct($context);
    }

    public function execute()
    {
        if ($this->sx->botDetector() || !$this->getRequest()->isXmlHttpRequest()) {
            $this->_redirect('/');
            return;
        }

        $moduleName = $this->sx->getModuleName(get_class($this));
        $controller = $this->getRequest()->getControllerName();
        $url = $this->sx->urlInterface()->getCurrentUrl();
        $configs = $this->sx->getConfigValue(['cono', 'sxcustomerid', 'whse','localpriceonly']);
        extract($configs);

        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "$moduleName: " . $controller . " / u: " . $url);

        if ($this->sx->getSession()->getApidown()) {
            $apidown = $this->sx->getSession()->getApidown();
        } else {
            $apidown = false;
        }

        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "config price started");
        $newprice = 0;

        if ($this->customerSession->isLoggedIn()) {
            // Logged In
            $customerSession = $this->customerSession;
            $customerData = $customerSession->getCustomer();
            $custno = $customerData['sx_custno'];
        } else {
            // Not Logged In
            $custno = $sxcustomerid;
        }

        if (empty($custno)) {
            $custno = $sxcustomerid;
        }

        $prod = $this->getRequest()->getParam('sku');

        if ($localpriceonly =="Magento") {
            $newprice = $product->getPrice();
        }
        elseif ($apidown == false) {
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "calling config price api. cono= " . $cono . " prod= " . $prod . " whse= " . $whse . " cust= " . $custno);

            try {
                $gcnl = $this->sx->SalesCustomerPricingSelect($cono, $prod, $whse, $custno, '', '1', $moduleName);
                if (!isset($gcnl) || isset($gcnl["fault"])) {
					$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "api failed in getAjax, retrying");
                    $gcnl = $this->sx->SalesCustomerPricingSelect($cono, $prod, $whse, $custno, '', '1', $moduleName);
                }
                if (!isset($gcnl) || isset($gcnl["fault"])) {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "error from pricing");
                    $this->sx->getSession()->setApidown(true);
                    if ($localpriceonly=="Hybrid") {
                        $newprice = $productObj->getPrice();
                    } else{
                        $newprice = 0;
                    }
                } elseif (isset($gcnl["price"])) {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "gcnl: " . json_encode($gcnl));
                    if ($gcnl["price"]>0) {
                        $newprice = $gcnl["price"];
                       // $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "price==::" . $newprice);
                        //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "pround==::" . $newprice);
                            if (!empty($gcnl["pround"])){
                                switch($gcnl["pround"])
                                {
                                    case 'u';
                                        $newprice=\ceil($newprice);
                                        break;
                                    case 'd';
                                        $newprice=\floor($newprice);
                                        break;
                                    case 'n';
                                        $newprice=\round($newprice);
                                        break;
                                    default;
                                        break;
                                }
                            } //end pround check
                        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "price==" . $newprice);
                    } else {
                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $productRepository = $objectManager->get('\Magento\Catalog\Model\ProductRepository');
                        $product = $productRepository->get($prod);
                        $newprice = $product->getPrice();
                        //$newprice = $gcnl["price"];
                        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "price===" . $newprice);
                    }
                }
            } catch (\Exception $e1) {
                $this->sx->gwLog($e1->getMessage());
            }
        } else {
            $this->sx->gwLog ("skipping config price api down");
        }
        if ($newprice==0 && $localpriceonly=="Hybrid") {
            $newprice = $productObj->getPrice();
        }

        $result = $this->_resultJsonFactory->create();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
        $store = $objectManager->get('\Magento\Framework\Locale\Resolver');  
        $this->sx->gwLog("result: $newprice");
        $this->sx->gwLog('currentCurrencyCode: ' . $store->getLocale());
        return $result->setData(json_encode([
            'result'                => $newprice,
            'currentCurrencyCode'   => $this->_storeManager->getStore()->getCurrentCurrencyCode(),
            'localeCode'            => $store->getLocale()
        ]));
    }
}
