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
use Symfony\Component\Yaml\Yaml;
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
	if (!file_exists($arguments["data"] . "/config.yml")) {
		throw new UserException(UserException::ERR_MISSING_CONFIG);
	}

	$config = Yaml::parse(file_get_contents($arguments["data"] . "/config.yml"));

	$action = isset($config['action']) ? $config['action'] : $action;

	if ($action !== 'run') {
		$logger->setHandlers(array(new NullHandler(Logger::INFO)));
	}

	if ($action === 'generate') {
		$privateKeyFile = ROOT_PATH . '/data/out/ssh.key';
		$publicKeyFile = $privateKeyFile . '.pub';

		$process = new Process("ssh-keygen -b 2048 -t rsa -f " . $privateKeyFile . " -N '' -q");

		$process->mustRun();

		$privateKey = file_get_contents($privateKeyFile);
		$publicKey = file_get_contents($publicKeyFile);

		unlink($privateKeyFile);
		unlink($publicKeyFile);

		echo json_encode([
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
