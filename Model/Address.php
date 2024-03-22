<?php

namespace Altitude\SX\Model;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Framework\Indexer\StateInterface;

class Address extends \Magento\Customer\Model\Address
{
    private $sx;

    private $regionFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Directory\Helper\Data $directoryData,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        AddressMetadataInterface $metadataService,
        AddressInterfaceFactory $addressDataFactory,
        RegionInterfaceFactory $regionDataFactory,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Reflection\DataObjectProcessor $dataProcessor,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data,
        \Altitude\SX\Model\SX $sx
    ) {
        $this->dataProcessor = $dataProcessor;
        $this->_customerFactory = $customerFactory;
        $this->indexerRegistry = $indexerRegistry;
        $this->sx = $sx;
        $this->regionFactory = $regionFactory;
		$data=[];
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $directoryData,
            $eavConfig,
            $addressConfig,
            $regionFactory,
            $countryFactory,
            $metadataService,
            $addressDataFactory,
            $regionDataFactory,
            $dataObjectHelper,
            $customerFactory,
            $dataProcessor,
            $indexerRegistry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Processing object before save data
     *
     * @return $this
     */
    public function beforeSave()
    {
              $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Sending address to sx?");
        $moduleName = $this->sx->getModuleName(get_class($this));
        $sendAddressToErp = $this->sx->getConfigValue('defaults/gwcustomer/address_to_erp');
        $configs = $this->sx->getConfigValue(['apikey', 'sxcustomerid']);
        extract($configs);
        if ($sendAddressToErp) {
            $apiname = "SalesShipToBatchInsertUpdate";
            $this->sx->LogAPICall($apiname, $moduleName);

          //  $client = $this->sx->createSoapClient($apikey,$apiname);

            $customer = $this->getCustomer();
            $addressData = $this->getData();

            $custno = $sxcustomerid;
            $shipto = $erpAddress = substr(md5(uniqid(mt_rand(), true)), 0, 8);

            if ($this->getData('ERPAddressID')) {
                $shipto = $this->getData('ERPAddressID');
                $erpAddress = $shipto;
            }

            if ($customer->getData('sx_custno')) {
                $custno = $customer->getData('sx_custno');
            }

            if (!empty($addressData["country_id"])) {
                $countrycode = $addressData["country_id"];
            } else {
                $countrycode = "US";
            }

 $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
 //$_regionFactory = $objectManager->get('Magento\Directory\Model\RegionFactory');
 //$regionModel = $_regionFactory->create();
 //$region = $_regionFactory->loadByCode($addressData["region_id"], $countrycode);

           // if (empty($region->getId())) {
                $region = $objectManager->create('Magento\Directory\Model\Region')->load($addressData["region_id"]); // Region Id

               // $region = $this->regionFactory->loadByCode($addressData["region_id"], $countrycode);
             //   $regionId = $region->getId();
          //  } else {
          //      $regionId = $region->getId();
          //  }
            $regionId = $region->getData()['code'];
            $addressData['state'] = $regionId;
      //   $this->sx->gwLog(__CLASS__ . "/" . __FUNCTION__ . ": " , "Sending address to sx?11" . $regionId);
            try {
                $result = $this->sx->SalesShipToBatchInsertUpdate(
                    $custno,
                    $shipto,
                    $addressData,
                    $customer->getData("email"),
                    $moduleName
                );

                if (isset($result["shipto"]) && $this->getData('ERPAddressID') == "") {
                    $shiptono = $result["shipto"];

                    if ($shiptono != "0" && $shiptono != "") {
                        $this->setData("ERPAddressID", $shiptono);
                    }
                }
            } catch (\Exception $e) {
            }
        }

        parent::beforeSave();
    }
}
