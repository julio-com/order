<?php
namespace Julio\Order\Model;
use Julio\Order\Model\ResourceModel\Export as ExportResource;
class ExportService implements ExportServiceInterface {
	/**
	 * @var ExportFactory
	 */
	protected $exportFactory;

	/**
	 * @var ExportResource
	 */
	protected $exportResource;

	/**
	 * ExportService constructor.
	 * @param ExportFactory $exportFactory
	 * @param ResourceModel\Export $exportResource
	 */
	public function __construct(ExportFactory $exportFactory, ExportResource $exportResource)
	{
		$this->exportFactory = $exportFactory;
		$this->exportResource = $exportResource;
	}

	/**
	 * Initialization by orderId
	 * @param int $orderId
	 * @return Export
	 */
	public function initByOrderId(int $orderId): Export
	{
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
	public function save(Export $orderExport)
	{
		$this->exportResource->save($orderExport);
	}

	/**
	 * Clean old
	 * @return void
	 */
	public function cleanOld()
	{

	}
}