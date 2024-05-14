<?php

namespace Altitude\SX\Block;

class SXBalancesBlock extends OrderQuery
{
    protected $sx;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Altitude\SX\Model\SX $sx,
        array $data = []
        ) {
        $this->_context = $context;
        $this->sx = $sx;
        parent::__construct($context, $data);
    }

    public function getBalances()
    {
        $moduleName = $this->sx->getModuleName(get_class($this));
        $customer = $this->sx->getSession()->getCustomer();
        $sxCustNo = $customer->getData('sx_custno');

        $data = $this->getRequest()->getParams();
        $configs = $this->sx->getConfigValue(['cono', 'sxcustomerid']);
        extract($configs);

  
        
        $sxCustomer = $this->sx->SalesCustomerSelect($cono, $sxCustNo, $moduleName);

        if (isset($sxCustomer["errordesc"]) && $sxCustomer["errordesc"] != "") {
            $shipToList = null;
        } else {
            $shipToList = $this->sx->SalesShipToList($cono, $sxCustNo, $moduleName);
        }

        if (isset($data["shipto"]) && $data["shipto"] != "") {
            $shipTo = $this->sx->SalesShipToSelect($cono, $sxCustNo, $data["shipto"], $moduleName);
            $selectedShipTo = $data["shipto"];
        } else {
            $shipTo = null;
            $selectedShipTo = null;
        }

        $balances = [
            'lastsaleamt' => ['label' => 'Last Sale Amount'],
            'periodbal1' => ['label' => "perioddt1", 'is_index' => true],
            'periodbal2' => ['label' => "perioddt2", 'is_index' => true],
            'periodbal3' => ['label' => "perioddt3", 'is_index' => true],
            'periodbal4' => ['label' => "perioddt4", 'is_index' => true],
            'periodbal5' => ['label' => "perioddt5", 'is_index' => true],
            'futinvbal' => ['label' => 'Future Invoice Balance'],
            'codbal' => ['label' => 'COD Balance'],
            'ordbal' => ['label' => 'Unapplied Credit', 'is_minus' => true],
            'uncashbal' => ['label' => 'Unapplied Cash', 'is_minus' => true],
            'servchgbal' => ['label' => 'Service Charge Balance'],
        ];

        return [
            'sxCustomer' => $sxCustomer,
            'shipToList' => $shipToList,
            'shipTo' => $shipTo,
            'selectedShipTo' => $selectedShipTo,
            'balances' => $balances,
            'sxcustomerid' => $sxcustomerid
        ];
    }
}
