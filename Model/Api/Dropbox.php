<?php
namespace Julio\Order\Model\Api;
use Julio\Order\Helper\GeneralHelper;
use Kunnu\Dropbox as DropboxLib;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\Sales\Model\Order as O;
class Dropbox {
	const EXPORT_DIR = '/tmp/xml_export';

	/**
	 * @var
	 */
	protected $api;

	/**
	 * @var GeneralHelper
	 */
	protected $generalHelper;

	/**
	 * @var WriteFactory
	 */
	protected $writeFactory;

	/**
	 * @var DirectoryList
	 */
	protected $directoryList;


	/**
	 * Dropbox constructor.
	 * @param GeneralHelper $generalHelper
	 * @param WriteFactory $writeFactory
	 * @param DirectoryList $directoryList
	 */
	function __construct(
		GeneralHelper $generalHelper,
		WriteFactory $writeFactory,
		DirectoryList $directoryList
	) {
		$this->generalHelper = $generalHelper;
		$this->writeFactory = $writeFactory;
		$this->directoryList = $directoryList;
	}

	/**
	 * API
	 * @return DropboxLib\Dropbox;
	 */
	protected function getApi()
	{
		if (!isset($this->api)) {
			$key = $this->generalHelper->getConfigDecrypted('dropbox_api_key');
			$secret = $this->generalHelper->getConfigDecrypted('dropbox_api_secret');
			$token = $this->generalHelper->getConfigDecrypted('dropbox_access_token');

			$dropboxApp = new DropboxLib\DropboxApp($key, $secret, $token);
			$this->api = new DropboxLib\Dropbox($dropboxApp);
		}

		return $this->api;
	}

	/**
	 * @param O $o
	 * @param \DOMDocument $xml
	 * @return mixed
	 * @throws \Magento\Framework\Exception\FileSystemException
	 */
	function push(O $o, \DOMDocument $xml) {
		$xml = $xml->saveXML();
		$varDir = $this->writeFactory->create(DirectoryList::VAR_DIR);
		if (!$varDir->isDirectory(self::EXPORT_DIR)) {
			$varDir->create(self::EXPORT_DIR);
		}
		$exportDir = $this->writeFactory->create(DirectoryList::VAR_DIR . self::EXPORT_DIR);
		$fileName = sprintf("ORDER_%s_{$o->getIncrementId()}.xml", df_date()->toString('ddMMy'));
		$tmpName = md5(microtime(true) . $fileName) . '.xml';
		$exportDir->writeFile($tmpName, $xml);
		$dbFile = $this->getApi()->upload(
			$this->directoryList->getRoot() . '/'. $exportDir->getAbsolutePath($tmpName),
			$this->generalHelper->getUploadDirPath() . $fileName
		);
		$exportDir->delete($tmpName);
		return $dbFile;
	}
}