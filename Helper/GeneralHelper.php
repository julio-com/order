<?php
namespace Julio\Order\Helper;
use DOMDocument as D;
use DOMElement as DE;
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
	function enable() {return $this->scopeConfig->isSetFlag(sprintf(self::PATH, 'enable'));}

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
		$d = new D('1.0', 'ISO-8859-1'); /** @var D $d */
		$d->formatOutput = true;
		$d->preserveWhiteSpace = false;
		$orderEl = $d->createElement('order');
		$orderEl->setAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
		$d->appendChild($orderEl);
		$eData = $d->createElement('order_data'); /** @var DE $eData */
		$orderEl->appendChild($eData);
		$this->addXmlChild($d, $eData, 'order_date', $o->getCreatedAt());
		$this->addXmlChild($d, $eData, 'item_count', count($o->getAllVisibleItems()));
		$this->addXmlChild($d, $eData, 'total_item_amount', $o->getBaseSubtotal());
		$this->addXmlChild($d, $eData, 'channel', $this->storeManager->getStore($o->getStoreId())->getName());
		$this->addXmlChild($d, $eData, 'payment_method', $o->getPayment()->getMethod());
		$this->addXmlChild($d, $eData, 'seller_shipping_cost', '0');
		$this->addXmlChild($d, $eData, 'reference', $o->getIncrementId());
		// 2019-09-26 "`client_id` should always have a value": https://github.com/julio-com/order/issues/2
		$this->addXmlChild($d, $eData, 'client_id', $o->getCustomerId() ?: $o->getCustomerEmail());
		$billing = $o->getBillingAddress();
		$eBA = $d->createElement('invoice_address'); /** @var DE $eBA */
		$orderEl->appendChild($eBA);
		$this->addXmlChild($d, $eBA, 'address_id', $billing->getId());
		$this->addXmlChild($d, $eBA, 'firstname', $billing->getFirstname());
		$this->addXmlChild($d, $eBA, 'lastname', $billing->getLastname());
		$this->addXmlChild($d, $eBA, 'neighbourhood', '');
		$this->addXmlChild($d, $eBA, 'street', $billing->getStreetLine(1));
		$this->addXmlChild($d, $eBA, 'street_no', $billing->getStreetLine(2));
		$this->addXmlChild($d, $eBA, 'zip', $billing->getPostcode());
		$this->addXmlChild($d, $eBA, 'city', $billing->getCity());
		$this->addXmlChild($d, $eBA, 'country', $billing->getCountryId());
		$this->addXmlChild($d, $eBA, 'email', $o->getCustomerEmail());
		$this->addXmlChild($d, $eBA, 'phone', $billing->getTelephone());
		$this->addXmlChild($d, $eBA, 'rfc', '');
		$shipping = $o->getShippingAddress();
		$eSA = $d->createElement('shipping_address');/** @var DE $eSA */
		$orderEl->appendChild($eSA);
		$this->addXmlChild($d, $eSA, 'address_id', $shipping->getId());
		$this->addXmlChild($d, $eSA, 'firstname', $shipping->getFirstname());
		$this->addXmlChild($d, $eSA, 'lastname', $shipping->getLastname());
		$this->addXmlChild($d, $eSA, 'neighbourhood', '');
		$this->addXmlChild($d, $eSA, 'street', $shipping->getStreetLine(1));
		$this->addXmlChild($d, $eSA, 'street_no', $shipping->getStreetLine(2));
		$this->addXmlChild($d, $eSA, 'zip', $shipping->getPostcode());
		$this->addXmlChild($d, $eSA, 'city', $shipping->getCity());
		$this->addXmlChild($d, $eSA, 'country', $shipping->getCountryId());
		$this->addXmlChild($d, $eSA, 'phone', $shipping->getTelephone());
		$itemsEl = $d->createElement('items');
		$orderEl->appendChild($itemsEl);
		foreach ($o->getAllVisibleItems() as $i) {/** @var OI $i */
			$itemEl = $d->createElement('item');
			$itemsEl->appendChild($itemEl);
			// 2019-09-26
			// "`item_id` should be filled with the product's SKU":
			// https://github.com/julio-com/order/issues/3
			$this->addXmlChild($d, $itemEl, 'item_id', $i->getSku());
			$this->addXmlChild($d, $itemEl, 'quantity', df_oqi_qty($i));
			$this->addXmlChild($d, $itemEl, 'label', $i->getName());
			$this->addXmlChild($d, $itemEl, 'item_price', df_oqi_price($i));
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