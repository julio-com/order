<?php
namespace Julio\Order\Model\Api;
interface ApiInterface {
	/**
	 * @return mixed
	 */
	function push(\Magento\Sales\Model\Order $order, \DOMDocument $xml);
}