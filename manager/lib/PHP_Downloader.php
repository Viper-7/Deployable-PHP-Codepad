<?php
class PHP_Downloader {
	public $debug = 0;

	public function Download($version) {
		$target = tempnam(sys_get_temp_dir(), 'download.');
		if(!preg_replace('/\W+|php/','',$version)) throw new Exception('Invalid Version');

		if(strpos($version, 'dev') !== FALSE) {
			$match = $this->getSnapURL($version);
		} else {
			$match = $this->getLiveURL($version);
			
			if(empty($match['url'])) {
				$match = $this->getArchiveURL($version);
			}
		}
		
		if($this->debug)
			echo "Fetching {$match['url']}\n...";
		copy($match['url'], $target);

		if($this->debug)
			echo "\nStored {$match['version']} archive as {$target}\n";
		$match['archive'] = $target;
		
		return $match;
	}

	protected function getLiveURL($version) {
		$dom = new DOMDocument();
		$dom->loadHTMLFile('http://nl3.php.net/downloads');
		$xpath = new DOMXPath($dom);

		foreach($xpath->query('//div[@id="content"]/ul/li/a/@href') as $href) {
			$url = $href->value;
			if(strpos($url, '.bz2') === FALSE)
				continue;

			$ver = str_replace(array('.tar.bz2/from/a/mirror', '/get/php-'), array(), $href->value);

			$url = str_replace('/from/a/', '/from/this/', $url);
			
			$urls[$ver] = "http://nl3.php.net{$url}";
		}

		if(isset($urls[$version])) {
			return array(
				'version' => $version,
				'url' => $urls[$version]
			);
		}
		if(!$urls) $urls = array();
		
		krsort($urls);

		foreach($urls as $ver => $url) {
			$urlver = strtolower(preg_replace('/\W+/','',$ver));
			$userver = strtolower(preg_replace('/\W+|php/','',$version));
			
			if($this->debug)
				echo "Comparing \"{$userver}\" to release {$urlver}\n";
			
			if(strpos($urlver, $userver) !== FALSE) {
				return array(
					'version' => $ver,
					'url' => $url
				);
			}
		}

		if($this->debug)
			echo "Could not find any current match, aborting.\n";

		return FALSE;
	}
	
	protected function getSnapURL($version) {
		$version = preg_replace('/\W+|php|dev/','',$version);
		
		return array(
			'version' => "{$version}dev",
			'url' => "http://snaps.php.net/?{$version}"
		);
	}

	protected function getArchiveURL($version) {
		throw new Exception("Downloading archive versions is not supported... yet");
		// http://www.php.net/releases/index.php?serialize=1&version=5&max=100
	}
}

