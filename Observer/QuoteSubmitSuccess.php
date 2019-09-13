<?php
namespace Julio\Order\Observer;
use Julio\Order\Model\ExportService;
use Magento\Framework\Event\Observer as Ob;
use Magento\Framework\Event\ObserverInterface;
// 2019-09-13
class QuoteSubmitSuccess implements ObserverInterface {
	/**
	 * 2019-09-13
	 * @override
	 * @see ObserverInterface::execute()
	 * @used-by \Magento\Framework\Event\Invoker\InvokerDefault::_callObserverMethod()
	 * @param Ob $ob
	 */
	function execute(Ob $ob) {
		try {
			$orderId = $ob->getData('order')->getId();
			$exportService = df_o(ExportService::class); /** @var ExportService $exportService */
			$orderExport = $exportService->initByOrderId($orderId);
			$exportService->save($orderExport);
		}
		catch (\Exception $e) {df_log($e);}
	}
}