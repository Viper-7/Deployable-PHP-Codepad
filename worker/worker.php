<?php
$worker = new GearmanWorker();
$worker->addServer();
$db = new PDO('mysql:host=127.0.0.1;dbname=Codepad;charset=utf8', 'jailed', 'jailed');
$stmt = $db->prepare('SELECT FuncName, Path FROM PHPVersion WHERE LastCompiled IS NOT NULL');
$stmt->execute();

foreach($stmt->fetchAll() as $row) {
	$path = "{$row['Path']}";
	
	$func = function($job) use ($path) {
		$input = $job->workload();
		$params = (array)json_decode($input);
		$script = $params['path'];
		
		echo "Executing " . basename($script) . "...";

		$params['input'] = 'GET /' . basename($script) . " HTTP/1.1\r\n";
		foreach($params['headers'] as $header) {
			$params['input'] .= "$header\r\n";
		}

		$params['input'] .= "\r\n";
		$params['input'] .= $params['body'];

		$limits = "/bin/bash -c ulimit -c 1024000 -e 19 -f 10 -t 20 -u 10 -x 5; timelimit -T 1 -t 15 ";
		$cmd = "{$limits}{$path}/bin/php-cgi {$script}";

		$process = proc_open(
			$cmd,
			array(
				0 => array("pipe", "r"),
				1 => array("pipe", "w"),
				2 => array("pipe", "w"),
			),
			$pipes,
			$cwd = '/tmp',
			$env = array()
		);
		
		if(is_resource($process)) {
			fwrite($pipes[0], $params['input']);
			fclose($pipes[0]);
			
			stream_set_timeout($pipes[1], 16);
			stream_set_timeout($pipes[2], 16);
			
			$content = stream_get_contents($pipes[1]);
			$errors = stream_get_contents($pipes[2]);
			
			fclose($pipes[1]);
			fclose($pipes[2]);
			
			$retval = proc_close($process);

			echo " done!\n";
			
			if($errors) {
				return '<div class="error">' . $errors . '</div>';
			} else {
				return $content;
			}
		} else {
			echo " failed!\n";

			return '<div class="error">Failed to open process</div>';
		}
	};
	
	echo "Registered {$row['FuncName']}\n";
	$worker->addFunction($row['FuncName'], $func);
}

while($worker->work());
?>
