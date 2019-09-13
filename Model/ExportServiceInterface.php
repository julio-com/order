<?php
declare(strict_types=1);
namespace Julio\Order\Model;
interface ExportServiceInterface {
	/**
	 * Initialization by orderId
	 * @param int $orderId
	 * @return Export
	 */
	function initByOrderId(int $orderId) : Export;

	/**
	 * Saves Export Item
	 * @param Export $orderExport
	 * @return void
	 */
	function save(Export $orderExport);

	/**
	 * Clean old
	 * @return void
	 */
	function cleanOld();
}