<?php

class WebSocket
{
	
	protected $socket;

	public function __construct()
	{
		global $host, $port;
		
		$this->host = $host;
		$this->port = $port;
		
		$this->users = array();
	
		$this->listen();
	}
	
	public function send($user, $string, $opcode = 1)
	{
		$string = $this->encode($string, $opcode);
		socket_write($user->socket, $string, strlen($string));
	}
	
	public function broadcast($string, $channel = false)
	{
		foreach ($this->users as $user)
		{
			if ($channel != false)
			{
				if ($user->channel == $channel)
				{
					$this->send($user, $string);
				}
			}
			else
			{
				$this->send($user, $string);
			}
		}
	}
	
	private function listen()
	{
		$socketMaster = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		
		socket_set_option($socketMaster, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($socketMaster, $this->host, $this->port);
		socket_listen($socketMaster);
		// socket_set_nonblock($socketMaster);
		
		$this->sockets = array($socketMaster);
		
		console("            WebSocket Server            ", "START", true);
		console("========================================", "START");
		console("Server Started : ".date('Y-m-d H:i:s'), "START");
		console("Listening on   : ".$this->host.":".$this->port, "START");
		console("========================================\r\n", "START");
		
		while (true)
		{
			$changed = $this->sockets;
			$expect  = $this->sockets;
			
			socket_select($changed, $write = null, $expect, 0, 1000000);
			
			foreach ($changed as $socket)
			{
				if ($socket == $socketMaster)
				{
					$client = socket_accept($socketMaster);
					
					if ($client < 0)
					{
						console("Connexion failed.", "ERROR");
						continue;
					}
					else
					{
						socket_set_option($client, SOL_SOCKET, SO_KEEPALIVE, 1);
						$this->newClient($client);
					}
				}
				else
				{
					$bytes = @socket_recv($socket, $buffer, 2048, 0);
					
					if ($bytes == 0)
					{
						$this->closeClient($socket);
					}
					else
					{
						$user = $this->userInfos($socket);
						
						if (!$user->handshake)
						{
							$this->handshake($user, $buffer);
						}
						else
						{
							$decode = $this->decode($buffer);
							
							switch ($decode[0])
							{
								case 0: // Continue
									break;
								case 1: // Message
									$this->broadcast($decode[1], $user->channel);
									// $this->broadcast($decode[1]);
									break;
								case 2: // Binary
									break;
								case 8: // Close
									$this->send($user, '', 8);
									break;
								case 9: // Ping
									$this->send($user, '', 9);
									break;
								default:
									break;
							}
						}
					}
				}
			}
		}
	}
	
	private function newClient($socket)
	{
		$user->id = uniqid();
		$user->socket = $socket;
		$user->handshake = false;
		
		array_push($this->users, $user);
		array_push($this->sockets, $socket);

		console("Client connected ($socket).");
	}
	
	private function closeClient($socket)
	{
		$found = null;
		$n = count($this->users);
		
		for ($i = 0; $i < $n; $i++)
		{
			if ($this->users[$i]->socket == $socket)
			{
				array_splice($this->users, $i, 1);
				break;
			}
		}
		
		$index = array_search($socket, $this->sockets);
		socket_close($socket);
		
		if ($index >= 0)
		{
			array_splice($this->sockets, $index, 1);
		}
		
		console("Client disconnected ($socket).");
	}
	
	private function userInfos($socket)
	{
		$found = null;
		
		foreach ($this->users as $user)
		{
			if ($user->socket == $socket)
			{
				$found = $user;
				break;
			}
		}
		
		return $found;
	}
	
	private function getHeaders($buffer)
	{
		$headers = array();
		
		preg_match('#GET (.*?) HTTP#', $buffer, $match) &&							$headers['ressources'] = $match[1];
		preg_match("#Host: (.*?)\r\n#", $buffer, $match) &&							$headers['host'] = $match[1];
		// preg_match("#Sec-WebSocket-Key1: (.*?)\r\n#", $buffer, $match) &&		$headers['key1'] = $match[1];
		// preg_match("#Sec-WebSocket-Key2: (.*?)\r\n#", $buffer, $match) &&		$headers['key2']= $match[1];
		preg_match("#Sec-WebSocket-Key: (.*?)\r\n#", $buffer, $match) &&			$headers['key'] = $match[1];
		// preg_match("#Sec-WebSocket-Protocol: (.*?)\r\n#", $buffer, $match) &&	$headers['protocol'] = $match[1];
		preg_match("#Origin: (.*?)\r\n#", $buffer, $match) &&						$headers['origin'] = $match[1];
		preg_match("#\r\n(.*?)\$#", $buffer, $match) &&								$headers['code'] = $match[1];
		
		return $headers;
	}
	
	private function handshake($user, $buffer)
	{
		$headers = $this->getHeaders($buffer);
		
        $handshake = 
		"HTTP/1.1 101 WebSocket Protocol Handshake\r\n".
		"Upgrade: WebSocket\r\n".
		"Connection: Upgrade\r\n".
		"Sec-WebSocket-Origin: {$headers['origin']}\r\n".
		"Sec-WebSocket-Location: ws://{$headers['host']}{$headers['ressources']}\r\n".
		// ($headers['protocol'] ? "Sec-WebSocket-Protocol: {$headers['protocol']}\r\n" : "").
		"Sec-WebSocket-Accept: ". base64_encode(SHA1($headers['key']."258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true)) . "\r\n\r\n";
		
		socket_write($user->socket, $handshake, strlen($handshake));
		
		$user->handshake = true;
		$user->channel = $headers['ressources'];
	}
	
	private function encode($text, $opcode = 1)
	{
		$FIN  = 1;
		$RSV1 = 0;
		$RSV2 = 0;
		$RSV3 = 0;

		$len = strlen($text);

		$firstByte = $opcode;

		$firstByte += $FIN * 128 + $RSV1 * 64 + $RSV2 * 32 + $RSV3 * 16;

		$encoded = chr($firstByte);

		if ($len <= 125)
		{
			$secondByte = $len;

			$encoded .= chr($secondByte);
		}
		else if ($len <= 255 * 255 - 1)
		{
			$secondByte = 126;

			$encoded .= chr($secondByte) . pack("n", $len);
		}
		else
		{
			$secondByte = 127;

			$encoded .= chr($secondByte);
			$encoded .= pack("N", 0);
			$encoded .= pack("N", $len);
		}

		if ($text)
		{
			$encoded .= $text;
		}
		
		return $encoded;
	}
	
	private function decode($data)
	{
		list($firstByte, $secondByte) = substr($data, 0, 2);
		
		$firstByte = ord($firstByte);
		$secondByte = ord($secondByte);

		$opcode = ($firstByte & 0x0F);
	
		$len = ord($data[1]) & 127;

		if($len == 126)
		{
			$masks = substr($data, 4, 4);
			$data = substr($data, 8);
		}
		elseif($len == 127)
		{
			$masks = substr($data, 10, 4);
			$data = substr($data, 14);
		}
		else
		{
			$masks = substr($data, 2, 4);
			$data = substr($data, 6);
		}

		$text = '';

		for ($i = 0; $i < strlen($data); ++$i)
		{
			$text .= $data[$i] ^ $masks[$i%4];
		}
		
		return array($opcode,$text);
	}
	
}