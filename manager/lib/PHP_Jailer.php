<?php
class PHP_Jailer {
	public $debug = 0;
	public $jailRoot = '/opt/codepad';
	public $jailkitPath = '/usr/sbin';
	public $jailUser = 'jailexec';
	public $webUser = 'www-data';
	
	public function Deploy($phpBase) {
		if(!file_exists($this->jailRoot)) {
			if($this->debug) echo "Creating Jail {$this->jailRoot}\n";
			try {
				$this->buildJail($this->jailRoot);
			} catch(Exception $e) {
				throw new Exception('Failed to create jail: ' . $e->getMessage());
			}
		}
		
		if(!file_exists("{$this->jailRoot}/bin/bash")) {
			if($this->debug) echo "Initializing jail\n";

			$cmd = "{$this->jailkitPath}/jk_init -j {$this->jailRoot} netutils basicshell jk_lsh openvpn 2>&1";
			exec($cmd, $init_log, $return);
			
			if($return) { // Not 0 = Failure
				throw new Exception('Failed to initialize jail: ' . implode("\n", $init_log));
			}
			
			$passwd = file_get_contents("{$this->jailRoot}/etc/passwd");
			if(strpos($passwd, $this->jailUser) === FALSE) {

				$passwd = file_get_contents("/etc/passwd");
				if(strpos($passwd, $this->jailUser) === FALSE) {
					if($this->debug) echo "Adding User\n";

					$cmd = "useradd -NMd {$this->jailRoot}/ {$this->jailUser}";
					exec($cmd, $user_log, $return);
					
					if($return) { // Not 0 = Failure
						throw new Exception('Failed to create user: ' . implode("\n", $user_log));
					}
				}
				
				if($this->debug) echo "Importing user into jail\n";
				$cmd = "{$this->jailkitPath}/jk_jailuser -j {$this->jailRoot} {$this->jailUser}";
				exec($cmd, $jailuser_log, $return);
				
				if($return) { // Not 0 = Failure
					throw new Exception('Failed to jail user: ' . implode("\n", $jailuser_log));
				}
			}
			
			if(!file_exists($tmp = "{$this->jailRoot}/tmp")) {
				if($this->debug) echo "Creating /tmp folder\n";
				mkdir($tmp);
				exec("chown {$this->webUser}:{$this->jailUser} {$tmp}");
				chmod($tmp, 0775);
			}
		}
		
		if($this->debug) echo "Deploying {$phpBase} to {$this->jailRoot}\n";
		$cmd = "{$this->jailkitPath}/jk_cp -j {$this->jailRoot} {$phpBase} 2>&1";
		exec($cmd, $jail_log, $return);
		
		if($return) { // Not 0 = Failure
			throw new Exception('Failed to install PHP into jail: ' . implode("\n", $jail_log));
		}
		
		return $jail_log;
	}
}
