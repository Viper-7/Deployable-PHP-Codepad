<?php
class PHPVersion extends DataObject {
        public static $db = array(
			'Title' => 'Varchar(255)',
			'Path' => 'Varchar(4000)',
			'FuncName' => 'Varchar(255)',
			'LastCompiled' => 'Datetime',
			'IsDefault' => 'Boolean',
        );

		public function isCurrent() {
			$paste = Controller::curr()->getCurrentPaste();
			$id = $paste->PHPVersionID;
			if(!$id) {
				$version = DataObject::get_one('PHPVersion', 'IsDefault=1');
				$id = $version->ID;
			}
			
			return $this->ID == $id;
		}
		
        public function executePaste($id, $view='', $ini='development') {
			$paste_dir = '/code';
			$jail_dir = '/opt/codepad';
			
			if(!$view)
				$view = 'html_body';
				
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
				
				$query = $_GET;
				unset($query['url']);
				$query = http_build_query($query);

				$options['query_string'] = $query;
				$options['method'] = $_SERVER['REQUEST_METHOD'];
				
				// Attempt 1 of 3 - Fetch request body from STDIN via php://input
				$options['body'] = file_get_contents('php://input');
				
				if($_SERVER['CONTENT_LENGTH'] > 0 && !$options['body']) {
					// Attempt 2 of 3 - Fetch request body via PECL_HTTP extension
					$options['body'] = http_get_request_body();
				
					// Attempt 3 of 3 - Reconstruct request body from $_POST and $_FILES
					if(!$options['body']) {
						list($mime, $boundary) = explode('boundary=', $_SERVER['CONTENT_TYPE']) + array('','');
						
						if($boundary) {
							foreach($_POST as $name => $value) {
								$options['body'] .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"{$name}\"\r\n\r\n{$value}\r\n";
							}
							
							foreach($_FILES as $name => $file) {
								$content = '||BASE64-BINARY||' . base64_encode(file_get_contents($file['tmp_name'])) . '||BASE64-BINARY||';
								$type = $file['type'];
								$length = $file['size'];
								$origname = $file['name'];
								
								$options['body'] .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"{$name}\"; filename=\"{$origname}\"\r\nContent-Type: {$type}\r\nContent-Length: {$length}\r\nContent-Transfer-Encoding: binary\r\n\r\n{$content}\r\n";
							}
							
							$options['body'] .= "--{$boundary}";
						}
					}
				}
				
				$options['ini'] = $ini;

				switch($view) {
					case 'request_headers':
						echo '<pre>';
						foreach($options['headers'] as $header => $value) {
							echo "$header: $value\r\n";
						}
						echo '</pre>';
						die();
					
					case 'request_body':
						echo $options['body'];
						die();

					case 'opcodes':
						$func = 'VLD';
//						$options['version'] = $paste->PHPVersion()->Path;
						break;
						
					case 'profile':
						echo 'Not yet implemented';
						die();
						
					case 'call_graph':
						echo 'Not yet implemented';
						die();
				}
				$res = $gearman->do($func, json_encode($options));
				$result = (array)json_decode($res);

				if(empty($result['headers'])) {
					// No headers, treat as error response

					if(!empty($result['errors'])) {
						echo $result['errors'];
						die();
					}
					if(strpos($result['body'], 'timelimit: sending warning signal 15') !== FALSE) {
var_dump($result);
						echo "Time limit exceeded.";
					} elseif($view != 'opcodes') {
						echo "Invalid HTTP Response from server, received:<br/>\n<pre>";
						var_dump($res);
echo "</pre>";
						die();
					}
				}

				$headers = base64_decode($result['headers']);
				$body = base64_decode($result['body']);

				switch($view) {
					case 'html_body':
						foreach(explode("\r\n", $headers) as $header) {
								header($header);
						}
						
						echo $body;
						die();
					
					case 'response_body':
						echo "<pre>{$body}</pre>";
						die();
					
					case 'response_headers':
						echo "<pre>{$headers}</pre>";
						die();
					
					case 'opcodes':
						echo "<pre>{$body}</pre>";
						die();
						
					case 'profile':
						echo 'Not yet implemented';
						die();
						
					case 'call_graph':
						echo 'Not yet implemented';
						die();

					default:
						echo 'Unknown view type';
						die();
				}
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
				if(strtotime($version->LastCompiled) < time() - (86400 * 21)) {
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
		
		function emu_getallheaders() {
			foreach($_SERVER as $name => $value) {
				if(substr($name, 0, 5) == 'HTTP_' || in_array($name, array('CONTENT_TYPE', 'CONTENT_LENGTH'))) {
					$name = str_replace(' ', '-', ucwords(strtolower(str_replace(array('HTTP_', '_'), array('', ' '), $name))));
					$headers[$name] = $value;
				}
			}
			return $headers;
        }
}
