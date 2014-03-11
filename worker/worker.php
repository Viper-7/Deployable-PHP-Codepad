<?php
$worker = new GearmanWorker();
$worker->addServer();
$db = new PDO('mysql:host=127.0.0.1;dbname=Codepad;charset=utf8', 'jailed', 'jailed');
$stmt = $db->prepare('SELECT FuncName, Path FROM PHPVersion WHERE LastCompiled IS NOT NULL');
$stmt->execute();

$func = function($job) {
        $input = $job->workload();
        $params = (array)json_decode($input);
        $script_path = $params['path'];
        $script = basename($params['path']);
        $version = isset($params['version']) ? $params['version'] : '5.3.10';

        $content = shell_exec('/php/'.$version.'/bin/php -dvld.active=1 -dvld.execute=0 2>&1 ' . $script_path);

        return json_encode(array('headers' => array(), 'body' => base64_encode($content)));
};

$worker->addFunction('VLD', $func);

foreach($stmt->fetchAll() as $row) {
        $path = "{$row['Path']}";

        $func = function($job) use ($path) {
                $input = $job->workload();
                $params = (array)json_decode($input);
                $script_path = $params['path'];
                $script = basename($params['path']);

                echo "Executing " . basename($script) . "...";

                $params['headers'] = (array)$params['headers'];

                if(empty($params['method']))
                        $params['method'] = 'GET';

                foreach($params['headers'] as $header => $value) {
                        if(strtolower($header) == 'content-type') $params['content_type'] = $value;
                        if(strtolower($header) == 'content-length') $params['content_length'] = $value;

                        $clean = 'HTTP_' . strtoupper(preg_replace('/\W+/', '_', $header));
                        $env[$clean] = $value;
                }

                $params['input'] = $params['body'];

                $exports = '';
                $env['PATH_INFO'] = '';
                $env['PATH_TRANSLATED'] = $script_path;
                $env['SCRIPT_FILENAME'] = $script_path;
                $env['SCRIPT_NAME'] = $script_path;
                $env['QUERY_STRING'] = $params['query_string'];
                $env['REQUEST_METHOD'] = $params['method'];
                $env['REDIRECT_STATUS'] = '200';
                $env['CONTENT_TYPE'] = $params['content_type'];
                $env['CONTENT_LENGTH'] = $params['content_length'];
                $env['DOCUMENT_ROOT'] = dirname($script_path);
                $env['SERVER_SOFTWARE'] = 'Viper-7 Codepad 1.0';
                $env['SERVER_NAME'] = 'codepad';
                $env['GATEWAY_INTERFACE'] = 'CGI/1.1';


                foreach($env as $name => $value) {
                        $exports .= "export {$name}=\"{$value}\" ; ";
                }

                $limits = "/bin/bash -c ulimit -c 1024000 -e 19 -f 10 -t 20 -u 10 -x 5; {$exports} timelimit -T 1 -t 60 -p -q";
                //$limits = $exports;

                $cmd = "{$limits} {$path}/bin/php-cgi";

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

                        if(trim($line = fgets($pipes[1])) != 'unlimited') {
                                $content = $line;
                        } else {
                                $content = '';
                        }

                        $content .= stream_get_contents($pipes[1]);
                        $errors = stream_get_contents($pipes[2]);

if(strpos($content, "\r\n\r\n") !== false) {
        list($headers,$body) = explode("\r\n\r\n", $content, 2);
        $content = $body . "\r\n" . $errors;
} else {
        $headers = '';
}

                        fclose($pipes[1]);
                        fclose($pipes[2]);
                        $retval = proc_close($process);

                        echo " done!\n";

echo "=====================================================================================\n";
echo "                                   {$script}\n";
echo "                                 {$path}\n";
echo "    {$cmd}\n";
echo "=====================================================================================\n";
echo "                                    Input\n";
echo "=====================================================================================\n";
echo file_get_contents($script_path, false, null, 0, 1000). "\n";
echo "=====================================================================================\n";
echo "                                    Output\n";
echo "=====================================================================================\n";
echo substr($content, 0, 1000) . $errors . "\n";
if(strlen($content) > 1000) { echo "...\n"; }
echo "=====================================================================================\n";

                        return json_encode(array('headers' => base64_encode($headers), 'body' => base64_encode($content), 'errors' => $errors));
                } else {
                        echo " failed!\n";

                        return json_encode(array('errors' => '<div class="error">Failed to open process</div>'));
                }
        };

        echo "Registered {$row['FuncName']}\n";
        $worker->addFunction($row['FuncName'], $func);
}

while($worker->work());
?>

