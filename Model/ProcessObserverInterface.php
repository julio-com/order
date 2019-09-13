<?php
namespace Julio\Order\Model;
interface ProcessObserverInterface {
	/**
	 * Notify
	 * @param string $message
	 * @return mixed
	 */
	public function notify(string $message);
}