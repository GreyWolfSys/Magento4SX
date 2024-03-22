<?php

namespace Altitude\SX\Block;

class Invoicedetail extends OrderQuery
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

    public function invoiceDetail()
    {
        $moduleName = $this->sx->getModuleName(get_class($this));
        $customer = $this->sx->getSession()->getCustomer();
        $data = $this->getRequest()->getParams();
        $configs = $this->sx->getConfigValue(['cono', 'sxcustomerid', 'invstartdate', 'simplifyinvoice', 'maxrecall', 'maxrecalluid', 'maxrecallpwd']);
        extract($configs);
        $podPdf = null;

        if (isset($data["invoice"]) && isset($data["invoicesuf"])) {
            $invtodetail = $data["invoice"] . $data["invoicesuf"];
            $invorderno = $data["invoice"];
            $invordersuf = $data["invoicesuf"];
        }

        try {
            if (isset($data['pod']) && $data['pod'] == '1') {
                $map_url = $maxrecall . "/Viewer/RetrieveDocument/D1097/[{'KeyID':'119','UserValue':'" . $invorderno . "'}]";
                $result1 = $this->sx->makeRESTRequest($map_url, "", $maxrecalluid, $maxrecallpwd);
                $podPdf = str_replace("<object ", "<object style='min-height:750px;' ", $result1);
            } elseif (isset($data['pdf']) && $data['pdf'] == '1') {
                $map_url = $maxrecall . "/Viewer/RetrieveDocument/D140/[{'KeyID':'119','UserValue':'" . $invorderno . "'}]";
                $result1 = $this->sx->makeRESTRequest($map_url, "", $maxrecalluid, $maxrecallpwd);
                $podPdf = str_replace("<object ", "<object style='min-height:750px;' ", $result1);
            }
            $pay = isset($data["pay"]) ? $data["pay"] : "";
            $paycart = isset($data["paycart"]) ? $data["paycart"] : "";
            if ($podPdf != null) {
                return ['podPdf' => $podPdf];
            } else {
                $total = 0;
                $moduleName = $this->sx->getModuleName(get_class($this));
                $customer = $this->sx->getSession()->getCustomer();
                $data = $this->getRequest()->getParams();
                $configs = $this->sx->getConfigValue(['cono', 'sxcustomerid', 'invstartdate']);
                extract($configs);

                if ($customer['sx_custno'] > 0) {
                    $sxCustNo = $customer['sx_custno'];
                } else {
                    $sxCustNo = $sxcustomerid;
                }

                $invoicesList = $this->sx->SalesCustomerInvoiceList($cono, $sxCustNo, $moduleName);
                $invoice = null;

                if (isset($invoicesList["SalesCustomerInvoiceListResponseContainerItems"])) {
                    foreach ($invoicesList["SalesCustomerInvoiceListResponseContainerItems"] as $_item) {
                        $invoice = $_item;

                        if ($_item["invno"] . $_item["invsuf"] == $invtodetail) {
                            $Order = $this->sx->SalesOrderSelect($cono, $invoice["invno"], $invoice["invsuf"], $moduleName);
                            $sxCustomer = $this->sx->SalesCustomerSelect($cono, $invoice["custno"], $moduleName);
                            $Package = $this->sx->SalesPackagesSelect($cono, $invoice["invno"], $invoice["invsuf"], $moduleName);

                            if (!isset($Order["stagedesc"])) {
                                foreach ($Order["SalesOrderSelectResponseContainerItems"] as $_order) {
                                    if ($_order["orderno"] == $invoice["invno"] && $_order["ordersuf"] == $invoice["invsuf"]) {
                                        $Order = $_order;
                                        break;
                                    }
                                }
                            }
                            break;
                        }
                    }
                }

                $orderDetail = $this->sx->SalesOrderLinesSelect($cono, $Order["orderno"], $Order["ordersuf"], $moduleName);

                if (isset($Order["custpo"])) {
                    $custpo = $Order["custpo"];
                } else {
                    $custpo = "";
                }


                return [
                    'invoice' => $invoice,
                    'orderDetail' => $orderDetail,
                    'orderhead' => $Order,
                    'customer' => $sxCustNo,
                    'sxCustomer' => $sxCustomer,
                    'Order' => $Order,
                    'custpo' => $custpo,
                    'simplifyinvoice' => $simplifyinvoice,
                    'podPdf' => $podPdf
                ];
            }

        } catch (\Exception $eheader) {
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "GWS Error: " . $eheader->getMessage());
        }

        return [];
    }
}
