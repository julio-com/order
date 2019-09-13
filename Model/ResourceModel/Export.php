<?php
namespace Julio\Order\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
class Export extends AbstractDb {
	const TABLE = 'sales_order_export_queue';
	const ID_FIELD = 'entity_id';

	/**
	 * Resource initialization
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init(self::TABLE, self::ID_FIELD);
	}
}