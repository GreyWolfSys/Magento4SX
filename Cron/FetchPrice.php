<?php
declare(strict_types=1);

namespace Altitude\SX\Cron;

class FetchPrice extends \Magento\Framework\View\Element\Template
{

    protected $logger;

    /*Product collection variable*/
    protected $_productCollection;
    protected $stockFilter;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(\Psr\Log\LoggerInterface $logger,
        \Altitude\SX\Model\SX $sx,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
        \Magento\CatalogInventory\Helper\Stock $stockFilter,
        array $data = []
  )
    {
        $this->logger = $logger;
        $this->sx = $sx;
        $this->_productCollection= $productCollection;
        $this->stockFilter = $stockFilter;
        parent::__construct($context, $data);
    }

    public function getProductCollection()
        {

            $collection = $this->_productCollection->create();
            $collection->addAttributeToSelect('*');
            $collection->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

            // ADD THIS CODE IF YOU WANT IN-STOCK-PRODUCT
            $this->stockFilter->addInStockFilterToCollection($collection);

            return $collection;
        }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Cronjob FetchPrice is executing.");
         //$configs = $this->sx->getConfigValue(['apikey', 'cono', 'sxcustomerid', 'whse', 'listorbase']);
        $configs = $this->sx->getConfigValue(['apikey', 'cono', 'sxcustomerid', 'whse', 'listorbase']);
        extract($configs);
        //  // $this->sx->gwLog( $listorbase);
        if (!empty($listorbase)){
         $productCollection = $this->getProductCollection();
           $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "got products");
            foreach ($productCollection as $product) {
               // // $this->sx->gwLog($product->getData());
                // $this->sx->gwLog( $product->getId());
                // $this->sx->gwLog( $product->getName());
                $sku= $product->getSku();

                $this->sx->gwLog( "sku=" . $sku);
                $this->sx->gwLog( "cono=" . $cono);
                $this->sx->gwLog( "whse=" . $whse);
                $this->sx->gwLog( "sxcustomerid=" . $sxcustomerid);
                try {

                    //    public function SalesCustomerPricingSelect($cono, $prod, $whse, $custno, $shipto, $qty, $moduleName = "")
                    $gcnl = $this->sx->SalesCustomerPricingSelect($cono, $sku, $whse, $sxcustomerid, '', '1', 'PriceCache');
                    if (!isset($gcnl) || isset($gcnl["fault"])) {
                         $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "error from pricing");
                        $this->sx->getSession()->setApidown(true);
                    } elseif (!empty($gcnl[$listorbase]) && false) {
                        $price = $gcnl[$listorbase];
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
                         $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "price=:=" . $price);
                        $product->setPrice($price);
                        $product->setFinalPrice($price);
                        if ($price > 0) {
                            //$product->setSpecialPrice($price);
                        } else {
                            //$product->setSpecialPrice(null);
                        }
                        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "saving");
                        $product->save();
                    } else{
                        $price = $gcnl["price"];
                        $qtybrkfl= $gcnl["qtybrkfl"] . "";
                        if (empty($qtybrkfl)){
                            $qtybrkfl='N';
                        }
                        if (!empty($qtybrkfl)){
                            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "setting qtybrkfl to " . $qtybrkfl);
                            $product->setData("qtybrkfl", $qtybrkfl);
                        }
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
                         $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "price=:==" . $price);
                        $product->setPrice($price);
                        $product->setFinalPrice($price);
                        if ($price > 0) {
                            //$product->setSpecialPrice($price);
                        } else {
                            //$product->setSpecialPrice(null);
                        }
                        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "saving");
                        $product->save();

                    }
                } catch (\Exception $e1) {
                    $this->sx->gwLog($e1->getMessage());
                }
            }
        }
            try{
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $_cacheTypeList = $objectManager->create('Magento\Framework\App\Cache\TypeListInterface');
                $_cacheFrontendPool = $objectManager->create('Magento\Framework\App\Cache\Frontend\Pool');
                $types = array('config','layout','block_html','collections','reflection','db_ddl','eav','config_integration','config_integration_api','full_page','translate','config_webservice');
                foreach ($types as $type) {
                    $_cacheTypeList->cleanType($type);
                }
                foreach ($_cacheFrontendPool as $cacheFrontend) {
                    $cacheFrontend->getBackend()->clean();
                }
            } catch (\Exception $e1) {
                $this->sx->gwLog($e1->getMessage());
            }
          $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Cronjob FetchPrice is complete.");

    }
}

