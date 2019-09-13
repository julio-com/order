<?php
namespace Julio\Order\Helper;
use DOMDocument as D;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Model\Order as O;
use Magento\Sales\Model\Order\Item as OI;
use Magento\Store\Model\StoreManagerInterface;
class GeneralHelper extends AbstractHelper {
	const PATH = 'julio_intelisis/order/%s';

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
	function __construct(Context $context, StoreManagerInterface $storeManager, EncryptorInterface $encryptor)
	{
		parent::__construct($context);
		$this->storeManager = $storeManager;
		$this->encryptor = $encryptor;
	}

	/**
	 * Check if queue is active
	 * @return bool
	 */
	function isQueueActive()
	{
		return $this->scopeConfig->isSetFlag(sprintf(self::PATH, 'queue_active'));
	}

	/**
	 * Decrypted config value
	 * @param $key
	 * @return string
	 */
	function getConfigDecrypted($key)
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
	function isAsyncGridActive()
	{
		return $this->scopeConfig->isSetFlag('dev/grid/async_indexing');
	}

	/**
	 * Upload directory path
	 * @return string
	 */
	function getUploadDirPath()
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
	 * 2019-09-13
	 * @param O $o  
	 * @return D
	 */
	function convertToXml(O $o) {
		$d = new D('1.0', 'UTF-8'); /** @var D $d */
		$d->formatOutput = true;
		$d->preserveWhiteSpace = false;
		$orderEl = $d->createElement('order');
		$orderEl->setAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
		$d->appendChild($orderEl);
		$orderDataEl = $d->createElement('order_data');
		$orderEl->appendChild($orderDataEl);
		$this->addXmlChild($d, $orderDataEl, 'order_date', $o->getCreatedAt());
		$this->addXmlChild($d, $orderDataEl, 'item_count', count($o->getAllVisibleItems()));
		$this->addXmlChild($d, $orderDataEl, 'total_item_amount', $o->getBaseSubtotal());
		$this->addXmlChild($d, $orderDataEl, 'channel', $this->storeManager->getStore($o->getStoreId())->getName());
		$this->addXmlChild($d, $orderDataEl, 'payment_method', $o->getPayment()->getMethod());
		$this->addXmlChild($d, $orderDataEl, 'seller_shipping_cost', '0');
		$this->addXmlChild($d, $orderDataEl, 'reference', $o->getIncrementId());
		$this->addXmlChild($d, $orderDataEl, 'client_id', $o->getCustomerId() ?: '');
		$billing = $o->getBillingAddress();
		$billingAddressEl = $d->createElement('invoice_address');
		$orderEl->appendChild($billingAddressEl);
		$this->addXmlChild($d, $billingAddressEl, 'address_id', $billing->getId());
		$this->addXmlCDataChild($d, $billingAddressEl, 'firstname', $billing->getFirstname());
		$this->addXmlCDataChild($d, $billingAddressEl, 'lastname', $billing->getLastname());
		$this->addXmlChild($d, $billingAddressEl, 'neighbourhood', '');
		$this->addXmlCDataChild($d, $billingAddressEl, 'street', $billing->getStreetLine(1));
		$this->addXmlCDataChild($d, $billingAddressEl, 'street_no', $billing->getStreetLine(2));
		$this->addXmlChild($d, $billingAddressEl, 'zip', $billing->getPostcode());
		$this->addXmlCDataChild($d, $billingAddressEl, 'city', $billing->getCity());
		$this->addXmlChild($d, $billingAddressEl, 'country', $billing->getCountryId());
		$this->addXmlChild($d, $billingAddressEl, 'email', $o->getCustomerEmail());
		$this->addXmlChild($d, $billingAddressEl, 'phone', $billing->getTelephone());
		$this->addXmlChild($d, $billingAddressEl, 'rfc', '');
		$shipping = $o->getShippingAddress();
		$shippingAddressEl = $d->createElement('shipping_address');
		$orderEl->appendChild($shippingAddressEl);
		$this->addXmlChild($d, $shippingAddressEl, 'address_id', $shipping->getId());
		$this->addXmlCDataChild($d, $shippingAddressEl, 'firstname', $shipping->getFirstname());
		$this->addXmlCDataChild($d, $shippingAddressEl, 'lastname', $shipping->getLastname());
		$this->addXmlChild($d, $shippingAddressEl, 'neighbourhood', '');
		$this->addXmlCDataChild($d, $shippingAddressEl, 'street', $shipping->getStreetLine(1));
		$this->addXmlCDataChild($d, $shippingAddressEl, 'street_no', $shipping->getStreetLine(2));
		$this->addXmlChild($d, $shippingAddressEl, 'zip', $shipping->getPostcode());
		$this->addXmlCDataChild($d, $shippingAddressEl, 'city', $shipping->getCity());
		$this->addXmlChild($d, $shippingAddressEl, 'country', $shipping->getCountryId());
		$this->addXmlChild($d, $shippingAddressEl, 'phone', $shipping->getTelephone());
		$itemsEl = $d->createElement('items');
		$d->appendChild($itemsEl);
		foreach ($o->getAllVisibleItems() as $i) {/** @var OI $i */
			$itemEl = $d->createElement('item');
			$itemsEl->appendChild($itemEl);
			$this->addXmlChild($d, $itemEl, 'item_id', $i->getId());
			$this->addXmlChild($d, $itemEl, 'quantity', $i->getQtyOrdered());
			$this->addXmlCDataChild($d, $itemEl, 'label', $i->getName());
			$this->addXmlChild($d, $itemEl, 'item_price', $i->getBasePrice());
			$this->addXmlChild($d, $itemEl, 'carrier', '');
			$this->addXmlChild($d, $itemEl, 'tracking_code', '');
			$this->addXmlChild($d, $itemEl, 'status', $o->getStatus());
		}
		return $d;
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