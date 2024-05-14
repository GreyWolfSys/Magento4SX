<?php

namespace Altitude\SX\Observer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\ObserverInterface;

class CustomerRegister implements ObserverInterface
{

    /** @var CustomerRepositoryInterface */
    protected $customerRepository;
    private $sx;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Altitude\SX\Model\SX $sx,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->customerRepository = $customerRepository;
        $this->sx = $sx;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Customer registered");
        $customer = $observer->getEvent()->getCustomer();
        $configs = $this->sx->getConfigValue(['cono', 'slsrepin', 'slsrepout', 'whse', 'defaultterms', 'shipviaty','createcustomer','sxcustomerid']);
        
//'createcustomer',
        extract($configs);
//$createcustomer=0;
        $statecd = "";
        $name = $customer->getFirstname() . " " . $customer->getLastname();
        $termstype = $defaultterms;
        $taxablety = "";

        //not so required fields
        $custno = "0";
        $addr1 = "";//"$address['street']";
        $addr2 = "";
        $addr3 = "";
        $city = "";//$address['city'];
        $state = "";
        $zipcd = "";
        $phoneno = "";
        $faxphoneno = "";
        $countrycd = "";
        $countycd = "";
        $email = "";

        $custtype = "";
        $salester = "";
        $pricetype = "";

        $pricecd = "1";
        $minord = "0";
        $maxord = "0";
        $siccd = "0";
        $bofl = "Y";
        $subfl = "Y";
        $shipreqfl = "N";
        $transproc = "arscr";
        //safe to ignore these fields
        $nontaxtype = "";
        $taxcert = "";
        $creditmgr = "";
        $taxauth = "";
        $dunsno = "";
        $user1 = "";
        $user2 = "";
        $user3 = "";
        $user4 = "";
        $user5 = "";
        $user6 = "0";
        $user7 = "0";
        $user8 = "";
        $user9 = "";
        $addon1 = "0";
        $addon2 = "0";
        $addon3 = "0";
        $addon4 = "0";
        $addon5 = "0";
        $addon6 = "0";
        $addon7 = "0";
        $addon8 = "0";
        $custpo = "";
        $inbndfrtfl = "";
        $outbndfrtfl = "";

        try {
              $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "sxcustomerid= " . $sxcustomerid);
            if ($createcustomer == 1)    {
                
					$GCCustomer = $this->sx->SalesCustomerInsert(
						$cono,   $custno,   $statecd,   $name,
						$addr1,   $addr2,   $city,   $state,
						$zipcd,   $phoneno,   $faxphoneno,   $siccd,
						$termstype,   $custtype,   $salester,   $bofl,
						$subfl,   $minord,   $maxord,   $taxcert,
						$shipviaty,   $whse,   $slsrepin,   $slsrepout,
						$shipreqfl,   $taxauth,   $taxablety,   $nontaxtype,
						$creditmgr,   $dunsno,   $user1,   $user2,
						$user3,   $user4,   $user5,   $user6,
						$user7,   $user8,   $user9,   $countrycd,
						$countycd,   $email,   $pricetype,   $pricecd,
						$transproc,   $addon1,   $addon2,
						$addon3,   $addon4,   $addon5,   $addon6,
						$addon7,   $addon8,   $inbndfrtfl,  $outbndfrtfl,
						$custpo,   $addr3
					);
                
                if (isset($GCCustomer)) {
                                    $custno = $GCCustomer["custno"];
                    
                $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "New custno: " . $custno);
                #$customer->setCustomAttribute("SX_CustNo", $custno);
    
                #$this->customerRepository->save($customer);
    
                $customer2 = $this->customerRepository->getById($customer->getId());
                $customer2->setCustomAttribute('sx_custno', $custno);
                $this->customerRepository->save($customer2);
                }
            } else {
                $custno=$sxcustomerid;
                $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Setting custno: " . $custno);
                $customer2 = $this->customerRepository->getById($customer->getId());
                $customer2->setCustomAttribute('sx_custno', $custno);
                $this->customerRepository->save($customer2);
            }   
        } catch (\Exception $e) {
            $this->sx->gwLog($e->getMessage());
        }
        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Customer registered - complete");
    }
}
