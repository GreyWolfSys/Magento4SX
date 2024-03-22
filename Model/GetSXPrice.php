<?php

namespace Altitude\SX\Model;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use SoapVar;

class GetSXPrice implements ObserverInterface
{
    protected $sx;

    protected $request;

    protected $_addressFactory;

    protected $_proxy;

    public function __construct(
        \Altitude\SX\Model\SX $sx,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
    ) {
        $this->sx = $sx;
        $this->addressFactory = $addressFactory;
        $this->remoteAddress = $remoteAddress;
        $this->request = $request;
      
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
      
        
        if ($this->sx->botDetector()) {
            return "";
        }
        
        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "checking price... " );
        $moduleName = $this->sx->getModuleName(get_class($this));
        $configs = $this->sx->getConfigValue(['apikey', 'cono', 'sxcustomerid', 'whse', 'onlycheckproduct']);
        extract($configs);

        $url = $this->sx->urlInterface()->getCurrentUrl();
        $ip = $this->remoteAddress->getRemoteAddress();
        $displayText = $observer->getEvent()->getName();
        $controller = $this->request->getControllerName();
        $singleitem = "true";
        $shipto = "";
        $custno = 0;
        $products = $productsCollection = [];

        $debuggingflag = "true";
     //   $debuggingflag = "false";

        if ($this->sx->getSession()->getProdDone()){
            $prodDone = $this->sx->getSession()->getProdDone();
        } else {
            $prodDone = $url . $controller . "|";
            $this->sx->getSession()->setProdDone($prodDone);
        }
        
