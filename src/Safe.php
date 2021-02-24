<?php

declare(strict_types=1);

namespace MatiCore\Utils;


use Nette\StaticClass;

/**
 * Class Safe
 * @package MatiCore\Utils
 */
class Safe
{

	use StaticClass;

	/**
	 * @return bool
	 */
	public static function isLocalhost(): bool
	{
		static $is;

		if ($is === null) {
			if (Http::userIp() === Http::IP_LOCALHOST) {
				return $is = true;
			}

			$url = Http::getCurrentUrl();

			if ($url !== null) {
				$localHosts = ['localhost', '[^\/]+\.l', '127\.0\.0\.1'];
				$allowedPorts = ['80', '443', '3000'];

				$is = (bool) preg_match(
					'/^https?:\/\/(' . implode('|', $localHosts) . ')(\:(?:' . implode('|', $allowedPorts) . '))?(?:\/|$)/',
					$url
				);
			} else {
				$is = false;
			}
		}

		return $is;
	}

	/**
	 * Predany e-mail zakoduje do HEXa (cemu zase rozumi prohlizec, ale uz mene spamboti)
	 *
	 * Pomoci $replaceAtSign muzete nastavit, za jaky znak/znaky se nahradit zavinac
	 * Muzete tam nastavit napr. "^" a pri vypise adresy v JS treba "^" nahradi za zavinac,
	 * takze e-mailovou adresu z HTML zdrojaku neodhali ani chytrejsi robot, ktery sice
	 * umi i parsovat i hexa, ale bude ocekavat zavinac.
	 *
	 * Interne je implementovana i mini cache, pro pripad, ze by se v jednom skriptu
	 * prevadelo nekolik stejnych adres.
	 *
	 * @param string $email
	 * @param string $replaceAtSign
	 * @return string
	 */
	public static function hexEmailEncode(string $email, string $replaceAtSign = '@'): string
	{
		static $cache = [];
		$cacheKey = $email . $replaceAtSign;
		if (isset($cache[$cacheKey])) {
			return $cache[$cacheKey];
		}

		$encoded = '';
		for ($x = 0, $mailLength = \strlen($email); $x < $mailLength; $x++) {
			if (preg_match('!\w!u', $email[$x])) {
				$encoded .= '%' . bin2hex($email[$x]);
			} else {
				$encoded .= $email[$x];
			}
		}

		$result = $encoded;
		$result = str_replace('@', $replaceAtSign, $result);

		$cache[$cacheKey] = $result;

		return $result;
	}

	/**
	 * @param string $functionName
	 * @return bool
	 */
	public static function functionIsAvailable(string $functionName): bool
	{
		static $disabled;

		if (\function_exists($functionName)) {
			if ($disabled === null) {
				$disableFunctions = ini_get('disable_functions');

				if (\is_string($disableFunctions)) {
					$disabled = explode(',', $disableFunctions);
				}
			}

			return \in_array($functionName, $disabled, true) === false;
		}

		return false;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public static function getIntegrityHash(string $path): string
	{
		$content = is_file($path) && ($file = file_get_contents($path))
			? hash('sha384', $file, true)
			: null;

		return 'sha384-' . base64_encode($content ? : 'error');
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public static function getFileHash(string $path): string
	{
		return is_file($path) && ($md5 = md5_file($path))
			? substr($md5, 0, 8)
			: md5($path);
	}

}