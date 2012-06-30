var ySocket = {
	
	connect: function () {
		var y = ySocket;
		var c = Commands;
		var host = $('host').value;
		
		try {
			socket = new WebSocket(host);
			y.log('<b>[INFO]</b>: Chargement...');
			
			/* this.readyState // Status of socket*/
			
			socket.onopen = function (s) { 
				y.log('<b>[INFO]</b>: <font color="blue">Connecté.</font>');
				$('status-img').src = 'images/online.png';
				$('connect').disabled = true;
				$('disconnect').disabled = false;
				$('message').disabled = false;
				$('send').disabled = false;
			};
			
			socket.onmessage = function (s) {
				if (y.debug) { y.log('<font color="green"><b>[RECV]</b></font>: '+ s.data); }
				c.analyse(s.data);
			};
			
			socket.onclose = function (s) {
				y.log('<b>[INFO]</b>: <font color="red">Déconnecté.</font>');
				$('status-img').src = 'images/offline.png';
				$('connect').disabled = false;
				$('disconnect').disabled = true;
				$('message').disabled = true;
				$('send').disabled = true;
			};
			
			socket.onerror = function (s) {
				y.log('<font color="orange"><b>[ERROR]</b></font>: Une erreur est survenue.');
				console.log(s);
			};
		}
		catch(e) {
			y.log(e);
		}
	},
	
	disconnect: function () {
		socket.close();
	},
	
	send: function (msg) {
		var y = ySocket;
		
		if (msg == null) { var msg = $('message').value; }
		
		if (msg != '') {
			$('message').value = '';
			if (y.debug) { y.log('<font color="blue"><b>[SEND]</b></font>: ' + msg); }
			socket.send(msg);
		}
	},
	
	onkey: function (e) {
		var y = ySocket;
		
		if (e.keyCode == 13) { y.send(); }
	},
	
	log: function (msg) {
		var y = ySocket;
		var p = document.createElement('p');
		
		msg = '<span style="float:right;color:grey;">'+ y.date() +'</span>' + msg;
		p.className = 'log';
		p.innerHTML = msg;
		$('consoleLog').appendChild(p);
		
		while ($('consoleLog').childNodes.lenght > 50) {
			$('consoleLog').removeChild($('consoleLog').firstChild);
		}
		
		$('consoleLog').scrollTop = $('consoleLog').scrollHeight;
	},
	
	clear: function () {
		$('consoleLog').innerHTML = '';
	},
	
	date: function () {
		date = new Date;
		
		var h = date.getHours();
		var m = date.getMinutes();
		var s = date.getSeconds();
		
		if (h<10) { h = "0"+h; }	
		if (m<10) { m = "0"+m; }
		if (s<10) { s = "0"+s; }
		
		return h + ':' + m + ':' + s;
	}
}

function $(id) {
	return document.getElementById(id);
}