<?php
namespace Julio\Order\Model;
interface ProcessObserverInterface {
	/**
	 * Notify
	 * @param string $message
	 * @return mixed
	 */
	function notify(string $message);
}