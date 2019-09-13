<?php
namespace Julio\Order\Cron;
use Julio\Order\Helper\GeneralHelper;
use Julio\Order\Model\ProcessObserverInterface;
use Julio\Order\Model\QueueProcessor;
class ProcessQueue implements ProcessObserverInterface {
	/**
	 * ProcessQueue constructor.
	 * @param QueueProcessor $queueProcessor
	 * @param GeneralHelper $generalHelper
	 */
	function __construct(QueueProcessor $queueProcessor, GeneralHelper $generalHelper) {
		$this->queueProcessor = $queueProcessor;
		$this->generalHelper = $generalHelper;
	}

	/** 2019-09-13 */
	function execute() {
		if ($this->generalHelper->enable()) {
			$this->queueProcessor
				->setTimeInterval(QueueProcessor::DEFAULT_TIME_INTERVAL)
				->process($this);
		}
	}

	/**
	 * Notify
	 * @param string $message
	 * @return mixed
	 */
	function notify(string $message) {}

	/**
	 * @var QueueProcessor
	 */
	private $queueProcessor;

	/**
	 * @var GeneralHelper
	 */
	private $generalHelper;
}