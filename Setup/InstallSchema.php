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
	function install(SchemaSetupInterface $setup, ModuleContextInterface $context) {
		$connection = $setup->getConnection();
		$setup->startSetup();
		$t = $connection->newTable($setup->getTable(ExportResource::TABLE));
		$t->addColumn(
			'entity_id', Table::TYPE_INTEGER, null
			,['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
			,'Config Id'
		);
		$t->addColumn('order_id', Table::TYPE_INTEGER, null,['unsigned' => true, 'nullable' => false], 'Order Reference');
		$t->addColumn('queued_at', Table::TYPE_TIMESTAMP, null,
			['nullable' => false, 'default' => Table::TIMESTAMP_INIT], 'Queued At'
		);
		$t->addColumn('synced_at', Table::TYPE_TIMESTAMP, null, ['nullable' => true, 'default' => null], 'Synced At');
		$t->addColumn('synced', Table::TYPE_SMALLINT, 2, ['nullable' => false, 'default' => 0], 'Is Synchronized?');
		$t->addColumn('last_error', Table::TYPE_TEXT, '1k', [], 'Last Sync Error');
		$t->addForeignKey(
			$connection->getForeignKeyName(ExportResource::TABLE, 'order_id', 'sales_order', 'entity_id'),
			'order_id',
			'sales_order',
			'entity_id',
			Table::ACTION_CASCADE
		)->setComment('Sales Order Export Queue');
		$connection->createTable($t);
		$connection->addColumn($setup->getTable('sales_order_grid'), 'export_synced', [
			'comment' => 'Export Synced'
			,'default' => 0
			,'length' => 2
			,'nullable' => false,
			'type' => Table::TYPE_SMALLINT
		]);
		$setup->endSetup();
	}
}