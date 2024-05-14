<?php

namespace Altitude\SX\Block;

class Orders extends OrderQuery
{
    protected $_product = null;

    protected $_registry;

    protected $_productFactory;

    protected $sx;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Checkout\Model\Cart $cart,
        \Altitude\SX\Model\SX $sx,
        array $data = []
        ) {
        $this->_registry = $registry;
        $this->_productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->_context = $context;
        $this->_cart = $cart;
        $this->sx = $sx;
        parent::__construct($context, $data);
    }
    function array_sort_by_column($arr, $col, $dir = SORT_ASC) {
      return $this->sx->array_sort_by_column($arr,$col,$dir);
        
    }
  
    
    public function getOrders()
    {
        $invtodetail = "";
        $podPdf = "";
        $moduleName = $this->sx->getModuleName(get_class($this));
        $customer = $this->sx->getSession()->getCustomer();
        $data = $this->getRequest()->getParams();
        $configs = $this->sx->getConfigValue(['cono', 'sxcustomerid', 'invstartdate']);
        extract($configs);

        if (isset($data["order"]) && isset($data["ordersuf"])) {
            $invtodetail = $data["order"] . $data["ordersuf"];
            $invorderno = $data["order"];
            $invordersuf = $data["ordersuf"];
        }

        #$displaytype = isset($data["ordertype"]) ? $data["ordertype"] : "order";
        $invstartdate = (isset($data["startdate"]) && $data["startdate"] != "") ? $data["startdate"] : $invstartdate;
        $invenddate = (isset($data["enddate"]) && $data["enddate"] != "") ? $data["enddate"] : date("m/d/Y", time());
        $pdf = isset($data["pdf"]) ? $data["pdf"] : "";
        $pod = isset($data["pod"]) ? $data["pdf"] : "";
        $pay = isset($data["pay"]) ? $data["pay"] : "";
        $paycart = isset($data["paycart"]) ? $data["paycart"] : "";
        $custno = ($customer['sx_custno'] > 0) ? $customer['sx_custno'] : $sxcustomerid;

        try {
            $ordertype=""; //"so"
            $shipToList = $this->sx->SalesShipToList($cono, $custno, $moduleName);
            $ordersList = $this->sx->SalesOrderList($cono, $custno, "", $ordertype, "", "", "", "", 0, 0, 0, $invstartdate, $invenddate, "", "", "", "", "", $moduleName);
            $stagedescript = $this->sx->array_sort_by_column($ordersList["SalesOrderListResponseContainerItems"], 'stagedesc');
        } catch (\Exception $e) {
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "GWS Error: " . $e->getMessage());
            $ordersList = false;
        }
        $ownedOrders="|";
        if ($custno==$sxcustomerid){
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $dbConnection = $resource->getConnection();
            $sales_order_table = $resource->getTableName('sales_order');
            $sql = "SELECT ext_order_id FROM $sales_order_table WHERE customer_id=" . $customer->getId() . " AND ext_order_id IS NOT null";
            //error_log("order no query=" . $sql);
            $result = $dbConnection->fetchAll($sql);
            //$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            if (count($result)) {
                foreach ($result as $row) {
                    $ownedOrders .= str_replace("-0","",$row["ext_order_id"] ) . "|";
                }
                //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "order no query result=" . $ownedOrders);
            }
        } else {
            $ownedOrders="";
        }
        return [
            'invstartdate' => $invstartdate,
            'invenddate' => $invenddate,
            'ordersList' => $ordersList,
            'shipToList' => $shipToList,
            'ownedOrders'=>$ownedOrders
        ];
    }
}
