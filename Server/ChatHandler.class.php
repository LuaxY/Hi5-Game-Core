<?php

class ChatHandler
{
	
	public function onMessage($ws, $string, $user)
	{
		$ws->broadcast('chat|Server|'.str_replace('|', ' ', $string), $user->channel);
	}
	
}