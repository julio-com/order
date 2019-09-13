<?php
namespace Julio\Order\Model;
use Julio\Order\Model\ResourceModel\Export as ExportResource;
class ExportService {
	/**
	 * ExportService constructor.
	 * @param ExportFactory $exportFactory
	 * @param ResourceModel\Export $exportResource
	 */
	function __construct(ExportFactory $exportFactory, ExportResource $exportResource) {
		$this->exportFactory = $exportFactory;
		$this->exportResource = $exportResource;
	}

	/**
	 * Initialization by orderId
	 * @param int $orderId
	 * @return Export
	 */
	function initByOrderId(int $orderId): Export {
		/** @var Export $orderExport */
		$orderExport = $this->exportFactory->create();
		$this->exportResource->load($orderExport, $orderId, 'order_id');
		if ($orderExport->isObjectNew()) {
			$orderExport->setData('order_id', $orderId);
		}
		return $orderExport;
	}

	/**
	 * Saves Order Export queue item
	 * @param Export $orderExport
	 * @return void
	 * @throws \Magento\Framework\Exception\AlreadyExistsException
	 */
	function save(Export $orderExport) {$this->exportResource->save($orderExport);}

	/**
	 * Clean old
	 * @return void
	 */
	function cleanOld() {}

	/**
	 * @var ExportFactory
	 */
	private $exportFactory;

	/**
	 * @var ExportResource
	 */
	private $exportResource;
}