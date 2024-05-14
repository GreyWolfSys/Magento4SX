<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Altitude\SX\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;


/**
 * Creates all required table and keys for Signifyd case
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @var string
     */
    private static $table = '';

    /**
     * @var string
     */
    private static $connectionName = 'sales';

    /**
     * @inheritdoc
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var AdapterInterface $connection */
        $connection = $setup->startSetup()->getConnection(self::$connectionName);

        $connection->addColumn(
            $setup->getTable('sales_order_grid'),
            'ext_order_id',
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'comment' => 'External Order ID'
            ]
        );
        if ($connection->tableColumnExists('sales_order', 'CC_AuthNo') === false) {
            $connection
                ->addColumn(
                    $setup->getTable('sales_order'),
                    'CC_AuthNo',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 0,
                        'comment' => 'CC Auth No'
                    ]
                );
        }
        if ($connection->tableColumnExists('sales_order', 'SX_OrderNo') === false) {
            $connection
                ->addColumn(
                    $setup->getTable('sales_order'),
                    'SX_OrderNo',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 0,
                        'comment' => 'SX Order No'
                    ]
                );
        }
        if ($connection->tableColumnExists('sales_order', 'SX_OrderSuf') === false) {
            $connection
                ->addColumn(
                    $setup->getTable('sales_order'),
                    'SX_OrderSuf',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 0,
                        'comment' => 'SX Order Suf'
                    ]
                );
        }
        if ($connection->tableColumnExists('sales_order', 'order_instructions') === false) {
            $connection
                ->addColumn(
                    $setup->getTable('sales_order'),
                    'order_instructions',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 0,
                        'comment' => 'Order Instructions'
                    ]
                );
        }
        $connection
        ->addColumn(
            $setup->getTable('sales_order_grid'),
            'order_instructions',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 0,
                'comment' => 'Order Instructions'
            ]
        );
        $connection
            ->addColumn(
                $setup->getTable('quote'),
                'order_instructions',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 0,
                    'comment' => 'Order Instructions'
                ]
            );
       $setup->endSetup();





    }
}
