<?php
namespace Meetanshi\ShippingRestrictions\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('meetanshi_shippingrestrict_rules'))
            ->addColumn(
                'rule_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn(
                'is_active',
                Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false, 'unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'error_message',
                Table::TYPE_TEXT,
                255,
                ['default' => null, 'nullable' => true]
            )
            ->addColumn(
                'name',
                Table::TYPE_TEXT,
                255,
                ['default' => null, 'nullable' => false]
            )
            ->addColumn(
                'days',
                Table::TYPE_TEXT,
                255,
                ['default' => '', 'nullable' => false]
            )
            ->addColumn(
                'stores',
                Table::TYPE_TEXT,
                255,
                ['default' => '', 'nullable' => false]
            )
            ->addColumn(
                'customer_groups',
                Table::TYPE_TEXT,
                255,
                ['default' => '', 'nullable' => false]
            )
            ->addColumn(
                'shipping_carriers',
                Table::TYPE_TEXT,
                null,
                ['default' => null, 'nullable' => true]
            )
            ->addColumn(
                'shipping_methods',
                Table::TYPE_TEXT,
                null,
                ['default' => null, 'nullable' => true]
            )
            ->addColumn(
                'conditions_serialized',
                Table::TYPE_TEXT,
                null,
                ['default' => null, 'nullable' => true]
            )
            ->addColumn(
                'actions_serialized',
                Table::TYPE_TEXT,
                null,
                ['default' => null, 'nullable' => true]
            )
            ->addColumn(
                'from_time',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => true, 'default' => null]
            )
            ->addColumn(
                'to_time',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => true, 'default' => null]
            )
            ->addColumn(
                'is_admin',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false]
            );

        $installer->getConnection()->createTable($table);

        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('meetanshi_shippingrestrict_attributes'))
            ->addColumn(
                'attr_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn(
                'rule_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'code',
                Table::TYPE_TEXT,
                255,
                ['default' => null, 'nullable' => true]
            )
            ->addIndex('rule_id', 'rule_id')
            ->addForeignKey(
                $installer->getFkName(
                    'meetanshi_shippingrestrict_attributes',
                    'rule_id',
                    'meetanshi_shippingrestrict_rules',
                    'rule_id'
                ),
                'rule_id',
                $installer->getTable('meetanshi_shippingrestrict_rules'),
                'rule_id',
                Table::ACTION_CASCADE
            );

        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
