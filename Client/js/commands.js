var Commands = {

	init: function () {
		var c = Commands;
		var y = ySocket;
		
		y.send('auth');
	},
	
	analyse: function (data) {
		var c = Commands;
		var y = ySocket;
		var key = 'yann';
		
		var split = data.split('|');
		
		switch (split[0]) {
			case 'auth':
				switch (split[1]) {
					case 'wiyk':
						y.send('auth|' + key);
						break;
					case 'good':
						break;
					case 'infos':
						break;
					case 'error':
						alert(split[2]);
						break;
				}
				break;
			case 'chat':
				y.log('<b>' + split[1] + '</b>: ' + split[2]);
				break;
			case 'error':
				y.log('<font color="orange"><b>[ERROR]</b></font>: ' + split[1]);
				break;
			default:
				break;
		}
	}
}