<?php
namespace Julio\Order\Console\Command;
use Julio\Order\Helper\GeneralHelper;
use Julio\Order\Model\ProcessObserverInterface;
use Julio\Order\Model\QueueProcessor;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
class ExportCommand extends Command implements ProcessObserverInterface {
	/**
	 * @var State
	 */
	private $appState;

	/**
	 * @var QueueProcessor
	 */
	private $queueProcessor;

	/**
	 * @var GeneralHelper
	 */
	private $generalHelper;

	/**
	 * @var \Symfony\Component\Console\Output\OutputInterface
	 */
	private $output;

	/**
	 * ExportCommand constructor.
	 * @param State $appState
	 * @param QueueProcessor $queueProcessor
	 * @param GeneralHelper $generalHelper
	 * @param null $name
	 */
	function __construct(
		State $appState,
		QueueProcessor $queueProcessor,
		GeneralHelper $generalHelper,
		$name = null
	) {
		parent::__construct($name);
		$this->appState = $appState;
		$this->queueProcessor = $queueProcessor;
		$this->generalHelper = $generalHelper;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function configure() {$this->setName('sales:order:export')->setDescription('Sales Order Export');}

	/**
	 * Execute
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return null
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->output = $output;
		$this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
		$this->queueProcessor
			->setTimeInterval(QueueProcessor::LONG_TIME_INTERVAL)
			->process($this);
	}

	/**
	 * Notify
	 * @param string $message
	 * @return mixed
	 */
	function notify(string $message) {$this->output->writeln($message);}
}