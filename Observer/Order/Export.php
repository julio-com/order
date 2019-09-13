<?php
namespace Julio\Order\Observer\Order;
use Julio\Order\Model\ExportService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
class Export implements ObserverInterface {
	/**
	 * @var ExportService
	 */
	private $exportService;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Export constructor.
	 * @param ExportService $exportService
	 * @param LoggerInterface $logger
	 */
	function __construct(ExportService $exportService, LoggerInterface $logger)
	{
		$this->exportService = $exportService;
		$this->logger = $logger;
	}

	/**
	 * Collect data about order.
	 * @param Observer $observer
	 * @return void
	 */
	function execute(Observer $observer)
	{
		try {
			$orderId = $observer->getData('order')->getId();
			$orderExport = $this->exportService->initByOrderId($orderId);
			$this->exportService->save($orderExport);
		} catch (\Exception $e) {
			$this->logger->error(sprintf('Could not create Order Export Queue Entity (%s)', $e->getMessage()));
		}
	}
}