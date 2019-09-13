<?php
namespace Julio\Order\Model;
use Julio\Order\Helper\GeneralHelper;
use Julio\Order\Model\Api\ApiInterface;
use Julio\Order\Model\ResourceModel\Export\Collection;
use Julio\Order\Model\ResourceModel\Export\CollectionFactory;
use Magento\Sales\Model\ResourceModel\GridInterface;
use Psr\Log\LoggerInterface;
class QueueProcessor {
	/**
	 * Must be compatible with interval in crontab.xml
	 */
	const DEFAULT_TIME_INTERVAL = 5 * 60; // N * 60s

	/**
	 * No time interval - for manual launch
	 */
	const LONG_TIME_INTERVAL = 3600 * 24;

	/**
	 * Time margin before time left
	 */
	const TIME_MARGIN = 10;

	/**
	 * @var ResourceModel\Export\CollectionFactory
	 */
	protected $collectionFactory;

	/**
	 * Time interval
	 * @var int
	 */
	protected $timeInterval;

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * @var ExportService
	 */
	protected $exportService;

	/**
	 * @var ApiInterface
	 */
	protected $api;

	/**
	 * @var GridInterface
	 */
	protected $grid;

	/**
	 * @var GeneralHelper
	 */
	protected $generalHelper;

	/**
	 * QueueProcessor constructor.
	 * @param CollectionFactory $collectionFactory
	 */
	public function __construct(
		CollectionFactory $collectionFactory,
		LoggerInterface $logger,
		ApiInterface $api,
		ExportService $exportService,
		GridInterface $grid,
		GeneralHelper $generalHelper
	) {
		$this->collectionFactory = $collectionFactory;
		$this->logger = $logger;
		$this->exportService = $exportService;
		$this->api = $api;
		$this->grid = $grid;
		$this->generalHelper = $generalHelper;
	}

	/**
	 * Interval
	 * @param $interval
	 * @return QueueProcessor
	 */
	public function setTimeInterval($interval)
	{
		$this->timeInterval = $interval;

		return $this;
	}

	/**
	 * Time interval
	 * @return int
	 */
	public function getTimeInterval()
	{
		return $this->timeInterval;
	}

	/**
	 * Processing the queue
	 * Number of processed items are dynamically calculated based on TimeInterval. Time interval is period between
	 * subsequent calls queue processing from Cron.
	 * @param ProcessObserverInterface $observer
	 * @return void|boolean
	 * @throws \Magento\Framework\Exception\AlreadyExistsException
	 */
	public function process(ProcessObserverInterface $observer)
	{
		/** @var Collection $queue */
		$queue = $this->collectionFactory
			->create()
			->actAsQueue();

		if (0 === $queue->count()) {
			return false;
		}

		$unitTimes = [];
		$timeStart = microtime(true);
		$timeEnd = $timeStart + $this->getTimeInterval() - self::TIME_MARGIN;
		$stillHaveTime = true;
		$queueItems = $queue->getIterator();
		$queueItem = current($queueItems);

		do {
			try {
				$unitTimes[] = $this->processItem($queueItem);
				$observer->notify('Processing Item: ' . $queueItem->getId());
			} catch (\Exception $e) {
				$this->logger->error(sprintf('XML Order Export: Error during processing Order Queue: %s', $e->getMessage()));
				$this->exportService->save($queueItem->setLastError($e->getMessage()));
				$observer->notify(sprintf('Processing Item: %d. Error: %s', $queueItem->getId(), $e->getMessage()));
			}
			$currentTime = microtime(true);
			if ($currentTime > $timeEnd - $this->avg($unitTimes)) {
				$stillHaveTime = false;
			}
			$queueItem = next($queueItems);
		} while ($queueItem instanceof Export && $stillHaveTime);
	}

	/**
	 * Processing one queue item
	 * @param Export $queueItem
	 * @return float
	 * @throws \Magento\Framework\Exception\AlreadyExistsException
	 */
	protected function processItem(Export $queueItem)
	{
		$timeStart = microtime(true);
		$this->getApi()->push($queueItem->getOrder(), $queueItem->asXml());
		$queueItem->addData([
			'last_error' => null,
			'synced' => 1,
			'synced_at' => new \Zend_Db_Expr('NOW()')
		]);
		$this->exportService->save($queueItem);
		if (!$this->generalHelper->isAsyncGridActive()) {
			$this->grid->refresh($queueItem->getOrderId());
		}
		$timeFinish = microtime(true);

		return $timeFinish - $timeStart;
	}

	/**
	 * Average time
	 * @param array $times
	 * @return float|int
	 */
	protected function avg(array $times)
	{
		$count = count($times);
		if ($count == 0) {
			return 0;
		}

		return array_sum($times) / $count;
	}

	/**
	 * Api
	 * @return ApiInterface
	 */
	protected function getApi()
	{
		return $this->api;
	}
}