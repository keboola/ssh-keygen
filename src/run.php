<?php
/**
 * @package ssh-keygen
 * @author Erik Zigo <erik.zigo@keboola.com>
 */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\NullHandler;
use Symfony\Component\Process\Process;
use Keboola\SSH\Keygen\Exception\UserException;

define('APP_NAME', 'ssh-keygen');

require_once(__DIR__ . "/../bootstrap.php");


$logger = new Logger(APP_NAME, array(
	(new StreamHandler('php://stdout', Logger::INFO))->setFormatter(new LineFormatter("%message%\n")),
	(new StreamHandler('php://stderr', Logger::ERROR))->setFormatter(new LineFormatter("%message%\n")),
));

$action = 'run';

try {
	$arguments = getopt("d::", ["data::"]);
	if (!isset($arguments["data"])) {
		throw new UserException(UserException::ERR_DATA_PARAM);
	}
	if (!file_exists($arguments["data"] . "/config.json")) {
		throw new UserException(UserException::ERR_MISSING_CONFIG);
	}

	$config = json_decode(file_get_contents($arguments["data"] . "/config.json"), true);

	$action = isset($config['action']) ? $config['action'] : $action;

	if ($action !== 'run') {
		$logger->setHandlers(array(new NullHandler(Logger::INFO)));
	}

	if ($action === 'generate') {
		$keyPath = $arguments["data"] . '/out';
		if (!is_dir($keyPath)) {
			mkdir($keyPath, 0700, true);
		}

		$privateKeyFile = $keyPath . '/ssh.key';
		$publicKeyFile = $privateKeyFile . '.pub';

		$process = new Process("ssh-keygen -b 4096 -t rsa -f " . $privateKeyFile . " -N '' -q");

		$process->mustRun();

		$privateKey = file_get_contents($privateKeyFile);
		$publicKey = file_get_contents($publicKeyFile);

		unlink($privateKeyFile);
		unlink($publicKeyFile);

		echo json_encode([
			'status' => 'success',
			'private' => $privateKey,
			'public' => $publicKey
		]);
	}

	exit(0);
} catch (UserException $e) {
	$logger->log('error', $e->getMessage(), array());

	if ($action !== 'run') {
		echo $e->getMessage();
	}

	exit(1);
} catch (\Exception $e) {
	print $e->getMessage();
	print $e->getTraceAsString();

	exit(2);
}
