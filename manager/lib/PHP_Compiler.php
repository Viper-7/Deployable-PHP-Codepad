<?php
class PHP_Compiler {
	public $debug = 0;
	
	public function Compile($file, $options, $version) {
		$basepath = "/tmp/php-{$version}";
		$gz = "{$basepath}/{$version}.tar.bz2";

		if(file_exists($basepath)) {
			$this->Deltree($basepath);
		}

		mkdir($basepath);
		if(rename($file, $gz)) {
			if($this->debug) 
				echo "Moved archive to {$gz}\n";
		} else {
			throw new Exception("Archive {$gz} not found!");
		}

		chdir($basepath);
		$tar_log = shell_exec("tar -xvBf {$gz} -C {$basepath} 2>&1");
		unlink($gz);

		$dir = $basepath;
		while(!file_exists("{$dir}/configure")) {
			foreach(glob("$dir/*", GLOB_ONLYDIR) as $dir) {
				if(!in_array($dir, array('.', '..'))) {
					continue 2;
				}
			}

			throw new Exception('Failed to find php build folder');
		}

		if($this->debug)
			echo "Building {$version} in {$dir}\n";
		chdir($dir);

		$cmd = "  ./configure \\\n    --" . implode(" \\\n    --", $options) . "\n  2>&1";
		if($this->debug)
			echo "Configuring with: \n{$cmd}\n...";
		$config_log = shell_exec($cmd);

		if(strpos($config_log, 'creating main/php_config.h') === FALSE) {
			throw new Exception("Failed to configure PHP\n\nLine:\n{$cmd}\n\nLog:\n{$config_log}");
		}

		if($this->debug)
			echo "\nCompiling\n...";
		$build_log = shell_exec('make install 2>&1');

		if(strpos($build_log, 'Installing PHP CGI binary') === FALSE) {
			throw new Exception("Failed to build PHP\n\nLog:\n{$build_log}");
		}

		chdir(dirname(__FILE__));

		if($this->debug)
			echo "\nCleaning Up\n";
		$this->cleanup($version);

		if($this->debug)
			echo "Finished compiling php-{$version}.\n";

		return $config_log . "\n" . $build_log;
	}

	public function Cleanup($version) {
		$basepath = "/tmp/php-{$version}";
		$this->Deltree($basepath);
	}

	protected function Deltree($dir) {
		$files = scandir($dir);
		foreach( $files as $file ){
			if( in_array( $file, array('.','..') ) ) continue;

			$file = "$dir/$file";

			if( is_dir( $file ) )
				$this->delTree( $file );
			else
				unlink( $file );
		}

		if (is_dir($dir)) rmdir( $dir );
	}
}
