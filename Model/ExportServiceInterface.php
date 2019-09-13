<?php
declare(strict_types=1);
namespace Julio\Order\Model;
interface ExportServiceInterface {
	/**
	 * Initialization by orderId
	 * @param int $orderId
	 * @return Export
	 */
	public function initByOrderId(int $orderId) : Export;

	/**
	 * Saves Export Item
	 * @param Export $orderExport
	 * @return void
	 */
	public function save(Export $orderExport);

	/**
	 * Clean old
	 * @return void
	 */
	public function cleanOld();
}