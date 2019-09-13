<?php
namespace Julio\Order\Model;
use Julio\Order\Helper\GeneralHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
class Export extends \Magento\Framework\Model\AbstractModel {
	/**
	 * @var \Magento\Sales\Model\Order
	 */
	protected $order;

	/**
	 * @var OrderRepositoryInterface
	 */
	private $orderRepository;
	/**
	 * @var GeneralHelper
	 */
	private $generalHelper;

	/**
	 * Export constructor.
	 * @param \Magento\Framework\Model\Context $context
	 * @param \Magento\Framework\Registry $registry
	 * @param OrderRepositoryInterface $orderRepository
	 * @param ResourceModel\AbstractResource|null $resource
	 * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
	 * @param array $data
	 */
	function __construct(
		\Magento\Framework\Model\Context $context,
		\Magento\Framework\Registry $registry,
		OrderRepositoryInterface $orderRepository,
		GeneralHelper $generalHelper,
		\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
		\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
		array $data = []
	) {
		parent::__construct($context, $registry, $resource, $resourceCollection, $data);
		$this->orderRepository = $orderRepository;
		$this->generalHelper = $generalHelper;
	}

	/**
	 * Export
	 */
	function _construct()
	{
		$this->_init(\Julio\Order\Model\ResourceModel\Export::class);
	}

	/**
	 * Is Synced?
	 * @return bool
	 */
	function isSynced()
	{
		return (bool)(int)$this->_getData('is_synced');
	}

	/**
	 * return \Magento\Sales\Model\Order
	 */
	function getOrder()
	{
		if (!isset($this->order)) {
			$this->order = $this->orderRepository->get($this->getOrderId());
		}

		return $this->order;
	}

	/**
	 * Exports XML from
	 * @return \DOMDocument
	 */
	function asXml()
	{
		return $this->generalHelper->convertToXml($this->getOrder());
	}
}