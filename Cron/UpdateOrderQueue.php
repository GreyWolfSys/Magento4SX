<?php

namespace Altitude\SX\Cron;

use Magento\Sales\Api\Data\OrderInterface;

class UpdateOrderQueue
{
    protected $sx;

    protected $order;

    protected $_objectManager;

    protected $resourceConnection;

    public function __construct(
        \Altitude\SX\Model\SX $sx,
        OrderInterface $order,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->sx = $sx;
        $this->order = $order;

        $this->scopeConfig = $scopeConfig;
        $this->invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->invoiceService = $invoiceService;
        $this->shipmentFactory = $shipmentFactory;
        $this->transactionFactory = $transactionFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
       * Write to system.log
       *
       * @return void
       */
    public function execute()
    {
        $moduleName = $this->sx->getModuleName(get_class($this));
        $autoinvoice = $this->sx->getConfigValue('autoinvoice');

        if ($autoinvoice == 1) { //if autoinvoice
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "automatically invoicing: " . $autoinvoice);
            try {
                // $this->sx->gwLog(__CLASS__ . '/' . __FUNCTION__ . ': ' , 'Checking for uninvoiced orders');
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $ordercollection = $objectManager->get('Magento\Sales\Model\Order')->getCollection();
                $ordercollection->setOrder('created_at', 'desc')
                    ->setPageSize(50)
                    ->setCurPage(1);

                foreach ($ordercollection as $order) {
                    //  $this->sx->gwLog(__CLASS__ . '/' . __FUNCTION__ . ': ' , 'Order=' . $order->getIncrementId());
                    if ($order->canInvoice()) {
                        $this->sx->gwLog(__CLASS__ . '/' . __FUNCTION__ . ': ' , 'Order"  . $order->getIncrementId() . " invoicing  now');
                        //CreateInvoice($order, $this);

                        try {
                            $invoices = $this->invoiceCollectionFactory->create()->addAttributeToFilter('order_id', ['eq' => $order->getId()]);
                            //$this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "order " . $order->getId());
                            $invoices->getSelect()->limit(1);

                            if ((int)$invoices->count() !== 0) {
                                //return null;
                            }

                            $invoice = $this->invoiceService->prepareInvoice($order);
                            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                            $invoice->register();
                            $invoice->getOrder()->setCustomerNoteNotify(false);
                            $invoice->getOrder()->setIsInProcess(true);
                            $order->addStatusHistoryComment('Automatically INVOICED', false);
                            $transactionSave = $this->transactionFactory->create()
                                ->addObject($invoice)
                                ->addObject($invoice->getOrder());
                            $transactionSave->save();
                        } catch (\Exception $e) {
                            $this->sx->gwLog(__CLASS__ . '/' . __FUNCTION__ . ': ' , 'Exception message: ' . $e->getMessage());
                            $order->addStatusHistoryComment('Exception message:: ' . $e->getMessage(), false);
                            $order->save();
                            //return null;
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->sx->gwLog(__CLASS__ . '/' . __FUNCTION__ . ': ' , 'Exception message: ' . $e->getMessage());
            }
        }

        $dbConnection = $this->resourceConnection->getConnection();
        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Updating ERP Order Insert Cron");
        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Checking Order Queue");
        $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Opening DB connection");

        $querycheck = 'SELECT 1 FROM `gws_GreyWolfOrderQueue`';
        $query_result = $dbConnection->query($querycheck);
        if ($query_result !== false) {
            // table exists, proceed
        } else {
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Order queue table does not exist");
            exit;
        }

        //check table for orders to process

        $this->sx->gwLog(__CLASS__ . '/' . __FUNCTION__ . ': ' , 'Getting gws_GreyWolfOrderQueue results');
        $sql = "SELECT *  FROM `gws_GreyWolfOrderQueue` WHERE `dateprocessed` is null ";

        try {
            $result = $dbConnection->fetchAll($sql);
        } catch (\Exception $e) {
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "ERROR getting data: " . $e->getMessage());
            exit;
        }

        if (count($result)) {
            
            $this->sx->gwLog(count($result) . ' gws_GreyWolfOrderQueue Records found');
            // output data of each row
            foreach ($result as $row) {
                //submit orders
                 $this->sx->gwLog(__CLASS__ . '/' . __FUNCTION__ . ': ' , 'Processing order ' . $row["orderid"]);
                $order1 = $this->order->loadByIncrementId($row["orderid"]);// order->loadByIncrementId
                
                if ($order1->hasInvoices()) {
                    $invIncrementId = [];
                    foreach ($order1->getInvoiceCollection() as $invoice) {
                        //$invoiceIncId[] = $invoice->getIncrementId();
                        $this->sx->gwLog(__CLASS__ . '/' . __FUNCTION__ . ': ' , 'Submitting Order Increment (i): ' . $row["orderid"]);
                        \Magento\Framework\Profiler::start("Altitude-SubmitOrder-queue1" );
                        if ($this->sx->SubmitOrder($order1, $moduleName) == true) {
                            //update queue table to not check future orders
                            $this->sx->UpdateOrderQueue($row["orderid"], $moduleName);
                            \Magento\Framework\Profiler::stop("Altitude-SubmitOrder-queue1" );
                            break;
                        }
                        \Magento\Framework\Profiler::stop("Altitude-SubmitOrder-queue1" );
                    }
                } else {
                    $this->sx->gwLog(__CLASS__ . '/' . __FUNCTION__ . ': ' , 'Submitting Order Increment (o): ' . $row["orderid"]);
                        \Magento\Framework\Profiler::start("Altitude-SubmitOrder-queue2" );
                    if ($this->sx->SubmitOrder($order1, $moduleName) == true) {
                            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Order Created");
                            $this->sx->UpdateOrderQueue($row["orderid"], $moduleName);
                            \Magento\Framework\Profiler::stop("Altitude-SubmitOrder-queue2" );
                            break;
                    }
                    \Magento\Framework\Profiler::stop("Altitude-SubmitOrder-queue2" );
                }
            }
        } else {
            $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "0 results");
        }
    }
}
