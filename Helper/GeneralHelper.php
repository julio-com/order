<?php
namespace Julio\Order\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\StoreManagerInterface;
class GeneralHelper extends AbstractHelper {
	const PATH = 'sales/orderexport/%s';

	/**
	 * @var StoreManagerInterface
	 */
	private $storeManager;

	/**
	 * @var EncryptorInterface
	 */
	private $encryptor;

	/**
	 * GeneralHelper constructor.
	 * @param Context $context
	 * @param StoreManagerInterface $storeManager
	 */
	public function __construct(Context $context, StoreManagerInterface $storeManager, EncryptorInterface $encryptor)
	{
		parent::__construct($context);
		$this->storeManager = $storeManager;
		$this->encryptor = $encryptor;
	}

	/**
	 * Check if queue is active
	 * @return bool
	 */
	public function isQueueActive()
	{
		return $this->scopeConfig->isSetFlag(sprintf(self::PATH, 'queue_active'));
	}

	/**
	 * Decrypted config value
	 * @param $key
	 * @return string
	 */
	public function getConfigDecrypted($key)
	{
		$value = $this->scopeConfig->getValue(sprintf(self::PATH, $key));
		if (empty($value)) {
			return '';
		}

		return $this->encryptor->decrypt($value);
	}

	/**
	 * Check if grid active
	 * @return boolean
	 */
	public function isAsyncGridActive()
	{
		return $this->scopeConfig->isSetFlag('dev/grid/async_indexing');
	}

	/**
	 * Upload directory path
	 * @return string
	 */
	public function getUploadDirPath()
	{
		$path = $this->scopeConfig->getValue(sprintf(self::PATH, 'dropbox_dir_path'));
		$path = trim($path);
		$path = trim($path, '/');

		if (empty($path)) {
			return '/';
		}

		return '/' . $path . '/';
	}

	/**
	 * Xml representation of order
	 * @param \Magento\Sales\Model\Order $order
	 */
	public function convertToXml(\Magento\Sales\Model\Order $order)
	{
		$xml = new \DOMDocument('1.0', 'UTF-8');
		$xml->formatOutput = true;
		$xml->preserveWhiteSpace = false;
		$orderEl = $xml->createElement('order');
		$orderEl->setAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
		$xml->appendChild($orderEl);

		$orderDataEl = $xml->createElement('order_data');
		$orderEl->appendChild($orderDataEl);
		$this->addXmlChild($xml, $orderDataEl, 'order_date', $order->getCreatedAt());
		$this->addXmlChild($xml, $orderDataEl, 'item_count', count($order->getAllVisibleItems()));
		$this->addXmlChild($xml, $orderDataEl, 'total_item_amount', $order->getBaseSubtotal());
		$this->addXmlChild($xml, $orderDataEl, 'channel', $this->storeManager->getStore($order->getStoreId())->getName());
		$this->addXmlChild($xml, $orderDataEl, 'payment_method', $order->getPayment()->getMethod());
		$this->addXmlChild($xml, $orderDataEl, 'seller_shipping_cost', '0');
		$this->addXmlChild($xml, $orderDataEl, 'reference', $order->getIncrementId());
		$this->addXmlChild($xml, $orderDataEl, 'client_id', $order->getCustomerId() ?: '');

		$billing = $order->getBillingAddress();
		$billingAddressEl = $xml->createElement('invoice_address');
		$orderEl->appendChild($billingAddressEl);
		$this->addXmlChild($xml, $billingAddressEl, 'address_id', $billing->getId());
		$this->addXmlCDataChild($xml, $billingAddressEl, 'firstname', $billing->getFirstname());
		$this->addXmlCDataChild($xml, $billingAddressEl, 'lastname', $billing->getLastname());
		$this->addXmlChild($xml, $billingAddressEl, 'neighbourhood', '');
		$this->addXmlCDataChild($xml, $billingAddressEl, 'street', $billing->getStreetLine(1));
		$this->addXmlCDataChild($xml, $billingAddressEl, 'street_no', $billing->getStreetLine(2));
		$this->addXmlChild($xml, $billingAddressEl, 'zip', $billing->getPostcode());
		$this->addXmlCDataChild($xml, $billingAddressEl, 'city', $billing->getCity());
		$this->addXmlChild($xml, $billingAddressEl, 'country', $billing->getCountryId());
		$this->addXmlChild($xml, $billingAddressEl, 'email', $order->getCustomerEmail());
		$this->addXmlChild($xml, $billingAddressEl, 'phone', $billing->getTelephone());
		$this->addXmlChild($xml, $billingAddressEl, 'rfc', '');

		$shipping = $order->getShippingAddress();
		$shippingAddressEl = $xml->createElement('shipping_address');
		$orderEl->appendChild($shippingAddressEl);
		$this->addXmlChild($xml, $shippingAddressEl, 'address_id', $shipping->getId());
		$this->addXmlCDataChild($xml, $shippingAddressEl, 'firstname', $shipping->getFirstname());
		$this->addXmlCDataChild($xml, $shippingAddressEl, 'lastname', $shipping->getLastname());
		$this->addXmlChild($xml, $shippingAddressEl, 'neighbourhood', '');
		$this->addXmlCDataChild($xml, $shippingAddressEl, 'street', $shipping->getStreetLine(1));
		$this->addXmlCDataChild($xml, $shippingAddressEl, 'street_no', $shipping->getStreetLine(2));
		$this->addXmlChild($xml, $shippingAddressEl, 'zip', $shipping->getPostcode());
		$this->addXmlCDataChild($xml, $shippingAddressEl, 'city', $shipping->getCity());
		$this->addXmlChild($xml, $shippingAddressEl, 'country', $shipping->getCountryId());
		$this->addXmlChild($xml, $shippingAddressEl, 'phone', $shipping->getTelephone());

		$itemsEl = $xml->createElement('items');
		$xml->appendChild($itemsEl);

		foreach ($order->getAllVisibleItems() as $item) {
			/** @var Item $item */
			$itemEl = $xml->createElement('item');
			$itemsEl->appendChild($itemEl);
			$this->addXmlChild($xml, $itemEl, 'item_id', $item->getId());
			$this->addXmlChild($xml, $itemEl, 'quantity', $item->getQtyOrdered());
			$this->addXmlCDataChild($xml, $itemEl, 'label', $item->getName());
			$this->addXmlChild($xml, $itemEl, 'item_price', $item->getBasePrice());
			$this->addXmlChild($xml, $itemEl, 'carrier', '');
			$this->addXmlChild($xml, $itemEl, 'tracking_code', '');
			$this->addXmlChild($xml, $itemEl, 'status', $order->getStatus());
		}

		return $xml;
	}

	/**
	 * Append new child with basic value
	 * @param \DOMDocument $doc
	 * @param \DOMElement $parentEl
	 * @param $name
	 * @param null $value
	 * @return \DOMElement
	 */
	protected function addXmlChild(\DOMDocument $doc, \DOMElement $parentEl, $name, $value = null)
	{
		$el = $doc->createElement($name, $value);
		$parentEl->appendChild($el);

		return $el;
	}

	/**
	 * Append new CData section
	 * @param \DOMDocument $doc
	 * @param \DOMElement $parentEl
	 * @param $name
	 * @param null $value
	 * @return \DOMElement
	 */
	protected function addXmlCDataChild(\DOMDocument $doc, \DOMElement $parentEl, $name, $value = null)
	{
		$el = $doc->createElement($name);
		$cdata = $doc->createCDATASection($value);
		$el->appendChild($cdata);
		$parentEl->appendChild($el);

		return $el;
	}
}