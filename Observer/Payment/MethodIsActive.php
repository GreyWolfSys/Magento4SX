<?php

namespace Altitude\SX\Observer\Payment;

class MethodIsActive implements \Magento\Framework\Event\ObserverInterface
{
    private $sx;

    private $customerFactory;

    private $addressFactory;

    private $regionFactory;

 private $customerSession;

    public function __construct(
        \Altitude\SX\Model\SX $sx,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Directory\Model\RegionFactory $regionFactory
    ) {
        $this->sx = $sx;
        $this->customerFactory = $customerFactory;
        $this->addressFactory = $addressFactory;
        $this->regionFactory = $regionFactory;
        $this->customerSession = $customerSession;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $moduleName = $this->sx->getModuleName(get_class($this));
        $url = $this->sx->urlInterface()->getCurrentUrl();
        $configs = $this->sx->getConfigValue(['cono', 'sxcustomerid', 'hidepmt','blockpofordefault']);
        extract($configs);

        $customerSession = $this->customerSession;
        if ($customerSession->isLoggedIn()) {
            // Logged In
            $customerData = $customerSession->getCustomer();
            $custno = $customerData['sx_custno'];
        } else {
            // Not Logged In
            $custno = $sxcustomerid;
        }
        
        $method = $observer->getEvent()->getMethodInstance();
        $methodTitle = $method->getTitle();

        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Payment method title: " . $methodTitle);
        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Payment method: " . $method->getCode());
        if ($blockpofordefault=="1"){
            if ($method->getCode() == "purchaseorder"){
                if ($custno == $sxcustomerid) {
                    $result = $observer->getEvent()->getResult();
                    $result->isAvailable = false;
                    $result->setData('is_available', false);
                }
            }    
        } 
        if (!empty($hidepmt)) {
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "payment method check");

            $terms = "notset";
            $gcnl = $this->sx->SalesCustomerSelect($cono, $custno, $moduleName);

            if (isset($gcnl["errordesc"])) {
                if ($gcnl["errordesc"] != "") {
                    $nocust = true;
                } else {
                    $nocust = false;
                }
            } else {
                $nocust = false;
            }
            if ($nocust) {
                $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Error retrieving results.");
            } else {
                $terms = $gcnl["termstype"];
            }

            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "cust=" . $custno . " terms=" . $terms);



            $result = $observer->getEvent()->getResult();
            $methods = explode(",", $hidepmt);
            foreach ($methods as $splitmethod) {
                $details = explode(":", $splitmethod);
                //COD:Credit Card
                if (strtolower(trim($details[0])) == strtolower(trim($terms)) && strtolower(trim($details[1])) == strtolower(trim($methodTitle))) {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Pmt method not allowed!");
                    $result->isAvailable = false;
                    $result->setData('is_available', false);
                }
            }
        }
    }
}
