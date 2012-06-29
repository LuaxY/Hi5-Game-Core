<?php

require_once('Configuration.ini.php');
require_once('WebSocket.class.php');

function console($message, $type = 'INFO', $back = false)
{
	echo ($back ? "\r\n<".date('H:i:s')."> [$type] $message\r\n" : "<".date('H:i:s')."> [$type] $message\r\n");
}

$WebSocket = new WebSocket();