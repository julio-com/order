<?php
namespace Julio\Order\Ui\Component\Source\Options;
use Magento\Framework\Data\OptionSourceInterface;
class Export implements OptionSourceInterface {
	/**
	 * {@inheritdoc}
	 */
	public function toOptionArray()
	{
		return [
			[
				'value' => 0,
				'label' => __('Pending')
			],
			[
				'value' => 1,
				'label' => __('Synced')
			]
		];
	}
}