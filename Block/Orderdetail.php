<?php

namespace Altitude\SX\Block;

class Orderdetail extends OrderQuery
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

    public function orderDetail()
    {
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

        try {
            $total = 0;
            $moduleName = $this->sx->getModuleName(get_class($this));
            $customer = $this->sx->getSession()->getCustomer();
            $data = $this->getRequest()->getParams();
            $configs = $this->sx->getConfigValue(['cono', 'sxcustomerid', 'invstartdate']);
            extract($configs);

            $Order = $this->sx->SalesOrderSelect($cono, $invorderno, $invordersuf, $moduleName);
            $OrderItem=$Order;
            if (isset($Order)){
                if (isset($Order["custno"])){
                    $OrderItem=$Order;
                } else {
                   
                    $OrderItem=$Order["SalesOrderSelectResponseContainerItems"][0];
                }
            } else {
                
            }
            $sxCustomer = $this->sx->SalesCustomerSelect($cono, $OrderItem["custno"], $moduleName);
            $package = $this->sx->SalesPackagesSelect($cono, $invorderno, $invordersuf, $moduleName);
            $addon = $this->sx->SalesOrderAddonsSelect($cono, $OrderItem["custno"],"", $invorderno, $invordersuf, $moduleName);

            if (isset($OrderItem["custpo"])) {
                $custpo = $OrderItem["custpo"];
            } else {
                $custpo = "";
            }

            if ($customer['sx_custno'] > 0) {
                $sxCustNo = $customer['sx_custno'];
            } else {
                $sxCustNo = $sxcustomerid;
            }

            $orderDetail = $this->sx->SalesOrderLinesSelect($cono, $OrderItem["orderno"], $OrderItem["ordersuf"], $moduleName);
           
            return [
                'orderhead' => $OrderItem,
                'orderDetail' => $orderDetail,
                'customer' => $sxCustNo,
                'sxCustomer' => $sxCustomer,
                'Order' => $OrderItem,
                'custpo' => $custpo,
                'package' => $package,
                'addon' => $addon
            ];

        } catch (\Exception $eheader) {
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "GWS order detail Error: " . $eheader->getMessage());
        }
    }
}
