<?php
namespace Julio\Order\Setup;
use Julio\Order\Model\ResourceModel\Export as ExportResource;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
class InstallSchema implements InstallSchemaInterface {
	/**
	 * {@inheritdoc}
	 */
	function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
	{
		$connection = $setup->getConnection();
		$setup->startSetup();
		$table = $connection->newTable($setup->getTable(ExportResource::TABLE))
			->addColumn(
				'entity_id',
				\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
				null,
				['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
				'Config Id'
			)->addColumn(
				'order_id',
				\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
				null,
				['unsigned' => true, 'nullable' => false,],
				'Order Reference'
			)->addColumn(
				'queued_at',
				\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
				null,
				['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
				'Queued At'
			)->addColumn(
				'synced_at',
				\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
				null,
				['nullable' => true, 'default' => null],
				'Synced At'
			)->addColumn(
				'synced',
				\Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
				2,
				['nullable' => false, 'default' => 0],
				'Is Synchronized?'
			)->addColumn(
				'last_error',
				\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
				'1k',
				[],
				'Last Sync Error'
			)->addForeignKey(
				$connection->getForeignKeyName(ExportResource::TABLE, 'order_id', 'sales_order', 'entity_id'),
				'order_id',
				'sales_order',
				'entity_id',
				\Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
			)->setComment(
				'Sales Order Export Queue'
			);
		$connection->createTable($table);

		$connection->addColumn(
			$setup->getTable('sales_order_grid'),
			'export_synced',
			[
				'type' => Table::TYPE_SMALLINT,
				'length' => 2,
				'comment' => 'Export Synced',
				'default' => 0,
				'nullable' => false
			]
		);

		$setup->endSetup();
	}
}
