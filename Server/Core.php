<?php

require_once('Configuration.ini.php');
require_once('WebSocket.class.php');
require_once('ChatHandler.class.php');

class Core
{
	
	public function __construct()
	{
		
	}

	public function console($message, $type = 'INFO', $back = false)
	{
		echo ($back ? "\r\n<".date('H:i:s')."> [$type] $message\r\n" : "<".date('H:i:s')."> [$type] $message\r\n");
	}
	
}

$Core = new Core();
$WebSocket = new WebSocket();