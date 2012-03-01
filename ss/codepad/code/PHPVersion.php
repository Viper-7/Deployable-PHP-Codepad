<?php
class PHPVersion extends DataObject {
	function emu_getallheaders() {
	        foreach($_SERVER as $name => $value) {
	                if(substr($name, 0, 5) == 'HTTP_' || in_array($name, array('CONTENT_TYPE', 'CONTENT_LENGTH'))) {
        	                $name = str_replace(' ', '-', ucwords(strtolower(str_replace(array('HTTP_', '_'), array('', ' '), $name))));
                	        $headers[$name] = $value;
        	        }
	        }
        	return $headers;
	}

	public static $db = array(
		'Title' => 'Varchar(255)',
		'Path' => 'Varchar(4000)',
		'FuncName' => 'Varchar(255)',
		'LastCompiled' => 'Datetime',
		'IsDefault' => 'Boolean',
	);

	public function executePaste($id) {
		$paste_dir = '/code';	
		$jail_dir = '/opt/codepad';

		$file = "{$paste_dir}/{$id}";

		if(file_exists("{$jail_dir}/{$file}")) {
			$gearman = new GearmanClient();
			$gearman->addServer();
			
			$paste = DataObject::get_one('Paste', 'Filename = \'' . Convert::raw2SQL($id) . '\'');
			if(!$paste) {
				echo 'Paste not found';
				die();
			}

			$func = $paste->PHPVersion()->FuncName;
			
			$options['path'] = $file;
			$options['headers'] = $this->emu_getallheaders();
			$options['body'] = '';
			
			$result = $gearman->do($func, json_encode($options));
			
			if(!strpos($result, "\r\n\r\n")) {
				// No headers, treat as error response

				if(strpos($result, 'timelimit: sending warning signal 15') !== FALSE) {
					echo "Time limit exceeded.";
				} else {
					echo "Invalid HTTP Response from server, received:\n";
					var_dump($result);
					die();
				}
			}
			
			list($headers, $body) = explode("\r\n\r\n", $result, 2);
			
			foreach(explode("\r\n", $headers) as $header) {
				header($header);
			}
			
			echo $body;
			die();
		} else {
			return FALSE;
		}
	}
	
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();
		
		if(!DataObject::get_one('PHPVersion')) {
			$version = new PHPVersion();
			$version->Title = '5.3-dev';
			$version->FuncName = '53dev';
			$version->IsDefault = true;
			$version->write();
		}
		
		$versions = DataObject::get('PHPVersion');
		
		foreach($versions as $version) {
			if(strtotime($version->LastCompiled) < time() - (86400 * 14)) {
				$version->CompilePHP();
			}
		}
	}
	
	public function CompilePHP() {
		$this->FuncName = preg_replace('/\W+|php/', '', $this->Title);
		$this->Path = '/php/' . $this->FuncName;
		
		$client = new GearmanClient();
		$client->addServer();
		$client->doBackground('CompilePHP', $this->FuncName);
		echo "\n<p><b>Started compiling PHP {$this->Title}</b></p>\n\n";
	}
}