        if ($this->sx->getSession()->getApidown()) {
            $apidown = $this->sx->getSession()->getApidown();
        } else {
            $apidown = false;
        }
 $apidown = false;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        if ($debuggingflag == "true") {
            error_log(__CLASS__ . "/" . __FUNCTION__ . ": url: " . $url);
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "url: " . $url);
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "ip:: " . $ip);
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "controller:: " . $controller);
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , ":::::::::::::::::::::::::::::::::::: ");
        }
        if ($url=="http:///"){
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "skipping for console...");
            return "";
        }
        try {
            $singleProduct = $observer->getEvent()->getProduct();
            if (is_null($singleProduct)) {
                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Item Collection");
                }

                $productsCollection = $observer->getCollection();
                $singleitem = "false";
            } else {
                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Single Item");
                }
                $products = [];
                $productsCollection[] = $singleProduct;
                $singleitem = "true";
            }
        } catch (exception $e) {
        }

        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
    
        if ($customerSession->isLoggedIn()){// || (strpos($url, '/rest/') !== false)) {
            // Logged In
            if ($debuggingflag == "true") {
                $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , " logged in!");
            }

            $customerData = $customerSession->getCustomer();
            //$customerData = $this->sx->getSession()->getCustomer();
                //ob_start();
                //var_dump($customerData);
                //$resultprice = ob_get_clean();
               // $this->sx->gwLog($resultprice);
            $custno = $customerData['sx_custno'];
            $customer_id=$customerData->getId();
            if ($debuggingflag == "true") {
                $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "cust= " . $custno);
            }

            $shippingAddressId = $customerData['default_shipping'];
            $shippingAddress = $this->addressFactory->create()->load($shippingAddressId);

            if ($shippingAddress->getData('ERPAddressID') != "") {
                $shipto = "";
            }
        } else {
            // Not Logged In
            $custno = $sxcustomerid;
            $customer_id=0;
        }

        if (empty($custno)) {
            $custno = $sxcustomerid;
        }

        if ($debuggingflag == "true") {
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Product retrieved");
        }

        if ($this->sx->df_is_admin()) {
            $admin = true;
        } else {
            $admin = false;
        }

        if ($debuggingflag == "true") {
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "admin = " . $admin);
        }

        $bSkip = '';
        $params = new \ArrayObject();
        
         $thisparam= array(
            'cono' => $cono, 'custno'=>$custno, 'whse'=>$whse,'shipto'=>$shipto,'qty'=>'1','APIKey'=>$apikey
        );
        $params[] = new \SoapVar($thisparam, SOAP_ENC_OBJECT);
        
       /* $params[] = new SoapVar($cono, XSD_STRING, null, null, 'cono');
        $params[] = new SoapVar($custno, XSD_STRING, null, null, 'custno');
        $params[] = new SoapVar($whse, XSD_STRING, null, null, 'whse');
        $params[] = new SoapVar($shipto, XSD_STRING, null, null, 'shipto');
        $params[] = new SoapVar("1", XSD_STRING, null, null, 'qty');
        $params[] = new SoapVar($apikey, XSD_STRING, null, null, 'APIKey');*/

        foreach ($productsCollection as $product) {
            $price = 0;
            $visibility = "";
            $prod = $product->getSku();
            $products[$prod] = $product;

            $price = $product->getPrice();
            
            if ($debuggingflag == "true") {
                $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "product sku: $prod");
                $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "product price: $price");
            }
            if ($controller != "product" && $controller != "block" && strpos($url, 'cart') == false && $controller != "order" && $controller != "order_create") {
                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "controllervar=" . $onlycheckproduct);
                }
                if ($onlycheckproduct == "1"  && strpos($url, 'wishlist') === false  && strpos($url, 'amasty_quickorder') === false  && strpos($url, 'checkout') === false  && strpos($url, 'loginVerification') === false) {
                    if ($debuggingflag == "true") {
                        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "skip price for non-product page! . " . $url);
                    }
                    $bSkip = 'true';
                }
            }
            try{
                if (strpos($url, '/wishlist/index/index') !== false  ){
                    $didthis="|" . $custno . ";" . $product->getSku() . ";" . $url . ";";
                    if (!isset($_SESSION["wldidthis"])){
                        $_SESSION["wldidthis"]=$didthis;
                    } elseif (strpos($_SESSION["wldidthis"], $didthis) !== false)  {
                         $this->sx->jwLog ("already processed: " . $didthis . " // " . $_SESSION["wldidthis"]);
                        return "";
                    }else{
                       $_SESSION["wldidthis"] .= $didthis; 
                    }
                }
            } catch (\Exception $e) {
                   $this->sx->gwLog('Error trace test ::: ' . $e->getMessage());
                   $this->sx->gwLog('Error trace' . $e->getTraceAsString());

            } 
            try{
                
                $productcount=count($productsCollection);
            } catch (\Exception $e) {
                $productcount=1;
            }
            try {
                
                // $this->sx->jwLog("...checking $url for performance");
                // $this->sx->jwLog("...checking count " . $productcount . " for performance");
                if ((strpos($url, '/totals-information') !== false  && $productcount>1) 
                    or (strpos($url, '/carts/mine/shipping-information') !== false  && $productcount>0)
                        or (strpos($url, '/carts/mine/estimate-shipping-methods') !== false  && $productcount>0 )
                            or (strpos($url, 'carts/mine/payment-information') !== false  )
                )////
                { 
                    
                    if ( $customer_id>0){
                        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "...setting $prod price from cart (before): $price");
                        $collection = $objectManager->get('\Magento\Framework\App\ResourceConnection');
                        $conn = $collection->getConnection();
                        $quote_table = $collection->getTableName('quote');
                        $quote_item_table = $collection->getTableName('quote_item');
                        $query = "SELECT q.entity_id, customer_id, q.store_id, i.sku, i.price as price FROM $quote_table q INNER JOIN $quote_item_table i ON i.quote_id=q.entity_id WHERE customer_id=$customer_id AND is_active=1 AND sku='$prod'";
                        $result = $conn->fetchAll($query);
                       /* ob_start();
                        var_dump($result);
                        $result2 = ob_get_clean();
                        $this->sx->jwLog($result2);*/
                        $price=$result[0]["price"];
                        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "...new price: $price");
						$product->setSpecialPrice($price);
                        $product->setPrice($price);
                        $product->setFinalPrice($price);
                        return $price;
                    } else {
                        //return $price;
                    }

                } elseif (strpos($url, '/checkout/cart/') !== false && $controller=='cart') {
                    //\Magento\Checkout\Model\Cart\updateItems();
                    //return "";
                }
            } catch (\Exception $e) {
                   $this->sx->gwLog('Error ::: ' . $e->getMessage());
                   //$this->sx->jwLog('Error trace' . $e->getTraceAsString());

            }        

            if ($debuggingflag == "true") {
                $this->sx->gwLog(__CLASS__ . '/' . __FUNCTION__ . ': ' ,'Product: ' . $prod . ' - Magento Price: ' . $price);
            }

            $currparent = "";
            unset($productparent);

            if (!isset($parentdone)) {
                $parentdone = "|";
            }

            if ($debuggingflag == "true") {
                $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Child check" . $parentdone);
            }
            try {
                $productparent = $objectManager->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable')->getParentIdsByChild($product->getId());
                if (isset($productparent[0])) {
                    $currparent = $productparent[0];
                }
            } catch (Exception $e) {
                if ($debuggingflag == "true") {
                    $this->sx->gwLog('Error ' . $e->getMessage());
                }
            }

            if ($debuggingflag == "true") {
                $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "price controller: " . $controller . " -- singleitem: " . $singleitem . " -- currparent: " . $currparent . " -- isset:" . isset($currparent));
            }
            if ($controller != 'product') {
                try {
                    if ($currparent == "" && $singleitem == "false" && isset($productparent[0])) {
                        if ($debuggingflag == "true") {
                            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Skipping SX price check for parent of collection");
                        }
                        $visibility = "0";
                    } elseif ($singleitem=="true" && 1==2){
                        if ($debuggingflag == "true") {
                            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Skipping SX price check single item");
                        }
                        $visibility = "0";

                    } else {
                        if ($debuggingflag == "true") {
                            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Setting vis by prev run");
                        }
                        if (isset($productparent[0])) {
                            if (strpos($parentdone, "|" . $productparent[0] . "|") !== false) {
                                $visibility = "0";
                                if ($debuggingflag == "true") {
                                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "hiding");
                                }
                            } else {
                                if ($debuggingflag == "true") {
                                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "not hiding");
                                }
                                $visibility = "4";
                            }
                        } else {
                            $visibility = "4";
                        }
                    }
                } catch (Exception $e) {
                    if ($debuggingflag == "true") {
                        $this->sx->gwLog('Error ' . $e->getMessage());
                    }
                    $visibility = "4";
                }
            }

            try {
                if ($singleitem == "false") {
                    if ($controller == 'product') {
                        if (isset($productparent[0])) {
                            if ($debuggingflag == "true") {
                                $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "skipping " . $prod);
                            }
                            $visibility = "0";
                        } else {
                            if ($debuggingflag == "true") {
                                $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "checking  " . $prod);
                            }
                            $visibility = "4";
                        }
                    } else {
                    }
                }
            } catch (Exception $e) {
                if ($debuggingflag == "true") {
                    $this->sx->gwLog('Error ' . $e->getMessage());
                }
            }

            if (strpos($url, 'cart') !== false) {
                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "checking for cart  " . $prod);
                }
                $visibility = "4";
            }

		//	 $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Checking url for cart: " . $url);

                 if (strpos($url, 'checkout') === false && strpos($url, 'cart') === false && (strpos($url, 'wishlist') === false) ) { //cart
                 }else {
                     $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Checkout, checking price");
                     $controller="cart";
                     $bSkip = 'false';
                 }

            $this->sx->getSession()->setApidown(false);
            $apidown = $this->sx->getSession()->getApidown();
            $pagestate =  $objectManager->get('Magento\Framework\App\State');

            if (strpos($url, 'admin') !== false || strpos($url, '/catalog/product/index/key/') !== false || $admin == true || strpos($url, '/product/index/key') !== false || $pagestate->getAreaCode()=='adminhtml' ) { //https://nee2go.com/cstore/rest/cstore/V1/carts/mine/totals-information
                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Skipping SX price check for admin");
                }

                return "";
                ///checkout/cart/
            } elseif ((strpos($url, 'checkout/cartxxx/') !== false) && $controller=='cart'  ) {
                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Skipping SX price check for possibly unneeded page " . $url);
                }
                    return "";
            } elseif (strpos($url, 'customer/section/loadx') !== false || strpos($url, 'cartquickpro/cart/configure') || strpos($url, 'cartquickpro/cart/addx') !== false || strpos($url, 'cartquickpro/sidebar/removeItem') !== false || strpos($url, '/wishlist/index/add') !== false || strpos($url, 'cartquickpro/cart/delete') !== false  || strpos($url, '/multiwishlist') !== false   || strpos($url, 'mine/totals-informationxx') !== false || strpos($url, '/carts/mine/totals-informationx') !== false  ) { 
                
           // } elseif (strpos($url, 'customer/section/load') !== false || strpos($url, 'cartquickpro/cart/add') !== false || strpos($url, 'cartquickpro/sidebar/removeItem') !== false || strpos($url, '/wishlist/index/add') !== false || strpos($url, 'cartquickpro/cart/delete') !== false  || strpos($url, '/multiwishlist') !== false   || strpos($url, 'mine/totals-informationxx') !== false || strpos($url, '/carts/mine/totals-informationx') !== false  ) { 
                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Skipping SX price check for unneeded page " . $url);
                }
                    return "";
            } elseif ($apidown == true || $bSkip == 'true') {
                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Skipping SX price check for apidown or non-product page" . ($apidown));
                }

                return "";
            } elseif ($visibility != "" && $visibility != "4" && $singleitem == "false") {
                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Skipping SX price check for invis");
                }

                return "";
            } elseif ($visibility != "" && $visibility != "4" && $singleitem == "true") {
                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Skipping SX price check for invis");
                }

                return "";
            } elseif ($controller == "product" && $singleitem == "false") {
                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Skipping SX price check for prod...");
                }

                return "";
            } elseif ($currparent !== "" && $controller !== "cart" && $singleitem == "false" && strpos($url, 'amasty_quickorder') === false) {
                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Skipping SX price check for child item");
                }

                return "";
            } elseif  ((strpos($prodDone, "|" . $prod . "xxx|") !== false) && (strpos($url, 'checkout') === false && strpos($url, 'cart') === false )  ) {
                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Skipping SX price check for already done item");
                }

                return "";
            }elseif (strpos($url, '/wishlist/index/index') === false && strpos($url, '/wishlist') !== false) {
                 if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Skipping SX price check for wishlist parent page " .$url);
                }

                return "";               

            } else {
                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Price check continues...");
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "launching api");
                }
            } 
             $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "adding prod: " . $prod . " to SCPL  ----  " . $url);
             $this->sx->getSession()->setProdDone($prodDone . $prod .  "|" );
             
    //$this->sx->getSession()->setApidown(true);
                
            $productParams = new \ArrayObject();
            $productParams[] = new \SoapVar(array('product' => $prod), SOAP_ENC_OBJECT);
           // $productParams[] = new SoapVar($prod, XSD_STRING, null, null, 'product');
           //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "GetSXPrice: Setting up SalesCustomerPricingList " . $url);
            $thisparamLines= array(   'SalesCustomerPricingListProductRequestContainer' => $productParams->getArrayCopy()   );
            $params->append(new SoapVar(
                $thisparamLines,
                SOAP_ENC_OBJECT,
                null,
                null,
                'SalesCustomerPricingListProductRequestContainer'
            ));
        }
    if (strpos($this->sx->getConfigValue('apiurl'),'csd') !==false  ) {
        
        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "GetSXPrice: Calling SalesCustomerPricingSelect " . $url);
        $dTime=$this->sx->LogAPITime("SalesCustomerPricingSelect","request", $moduleName,"" ); //request/result // //request/result
        $gcnl=SalesCustomerPricingSelect($cono, $custno,$this->getConfigValue('operinit'),'',$whse, $qty, $prod);
        $this->sx->LogAPITime("SalesCustomerPricingSelect","result", $moduleName,$dTime );
        $gcnl = json_decode(json_encode($result), true);
    
     } else {

        try {
            if ($currparent . "" != "") {
                $parentdone .= "|" . $currparent . "|";
            }
            if ($debuggingflag == "true") {
                $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "selprod=" . $prod);
            }

            $apiname = "SalesCustomerPricingList";
            $client = $this->sx->createSoapClient($apikey, $apiname);
         
            $rootparams = (object) [];
            $rootparams->SalesCustomerPricingListRequestContainer = $params->getArrayCopy();

           $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "GetSXPrice: Calling SalesCustomerPricingList " . $url);
            $dTime=$this->sx->LogAPITime($apiname,"request", $moduleName,"" ); //request/result // //request/result
            $result = $client->SalesCustomerPricingListRequest($rootparams);
            $this->sx->LogAPITime($apiname,"result", $moduleName,$dTime,$this->sx->get_string_between($client->__getLastResponse(),"<requestId>","</requestId>") );
            $gcnl = json_decode(json_encode($result), true);
           // $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . json_encode($result));

        } catch (Exception $e) {
            $this->sx->gwLog('Error ' . $e->getMessage());
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "REQUEST:\n" . htmlentities($client->__getLastRequest()) . "");
        }
}
        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Processing data...");

        $newprice = 0;

        try {
            if (!isset($gcnl)) {
                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "error from pricing: apidown");
                }

                $this->sx->getSession()->setApidown(true);
                $apidown = $this->sx->getSession()->getApidown();
            }

            if (isset($gcnl["fault"])) {
                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "error from pricing: " . $gcnl["fault"]);
                }

                $this->sx->getSession()->setApidown(true);
                $apidown = $this->sx->getSession()->getApidown();

                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "API error: " . $gcnl["fault"]);
                }
            }
        } catch (\Exception $e) {
            $this->sx->gwLog($e->getMessage());
        }

        try {
            $listprice = null;

            if (isset($gcnl["SalesCustomerPricingListResponseContainerItems"])) {
                $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "mult item");

                foreach ($gcnl["SalesCustomerPricingListResponseContainerItems"] as $_gcnl) {
                    if ($_gcnl["product"] != "") {
                        $product = $products[$_gcnl["product"]];
                        $price = $product->getPrice();
                        $prod = $product->getSku();

                        if (isset($_gcnl["price"])) {
                            $price = $_gcnl["price"];
                            if (!empty($_gcnl["pround"])){
                                switch($_gcnl["pround"])
                                {
                                    case 'u';
                                        $price=\ceil($price);
                                        break;
                                    case 'd';
                                        $price=\floor($price);
                                        break;
                                    case 'n';
                                        $price=\round($price);
                                        break;
                                    default;
                                        break;
                                }
                            } //end pround check
                        }
                        if (isset($_gcnl["listprice"])) {
                            $listprice = $_gcnl["listprice"];
                        }

                        if ($price>0) {
                            $product->setSpecialPrice($price);
                            $product->setPrice($price);
                            $product->setFinalPrice($price);
                        } elseif ($listprice > 0) {
                            $product->setPrice($listprice);
                            $product->setFinalPrice($price);
                            $product->setSpecialPrice($price);
                        } else {
                            $price=$product->getPrice();
                        }

                        $message = $prod . " Before: " . $price . " After: " . $product->getData('final_price');
                        $this->sx->gwLog(__CLASS__ . '/' . __FUNCTION__ . ': ' ,$message);
                    }
                }
            } elseif (isset($gcnl["product"])) {
                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "single item " . $gcnl["product"]);
                }
                try {
                    $product = $products[$gcnl["product"]];
                    $price = $product->getPrice();
                    $prod = $product->getSku();

                    if (isset($gcnl["price"])) {
                        $price = $gcnl["price"];
                        if (!empty($gcnl["pround"])){
                                switch($gcnl["pround"])
                                {
                                    case 'u';
                                        $price=\ceil($price);
                                        break;
                                    case 'd';
                                        $price=\floor($price);
                                        break;
                                    case 'n';
                                        $price=\round($price);
                                        break;
                                    default;
                                        break;
                                }
                            } //end pround check
                         
                    }
                    if (isset($gcnl["listprice"])) {
                        $listprice = $gcnl["listprice"];
                    }

                    $product->setPrice($price);
                    $product->setFinalPrice($price);

                    if ($listprice > 0) {
                        $product->setPrice($price);
                        $product->setFinalPrice($price);
                        $product->setSpecialPrice($price);
                    } elseif ($price>0)  {
                        $product->setSpecialPrice($price);
                        $product->setPrice($price);
                        $product->setFinalPrice($price);
                    } else {
                        $price=$product->getPrice();
                    }
					/*****/
                    if (strpos($url, '/checkout/cart/') !== false && $controller=='cart'  ) {
                        //\Magento\Checkout\Model\Cart\updateItems();
                        //return "";
                        $collection = $objectManager->get('\Magento\Framework\App\ResourceConnection');
                        $conn = $collection->getConnection();
                        $quote_table = $collection->getTableName('quote');
                        $quote_item_table = $collection->getTableName('quote_item');
                        $query = "SELECT q.entity_id, customer_id, q.store_id, i.item_id, i.sku, i.price, i.product_id as price FROM $quote_table q INNER JOIN $quote_item_table i ON i.quote_id=q.entity_id WHERE customer_id=$customer_id AND is_active=1 AND sku='$prod'";
                        $result = $conn->fetchAll($query);
                        $QuoteId= $result[0]["entity_id"];
                        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "updating " . $gcnl["product"] . " price in cart1");
                        $quote=$objectManager->get('\Magento\Quote\Api\CartRepositoryInterface')->getActive($QuoteId);
                        $quoteItem = $quote->getItemById($result[0]["item_id"]);
                        $quoteItem->setPrice($price);
                        $quoteItem->setBasePrice($price);
                        $quoteItem->setCustomPrice($price);
                        $quoteItem->setOriginalCustomPrice($price);
                        /* maybe this is a solution??*/
                        $rowtotal=$price * $quoteItem->getQty();
                        $quoteItem->setCustomRowTotalPrice( $rowtotal);
                        $quoteItem->setRowTotal( $rowtotal);
                        $quoteItem->setBaseRowTotal( $rowtotal);
                        $quoteItem->save();
                        //$query="UPDATE quote_item set row_total=$rowtotal, base_row_total=$rowtotal where quote_id=$QuoteId and item_id=" . $result[0]['item_id'] . "";
                       // $this->sx->gwLog($query);
                        //$quote->updateItem($result[0]["item_id"],null,null);
                       // $quote->collectTotals();
                       // $quote->save($quote);
                       // $quote->setTotalsCollectedFlag(false)->collectTotals()->save();
                        $conn->closeConnection();
                    }
                    /******/
                    $message = $prod . " !!Before: " . $price . " After: " . $product->getData('final_price');
                    if ($debuggingflag == "true") {
                        $this->sx->gwLog($message);
                    }
                } catch (\Exception $e1) {
                }
            } else {
                if ($debuggingflag == "true") {
                    $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "not set");
                }
            }
        } catch (Exception $e) {
            $this->sx->gwLog($e->getMessage());
            $this->sx->gwLog('Error ' . $e->getMessage());
        }

        return $price;
    }
}
