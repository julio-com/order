<?php
namespace Julio\Order\Cron;
use Julio\Order\Helper\GeneralHelper;
use Julio\Order\Model\ProcessObserverInterface;
use Julio\Order\Model\QueueProcessor;
class ProcessQueue implements ProcessObserverInterface {
	/**
	 * @var QueueProcessor
	 */
	private $queueProcessor;

	/**
	 * @var GeneralHelper
	 */
	private $generalHelper;

	/**
	 * ProcessQueue constructor.
	 * @param QueueProcessor $queueProcessor
	 * @param GeneralHelper $generalHelper
	 */
	function __construct(QueueProcessor $queueProcessor, GeneralHelper $generalHelper)
	{
		$this->queueProcessor = $queueProcessor;
		$this->generalHelper = $generalHelper;
	}

	/**
	 * Execute
	 */
	function execute()
	{
		if (!$this->generalHelper->isQueueActive()) {
			return false;
		}

		$this->queueProcessor
			->setTimeInterval(QueueProcessor::DEFAULT_TIME_INTERVAL)
			->process($this);
	}

	/**
	 * Notify
	 * @param string $message
	 * @return mixed
	 */
	function notify(string $message)
	{
		// do nothing;
	}
}