<?php
if(!file_exists('lib/PHP_Compiler.php')) {
	set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__));
}

include 'lib/PHP_Downloader.php';
include 'lib/PHP_Compiler.php';
include 'lib/PHP_Jailer.php';



if(isset($argv[1])) {
	
	// Command line compiler
	
	$result = CompilePHP($argv[1], TRUE);
	print_r(json_decode($result));
	
} else {
	
	// Gearman worker
	
	$worker = new GearmanWorker();
	$worker->addServer();
	$worker->addFunction('CompilePHP', 'CompilePHP');
	while($worker->work()) { usleep(100000); }
	
}



function CompilePHP($version, $debug=true) {
	$downloader = new PHP_Downloader();
	$compiler = new PHP_Compiler();
	$jailer = new PHP_Jailer();
	
	if(is_object($version) && is_a($version, 'GearmanJob')) {
		$version = $version->workload();
	}
	
	$jailer->debug = $downloader->debug = $compiler->debug = $debug;
	
	try {
		$info = $downloader->Download($version);
	} catch(Exception $e) {
		$result = array('result' => 'ERROR', 'type' => 'DOWNLOAD', 'message' => $e->getMessage());
		return json_encode($result);
	}
	
	$deploy_path = "/php/{$info['version']}";
	
	$options = array();
	$options[] = "with-config-file-path={$deploy_path}/etc";
	$options[] = "prefix={$deploy_path}";
	$options[] = "with-layout=GNU";
	$options[] = "enable-force-cgi-redirect";
	$options[] = "enable-discard-path";
	$options[] = "enable-mbstring";
	$options[] = "enable-calendar";
	$options[] = "enable-bcmath";
	$options[] = "enable-pdo";
	$options[] = "enable-sockets";
	$options[] = "enable-soap";
	$options[] = "with-mysql";
	$options[] = "with-regex=php";
	$options[] = "with-curl";
	$options[] = "with-gd";
	$options[] = "with-mcrypt";
	$options[] = "with-jpeg-dir";
	$options[] = "with-png-dir";
	$options[] = "with-zlib";
	$options[] = "with-tidy";
	$options[] = "with-mysqli";
	$options[] = "with-pdo-mysql";
	$options[] = "with-pdo-sqlite";
	$options[] = "with-gettext";
	$options[] = "with-sqlite";
	$options[] = "with-openssl";
//	$options[] = "with-imap";
//	$options[] = "with-imap-ssl";
//	$options[] = "with-kerberos";

	try {
		$build_log = $compiler->Compile($info['archive'], $options, $info['version']);
	} catch(Exception $e) {
		$result = array('result' => 'ERROR', 'type' => 'COMPILE', 'message' => $e->getMessage());
		return json_encode($result);
	}
	
	if($debug) echo "Updating Database for {$version}\n";

	$db = new PDO('mysql:host=127.0.0.1;dbname=Codepad;charset=utf8', 'root', '');
	$stmt = $db->prepare('UPDATE PHPVersion SET LastCompiled = NOW(), FuncName = ?, Path = ? WHERE FuncName = ?');
	$stmt->execute(array($info['version'], $deploy_path, trim($version)));

	if($debug) echo "Updated {$stmt->rowCount()} rows\n";
	if($debug) echo "Installed {$info['version']} to {$deploy_path}\n";

	try {
		$jailer->Deploy($deploy_path);
	} catch(Exception $e) {
		$result = array('result' => 'ERROR', 'type' => 'JAIL', 'message' => $e->getMessage());
		return json_encode($result);
	}
	
	if($debug) echo "Build Complete\n";

	$result = array('result' => 'OK', 'type' => 'SUCCESS', 'message' => $build_log);
	return json_encode($result);
}
