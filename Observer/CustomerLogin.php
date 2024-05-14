<?php

namespace Altitude\SX\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerLogin implements ObserverInterface
{
    private $sx;

    private $customerFactory;

    private $addressFactory;

    private $regionFactory;
    private $_customerRepositoryInterface;

    public function __construct(
        \Altitude\SX\Model\SX $sx,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
    ) {
        $this->sx = $sx;
        $this->customerFactory = $customerFactory;
        $this->addressFactory = $addressFactory;
        $this->regionFactory = $regionFactory;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
    }

        public function ProcessAddress($item,$customer,$addrSet){
                        if(isset($item["errordesc"]) && $item["errordesc"]!="" ) {
                              $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Error " . $item["errordesc"]);
                              return "";
                          }
                            if (isset($item["phoneno"])) {
                                $phone = $item["phoneno"];
                                if (strlen($phone) < 1) {
                                    $phone = "1112223333";
                                }
                            } else {
                                $phone = "1112223333";
                            }

                         //   try {
                                unset($address);

                                try {
                                    //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , " Looping addresses");
                                    foreach ($customer->getAddresses() as $address1) {
                                        $erp = $address1->getData("ERPAddressID");
                                        if ($erp == $item["shipto"]) {
                                            $address = $address1;
                                            break;
                                        }
                                    }
                                } catch (\Exception $e) {
                                    $this->sx->gwLog(json_encode($e->getMessage()));
                                }

                                if (!empty($item["countrycd"])) {
                                    $countrycode = $item["countrycd"];
                                } else {
                                    $countrycode = "US";
                                }

                                //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , " Address - getting region from erp addr $countrycode");

                             /*    try {
                                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                                    $region = $objectManager->create('Magento\Directory\Model\Region')->loadByCode($item["state"], $countrycode);
                                } catch (Exception $exregion) {
                                    $this->sx->gwLog(json_encode($exregion->getMessage()));

                                }*/

                                   $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                                 //$_regionFactory = $objectManager->get('Magento\Directory\Model\RegionFactory');
                                 //$regionModel = $_regionFactory->create();
                                 //$region = $_regionFactory->loadByCode($addressData["region_id"], $countrycode);

                                           // if (empty($region->getId())) {
                                                $region = $objectManager->create('Magento\Directory\Model\Region')->loadByCode($item["statecd"], $countrycode); //->load($item["statecd"]); // Region Id

                                               // $region = $this->regionFactory->loadByCode($addressData["region_id"], $countrycode);
                                                $regionId = $region->getId();
                                          //  } else {
                                          //      $regionId = $region->getId();
                                $statecd = $regionId;
                                
                                
   //  $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "s state= " . $item["statecd"]);
                    
   // $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "s regionid= " . $regionId );
                 
   // $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "s statecd " . $statecd);
                                          //  }
//ob_start();
//var_dump($region);
//$result = ob_get_clean();
 //$this->sx->gwLog($result);
 //$this->sx->gwLog($regionId);
                                //$addressData['state'] = $regionId;

                                if (!isset($address)) {
                                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , " No address found for " . $item["shipto"]);
                                    $address = $this->addressFactory->create();
                                }
                              
                                
                                $address->setCustomerId($customer->getId());
                                $address->setFirstname($customer->getFirstname());
                                $address->setLastname($customer->getLastname());
                                $address->setStreet([$item["addr1"],$item["addr2"]]);
                                $address->setCompany($item["name"]);
                                $address->setCity($item["city"]);
                                $address->setRegionId($statecd);
                                $address->setPostcode($item["zipcd"]);
                                $address->setCountryId($countrycode);
                                $address->setTelephone($phone);
                                $address->setFax($item["faxphoneno"]);
                                //$address->setIsDefaultBilling('0');
                                //$address->setIsDefaultShipping('0');
                                if ($addrSet) {
                                    //$address->setIsDefaultBilling('0');
                                } else {
                                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "2 setting default billing: " . $item["addr1"]);
                                    $address->setIsDefaultBilling('1');
                                    $addrSet=true;
                                }
                                if ($addrSet){
                                    //$address->setIsDefaultShipping('0');
                                } else {
                                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "2 setting default shipping: " . $item["addr1"]);
                                    $address->setIsDefaultShipping('1');
                                    $addrSet=true;
                                }
                                $address->setSaveInAddressBook('1');
                                $address->SetData("ERPAddressID", $item["shipto"]);
                                $address->save();

                                $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , " shipto=" . $address->getData("ERPAddressID") . " and " . $item["shipto"]);
                         //   } catch (\Exception $e) {
                         //       $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "ERROR");
                         //       $this->sx->gwLog(json_encode($e->getMessage()));
                         //   }

        }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //error_log ("Address import check");
        //$disableAddressImport = $this->sx->getConfigValue('defaults/gwcustomer/disable_address_import');
        //if ($disableAddressImport) {
        //    return;
        //}
        //error_log("address import");
       // $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Running address import into Altitude");
       // $debug_export = var_export($observer, true);
       // $this->sx->gwLog($debug_export);

        $moduleName = $this->sx->getModuleName(get_class($this));
        $configs = $this->sx->getConfigValue(['cono', 'sxcustomerid', 'importshipto','defaultcurrency']);
        extract($configs);


        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "address import allowed, continuing");
        $customerSession = $this->sx->getSession();
        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "$moduleName: Check if logged in");

        if ($customerSession->isLoggedIn()) {
            $customerData = $customerSession->getCustomer();

            $customer = $customerSession->getCustomer();
            $cust = $customerSession->getCustomerData();

            $sxcustno = $customerData['sx_custno'];
            $cattrValue = $customer->getCustomAttribute('SX_CustNo');

            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "$moduleName: Customer logged in!");

            if ($sxcustno == "" || $sxcustno == "0") {
                $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "$moduleName: SX_Custno is empty or zero. Exit.");

                return;
            }

            try {
                $GCCust = $this->sx->SalesCustomerSelect($cono, $sxcustno, $moduleName);

                if (isset($GCCust)) {
                    $_customer = $this->customerFactory->create();
                    $_customer->load($customer->getId());
                    
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "setting cust data, tax type=" . $GCCust["taxabletype"]);
                    // $customerData = $this->customerFactory->create()->load($customer->getId())->getDataModel();
                    // $customerData->setCustomAttribute('warehouse', $GCCust["whse"]);
                    // $this->_customerRepositoryInterface->save($customerData);
                     
                      //$_customer->setData('warehouse', 'xx');
 
                     $_customer->setData('taxabletype', $GCCust["taxabletype"] . "");
 
                     if ($sxcustno != $sxcustomerid){
                         //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "setting warehouse now");
                         $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "setting warehouse to ".  $GCCust["whse"]);
                         $_customer->setData('whse', $GCCust["whse"] . "");
                         $_customer->setData('erpshipviadesc', $GCCust["shipviatydesc"] );
                         $_customer->setData('erpshipvia', $GCCust["shipviaty"] );
                     }
                    
           // $objectManager = \Magento\Framework\App\ObjectManager::getInstance();        
           // $customer2 = $objectManager->create('Magento\Customer\Model\Customer')->load($customer->getId());
           // $warehouse = $customer2->getData('whse');
          //    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "cust  warehouse is " . $warehouse);
            
                if (isset($defaultcurrency) && ($sxcustno != $sxcustomerid)){
                        $_customer->setData('currencyty', $GCCust["currencyty"]);
                        
                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
                        if ($GCCust["currencyty"]=="US" || $GCCust["currencyty"]=="US") {
                             $currency = "USD";
                        } elseif ($GCCust["currencyty"]=="CA" || $GCCust["currencyty"]=="CAD") {
                             $currency = "CAD";
                        } elseif ($GCCust["currencyty"]=="EU" || $GCCust["currencyty"]=="EUR") {
                             $currency = "EUR";
                        } else {
                            $currency = $defaultcurrency; // set currency code which you want to set //set this to default currency setting...new setting
                        }
                        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Setting currency to " . $currency);
                        if ($currency) {
                           $storeManager->getStore()->setCurrentCurrencyCode($currency);
                        }
                    }
                    
                   /* try {
                        if (!empty($GCCust["user6"])){
                            $_customer->setData('moq_switch', 1);
                            $_customer->setData('moq_value', $GCCust["user6"]);
                        }
                    } catch (\Exception $e1) {
                        $this->sx->gwLog(json_encode($e1->getMessage()));
                    }*/
                    $_customer->save();
                }
            } catch (\Exception $e) {
                $this->sx->gwLog(json_encode($e->getMessage()));
            }
        } else {
            $sxcustno = "";
            $customer = $observer->getEvent()->getCustomer();
        }
        if (isset($importshipto)) {
            if ($importshipto != "1") {
                //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "address import diallowedallowed, exiting");
                return;
            }
        }
        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "$moduleName: Default cust no: $sxcustomerid");
        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "$moduleName: Cust no: $sxcustno");
        //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , " checking if should check addresses for arsc");
        if ($customerSession->isLoggedIn() && $sxcustno != $sxcustomerid) {
            //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , " checking addresses for arsc");
            try{
                if (isset($GCCust["addr1"])){
                    //put arsc address in addressbook
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , " we have an address for arsc");
                    try {
                        //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , " Looping addresses for arsc check");
                        foreach ($customer->getAddresses() as $address1) {
                            $erp = $address1->getData("ERPAddressID");
                            if (empty($erp)) {
                                //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , " Found existing arsc address");
                                $address = $address1;
                                break;
                            }
                        }
                    } catch (\Exception $e) {
                        $this->sx->gwLog(json_encode($e->getMessage()));
                    }
                    
                      if (isset($GCCust["phoneno"])) {
                        $phone = $GCCust["phoneno"];
                        if (strlen($phone) < 1) {
                            $phone = "1112223333";
                        }
                    } else {
                        $phone = "1112223333";
                    }
                            
                            

                    if (!empty($GCCust["countrycd"])) {
                        $countrycode = $GCCust["countrycd"];
                    } else {
                        $countrycode = "US";
                    }
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
  //  $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "state= " . $GCCust["state"]);
                    $region = $objectManager->create('Magento\Directory\Model\Region')->loadByCode($GCCust["state"], $countrycode); //->load($item["statecd"]); // Region Id
                    $regionId = $region->getId();
 //   $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "regionid= " . $regionId );
                    $statecd = $regionId;
 //   $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "statecd " . $statecd);
                    //////////////
                    if (!isset($address)) {
                        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , " No address found for " . $GCCust["custno"]);
                        $address = $this->addressFactory->create();
                    }
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , " setting arsc address: " . $GCCust["addr1"]);
                    $bShip=$customer->getDefaultShipping();
                    $bBill=$customer->getDefaultBilling();
                    $address->setCustomerId($customer->getId());
                    $address->setFirstname($customer->getFirstname());
                    $address->setLastname($customer->getLastname());
                    $address->setStreet([$GCCust["addr1"],$GCCust["addr2"] , $GCCust["addr3"]]);
                    $address->setCompany($GCCust["name"]);
                    $address->setCity($GCCust["city"]);
                    $address->setRegionId($statecd);
                    $address->setPostcode($GCCust["zipcd"]);
                    $address->setCountryId($countrycode);
                    $address->setTelephone($phone);
                    $address->setFax($GCCust["faxphoneno"]);
                    if (isset($bBill)) {
                        $addrSet=true;
                        //$address->setIsDefaultBilling('0');
                    } else {
                        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , " setting default billing: " . $GCCust["addr1"]);
                        $address->setIsDefaultBilling('1');
                        $addrSet=true;
                    }
                    if (isset($bShip)){
                        $addrSet=true;
                        //$address->setIsDefaultShipping('0');
                    } else {
                        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , " setting default shipping: " . $GCCust["addr1"]);
                        $address->setIsDefaultShipping('1');
                        $addrSet=true;
                    }
                    $address->setSaveInAddressBook('1');
                    //$address->SetData("ERPAddressID", $GCCust["shipto"]);
                    $address->save();
                    //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "arsc address saved: " . $GCCust["addr1"] );
                    $customer = $customerSession->getCustomer();
                    //////////////
                }
            } catch (\Exception $e) {
                $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "arsc address error: " . json_encode($e->getMessage()));
            }
            try {
                $GCShip = $this->sx->SalesShipToList($cono, $sxcustno, $moduleName);
                $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "post ship-to API");
                if (isset($GCShip)) {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "gcship is set");
                    if (isset($GCShip["SalesShipToListResponseContainerItems"]) ) {
                        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "multiple records");
                        foreach ($GCShip["SalesShipToListResponseContainerItems"] as $item) {
                          $this->ProcessAddress($item,$customer,$addrSet);
                        }
                    } else {
                        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "single record");
                         $this->ProcessAddress($GCShip,$customer,$addrSet);
                    }
                }
            } catch (\Exception $e) {
                $this->sx->gwLog(json_encode($e->getMessage()));
            }
        }
        return "";
    }
}
