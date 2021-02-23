<?php

declare(strict_types=1);

namespace MatiCore\Utils;


use Nette\StaticClass;

/**
 * Class Http
 * @package MatiCore\Utils
 */
class Http
{

	use StaticClass;

	public const IP_LOCALHOST = '127.0.0.1';

	/**
	 * @return string
	 */
	public static function userIp(): string
	{
		static $ip = null;

		if ($ip === null) {
			if (isset($_SERVER['REMOTE_ADDR'])) {
				if (\in_array($_SERVER['REMOTE_ADDR'], ['::1', '0.0.0.0', 'localhost'], true)) {
					$ip = self::IP_LOCALHOST;
				} else {
					$ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);

					if ($ip === false) {
						$ip = self::IP_LOCALHOST;
					}
				}
			} else {
				$ip = self::IP_LOCALHOST;
			}
		}

		return $ip;
	}

	/**
	 * Return current absolute URL.
	 * Return null, if current URL does not exist (for example in CLI mode).
	 *
	 * @return string|null
	 */
	public static function getCurrentUrl(): ?string
	{
		if (!isset($_SERVER['REQUEST_URI'], $_SERVER['HTTP_HOST'])) {
			return null;
		}

		return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
			. '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	/**
	 * @return string|null
	 */
	public static function getBaseUrl(): ?string
	{
		static $return;

		if ($return !== null) {
			return $return;
		}

		$currentUrl = self::getCurrentUrl();

		if ($currentUrl !== null) {
			if (preg_match('/^(https?:\/\/.+)\/www\//', $currentUrl, $localUrlParser)) {
				$return = $localUrlParser[0];
			} elseif (preg_match('/^(https?:\/\/[^\/]+)/', $currentUrl, $publicUrlParser)) {
				$return = $publicUrlParser[1];
			}
		}

		if ($return !== null) {
			$return = rtrim($return, '/');
		}

		return $return;
	}

	/**
	 * Funkce pro ziskani DNS podle IP
	 *
	 * Na rozdil od PHP funkce gethostbyaddr() tato funkce podporuje
	 * i nastaveni $timeoutu. Funkce gethostbyaddr() totiz u nekoho
	 * muze zpusobovat i minutove nacitani a celkove lagnuti...
	 *
	 * Kdyz se DNS nepodari nacist, vrati se IP adresa.
	 *
	 * @param string $ip
	 * @param int $timeout
	 * @return string
	 * @throws FunctionException
	 */
	public static function dnsByIp(string $ip, int $timeout = 2): string
	{
		if (!self::isIp($ip)) {
			throw new FunctionException('Input is not valid IP "' . $ip . '".');
		}

		$dns = $ip;

		if (stripos(PHP_OS, 'win') !== false) { // Windows
			$result = `nslookup -timeout={$timeout} {$ip}`;
			if (preg_match('/^Name:[\s]*(\S+)$/im', $result, $matches) !== 0) {
				$dns = $matches[1];
			}
		} elseif (Safe::functionIsAvailable('shell_exec')) { // Linux
			$result = `host -W {$timeout} {$ip}`;
			if (preg_match('/pointer (\S+)\.?\s*$/isU', $result, $matches) !== 0) {
				$dns = $matches[1];
			}
		} else {
			$dns = gethostbyaddr($ip);
		}

		return $dns;
	}

	/**
	 * Metoda overi, jestli je predana IP adresa $ip v rozsahu $subnetWithBitMask
	 * POZOR: tato metoda funguje jenom s IPv4
	 *
	 * @param string $ip (napr. 88.86.101.49)
	 * @param string $subnetWithBitMask (napr. 88.86.101/24)
	 * @return bool
	 * @throws FunctionException
	 */
	public static function ipMatch(string $ip, string $subnetWithBitMask): bool
	{
		if (!self::isIp($ip)) {
			throw new FunctionException('Invalid IP');
		}

		if (strpos($subnetWithBitMask, '/') === false) {
			$subnetWithBitMask .= '/32';
		}

		if (@preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2}\z/', $subnetWithBitMask) !== 1) {
			throw new FunctionException('Invalid subnet with bitmask');
		}

		[$subnet, $bits] = explode('/', $subnetWithBitMask);
		$subnet = \ip2long($subnet);
		$mask = -1 << (32 - (int) $bits);
		$subnet &= $mask;

		return (\ip2long($ip) & $mask) === $subnet;
	}

	/**
	 * Okamzite flushne buffer
	 *
	 * Volani teto funkce se hodi po kazdem echo prikazu, ktereho
	 * vystup chcete na strance zobrazit okamzite a nechcete cekat,
	 * az cely skript dobehne
	 *
	 * Uzitecne hlavne pri skriptech, ktere x-minut neco generuji a
	 * potrebujeme videt progress v prubehu skriptu
	 *
	 * @return void
	 */
	public static function flushNow(): void
	{
		@ob_end_flush();
		@flush();
		@ob_flush();
	}

	/**
	 * @param string $value
	 * @return bool
	 */
	public static function isIp(string $value): bool
	{
		return self::isIpV4($value) || self::isIpV6($value);
	}

	/**
	 * @param string $value
	 * @return bool
	 */
	public static function isIpV4(string $value): bool
	{
		return preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $value) === 1 && ip2long($value) !== false;
	}

	/**
	 * @param string $value
	 * @return bool
	 */
	public static function isIpV6(string $value): bool
	{
		return strpos($value, ':') && filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
	}

	/**
	 * @return int
	 */
	public static function getMaxUploadFileSize(): int
	{
		return (int) min(ini_get('post_max_size'), ini_get('upload_max_filesize'));
	}

	/**
	 * @return string
	 */
	public static function getLang(): string
	{
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			return substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
		}

		return 'cs';
	}

	/**
	 * Convert given link (absolute, relative, broken format) to valid absolute link.
	 * If domain is not specified, use current base URL.
	 *
	 * @param string $link
	 * @return string
	 */
	public static function fixLink(string $link): string
	{
		if (preg_match('/^https?\:\/\//', $link)) {
			return $link;
		}

		// shopup.cz | www.shopup.cz | www.help.shopup.cz
		if (preg_match('/^((?:[\w\-]+)(?:\.[\w\-]+)*\.\w{2,10})(.*)$/', $link, $linkParser)) {
			return 'http://' . $linkParser[1] . rtrim($linkParser[2], '/');
		}

		if (isset($link[0]) && $link[0] !== '/') {
			return self::getBaseUrl() . '/' . $link;
		}

		return self::getBaseUrl() . $link;
	}

	/**
	 * Page not found: set_http_status(404);
	 * Temporary redirect: set_http_status(302, '/new-url/'); or set_http_status(302, 'https://example.com/new-url/');
	 * Persistent redirect: set_http_status(301, '/new-url/'); or set_http_status(302, 'https://example.com/new-url/');
	 * Page temporary not found:
	 *    set_http_status(503, date('Y-m-d H:i:s', strtotime('+5 minutes')));
	 *    set_http_status(503, '2012-01-06 18:00:00'); // If you know when page will be stable (example 18:00)
	 * Forbidden: set_http_status(403);
	 * Bad request: set_http_status(400);
	 *
	 * @param int $statusCode
	 * @param string|null $location New URL to redirect (header 30x only)
	 * @param string|null $retryAfter YYYY-MM-DD HH:MM:SS date for crawler when page will be stable (header 503 only)
	 * @throws FunctionException
	 */
	public static function setHttpStatus(int $statusCode, string $location = null, string $retryAfter = null): void
	{
		if ($statusCode < 100 || $statusCode > 505) {
			throw new FunctionException('Invalid status code (valid range 100 - 505)');
		}

		if ($location !== null && ($statusCode < 300 || $statusCode > 307)) {
			throw new FunctionException('Location use only with status codes from 300 to 307');
		}

		if ($retryAfter !== null && $statusCode !== 503) {
			throw new FunctionException('Retry-After use only with status codes 503');
		}

		if ($retryAfter && !@strtotime($retryAfter)) {
			throw new FunctionException('Retry-After date must be compatible with strtotime() function');
		}

		if (\in_array($statusCode, [301, 302], true) && (!\is_string($location) || trim($location) === '')) {
			throw new FunctionException(
				'Status code #' . $statusCode . ' must be defined with $location for redirect'
			);
		}

		if (headers_sent($file, $line)) {
			FunctionException::headersWasAlreadySend($file, $line, $statusCode);
		}

		$headers = [
			100 => 'HTTP/1.1 100 Continue',
			101 => 'HTTP/1.1 101 Switching Protocols',
			200 => 'HTTP/1.1 200 OK',
			201 => 'HTTP/1.1 201 Created',
			202 => 'HTTP/1.1 202 Accepted',
			203 => 'HTTP/1.1 203 Non-Authoritative Information',
			204 => 'HTTP/1.1 204 No Content',
			205 => 'HTTP/1.1 205 Reset Content',
			206 => 'HTTP/1.1 206 Partial Content',
			300 => 'HTTP/1.1 300 Multiple Choices',
			301 => 'HTTP/1.1 301 Moved Permanently',
			302 => 'HTTP/1.1 302 Found',
			303 => 'HTTP/1.1 303 See Other',
			304 => 'HTTP/1.1 304 Not Modified',
			305 => 'HTTP/1.1 305 Use Proxy',
			307 => 'HTTP/1.1 307 Temporary Redirect',
			400 => 'HTTP/1.1 400 Bad Request',
			401 => 'HTTP/1.1 401 Unauthorized',
			402 => 'HTTP/1.1 402 Payment Required',
			403 => 'HTTP/1.1 403 Forbidden',
			404 => 'HTTP/1.1 404 Not Found',
			405 => 'HTTP/1.1 405 Method Not Allowed',
			406 => 'HTTP/1.1 406 Not Acceptable',
			407 => 'HTTP/1.1 407 Proxy Authentication Required',
			408 => 'HTTP/1.1 408 Request Timeout',
			409 => 'HTTP/1.1 409 Conflict',
			410 => 'HTTP/1.1 410 Gone',
			411 => 'HTTP/1.1 411 Length Required',
			412 => 'HTTP/1.1 412 Precondition Failed',
			413 => 'HTTP/1.1 413 Request Entity Too Large',
			414 => 'HTTP/1.1 414 Request-URI Too Long',
			415 => 'HTTP/1.1 415 Unsupported Media Type',
			416 => 'HTTP/1.1 416 Requested Range Not Satisfiable',
			417 => 'HTTP/1.1 417 Expectation Failed',
			500 => 'HTTP/1.1 500 Internal Server Error',
			501 => 'HTTP/1.1 501 Not Implemented',
			502 => 'HTTP/1.1 502 Bad Gateway',
			503 => 'HTTP/1.1 503 Service Unavailable',
			504 => 'HTTP/1.1 504 Gateway Timeout',
			505 => 'HTTP/1.1 505 HTTP Version Not Supported',
		];

		if (isset($headers[$statusCode])) {
			header($headers[$statusCode]);

			if ($statusCode === 503) {
				header('Status: 503 Service Temporarily Unavailable');
			}

			if ($location) {
				header('Location: ' . trim((string) preg_replace('/\s+/', '%20', $location)));
			}

			if ($retryAfter) {
				header('Retry-After: ' . @gmdate('D, d M Y H:i:s', (int) strtotime($retryAfter)) . ' GMT');
			}
		} else {
			throw new FunctionException(
				'Invalid status code #' . $statusCode . ': '
				. 'check HTTP specification: http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html'
			);
		}
	}

	/**
	 * Detect if current request was called by cURL.
	 *
	 * @return bool
	 */
	public static function isCurlRequest(): bool
	{
		return isset($_SERVER['HTTP_USER_AGENT']) && \stripos($_SERVER['HTTP_USER_AGENT'], 'curl') !== false;
	}

	/**
	 * @return bool
	 */
	public static function isCliRequest(): bool
	{
		return PHP_SAPI === 'cli';
	}

	/**
	 * @return bool
	 */
	public static function isAJaxRequest(): bool
	{
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
	}

	/**
	 * @return bool
	 */
	public static function isRobot(): bool
	{
		static $isBot;
		static $botUserAgents = [
			'ahrefsbot',
			'baiduspider',
			'bingbot',
			'blexbot',
			'dotbot',
			'googlebot',
			'grapeshot',
			'mediatoolkitbot',
			'mj12bot',
			'phpcrawl',
			'proximic',
			'semrushbot',
			'seoscanners',
			'seznambot',
			'slurp',
			'spbot',
			'velenpublicwebcrawler',
			'xenu',
			'yandex',
		];

		if ($isBot === null) {
			$userAgent = strtolower(trim($_SERVER['HTTP_USER_AGENT'] ?? ''));
			$isBot = false;

			if ($userAgent !== '') {
				foreach ($botUserAgents as $agent) {
					if (strpos($userAgent, $agent) !== false) {
						$isBot = true;
						break;
					}
				}
			}
		}

		return $isBot;
	}

}