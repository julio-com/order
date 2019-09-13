<?php
namespace Julio\Order\Model\ResourceModel\Export;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
class Collection extends AbstractCollection {
	/**
	 * Resource initialization
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init(\Julio\Order\Model\Export::class, \Julio\Order\Model\ResourceModel\Export::class);
	}

	/**
	 * Set collection acting as Queue
	 */
	public function actAsQueue()
	{
		$this->setOrder('queued_at', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
		$this->addFieldToFilter('synced', 0);
		$this->setPageSize(100);

		return $this;
	}
}