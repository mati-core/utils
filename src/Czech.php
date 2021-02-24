<?php

declare(strict_types=1);

namespace MatiCore\Utils;


use Nette\StaticClass;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;

class Czech
{

	use StaticClass;

	/**
	 * Format number and string by count of items by czech grammar.
	 *
	 * inflection($count, ['zájezd', 'zájezdy', 'zájezdů']) => 1 zájezd, 3 zájezdy, 24 zájezdů
	 *
	 * @param int $number
	 * @param string[] $parameters
	 * @return string
	 * @throws FunctionException
	 */
	public static function inflection(int $number, array $parameters): string
	{
		$numberTxt = number_format($number, 0, '.', ' ');
		$parameters = Safe::strictScalarType($parameters);

		if (!isset($parameters[0], $parameters[1], $parameters[2])) {
			throw new FunctionException(
				'Parameter [0, 1, 2] does not set. Given: ["' . implode('", "', $parameters) . '"].'
			);
		}

		[$for1, $for234, $forOthers] = $parameters;

		if (!$number) {
			$result = '0 ' . $forOthers;
		} elseif ($number === 1) {
			$result = '1 ' . $for1;
		} elseif ($number >= 2 && $number <= 4) {
			$result = $numberTxt . ' ' . $for234;
		} else {
			$result = $numberTxt . ' ' . $forOthers;
		}

		return $result;
	}

	/**
	 * Format phone number to user-friendly format.
	 * Examples:
	 *  +420777123456  -> +420 777 123 456
	 *  00420777123456 -> +420 777 123 456
	 *  777123456      -> +420 777 123 456
	 *
	 * @param string $phoneNumber
	 * @param bool|null $addNoBreakSpace
	 * @return string
	 */
	public static function formatPhoneNumber(string $phoneNumber, ?bool $addNoBreakSpace = null): string
	{
		$result = null;
		$phoneNumber = (string) preg_replace('/[^0-9\+]+/', '', $phoneNumber);

		$prefix = null;
		if (preg_match('/^\d{9}$/', $phoneNumber)) {
			$prefix = '+420 ';
		} elseif (strncmp($phoneNumber, '+', 1) === 0) {
			$prefix = '+';
			$phoneNumber = trim($phoneNumber, '+');
		} elseif (strncmp($phoneNumber, '00', 2) === 0) {
			$prefix = '+';
			$phoneNumber = ltrim($phoneNumber, '0');
		} else {
			$prefix = '';
		}

		$result = $prefix . implode(' ', str_split($phoneNumber, 3));

		if ($addNoBreakSpace === true) {
			$result = str_replace(' ', '&nbsp;', $result);
		}

		return $result;
	}

	/**
	 * Return "6. září 2018"
	 *
	 * @param string|int|\DateTime $date
	 * @param bool $singular (true => "5. květen 2018", false => "5. května 2018")
	 * @return string
	 */
	public static function getDate($date = null, bool $singular = false): string
	{
		if ($date === null) {
			$time = \time();
		} elseif ($date instanceof \DateTime) {
			$time = $date->getTimestamp();
		} else {
			$time = is_numeric($date) ? $date : @strtotime($date);
		}

		$months = [
			'ledna', 'února', 'března', 'dubna', 'května',
			'června', 'července', 'srpna', 'září', 'října',
			'listopadu', 'prosince',
		];

		$singularMonths = [
			'leden', 'únor', 'březen', 'duben', 'květen',
			'červen', 'červenec', 'srpen', 'září', 'říjen',
			'listopad', 'prosinec',
		];

		[$day, $month, $year] = explode('-', date('j-n-Y', (int) $time));

		return $day . '. ' . ($singular === true
				? $singularMonths[(int) $month - 1]
				: $months[(int) $month - 1]
			) . ' ' . $year;
	}

	/**
	 * Validate czech "born number" (originally "Rodné číslo").
	 * Function does not check if number really exists, validate only format.
	 *
	 * @see https://phpfashion.com/jak-overit-platne-ic-a-rodne-cislo
	 * @param string $rc
	 * @return bool
	 */
	public static function isIdentificationNumber(string $rc): bool
	{
		// "be liberal in what you receive"
		if (!preg_match('#^\s*(\d\d)(\d\d)(\d\d)[ /]*(\d\d\d)(\d?)\s*$#', $rc, $matches)) {
			return false;
		}

		[, $year, $month, $day, $ext, $c] = $matches;

		// 9 digits numbers to year 1954 can not check
		if ($c === '') {
			return $year < 54;
		}

		// control digits
		$mod = ($year . $month . $day . $ext) % 11;
		if ($mod === 10) {
			$mod = 0;
		}
		if ($mod !== (int) $c) {
			return false;
		}

		// control data
		$year += $year < 54 ? 2000 : 1900;

		// in month can be added 20, 50 or 70
		if ($year > 2003) {
			if ($month > 70) {
				$month -= 70;
			} elseif ($month > 20) {
				$month -= 20;
			}
		} elseif ($month > 50) {
			$month -= 50;
		}

		return checkdate((int) $month, (int) $day, (int) $year);
	}

	/**
	 * Validate czech company ID (originally "IČ" or "IČO").
	 * Function does not check if ID really exists, validate only format.
	 *
	 * @see https://phpfashion.com/jak-overit-platne-ic-a-rodne-cislo
	 * @param string $ic
	 * @return bool
	 */
	public static function isCompanyId(string $ic): bool
	{
		// "be liberal in what you receive"
		$ic = (string) preg_replace('#\s+#', '', $ic);

		// is correct format?
		if (!preg_match('#^\d{8}$#', $ic)) {
			return false;
		}

		// control sum
		$a = 0;
		for ($i = 0; $i < 7; $i++) {
			$a += (int) $ic[$i] * (8 - $i);
		}

		$a %= 11;

		if ($a === 0) {
			$c = 1;
		} elseif ($a === 10) {
			$c = 1;
		} elseif ($a === 1) {
			$c = 0;
		} else {
			$c = 11 - $a;
		}

		return (int) $ic[7] === $c;
	}


	/**
	 * Calculate date of birth and gender from birth.
	 *
	 * Return: [
	 *    'bornDate' => '1. 1. 1970' (by \DateTime)
	 *    'gender' => 'male'
	 * ]
	 *
	 * @param string $rc
	 * @return mixed[]|null
	 */
	public static function getBornDateFromIdentificationNumber(string $rc): ?array
	{
		if ($rc === '') {
			return null;
		}

		$rc = str_replace('/', '', $rc);

		if (\strlen($rc) === 10 && (int) \substr($rc, 0, 2) < 54) {
			$year = (int) \substr($rc, 0, 2) + 2000;
		} else {
			$year = (int) \substr($rc, 0, 2) + 1900;
		}

		$month = (int) substr($rc, 2, 2);
		if ((int) substr($rc, 2, 2) > 12) {
			$month -= 50;
		}

		$day = (int) substr($rc, 4, 2);

		try {
			if ($day > 0 && $month > 0 && $year > 0) {
				return [
					'bornDate' => new \DateTime($day . '.' . $month . '.' . $year),
					'gender' => (int) substr($rc, 2, 2) > 12 ? 'female' : 'male',
				];
			}
		} catch (\Exception $e) {
		}

		return null;
	}

	/**
	 * Normalize czech address.
	 * Remove things in brackets (for ex. "(OC Hostivař)")
	 *
	 * @param string $address
	 * @return string
	 */
	public static function formatAddress(string $address): string
	{
		$address = Strings::firstUpper(trim($address));

		$address = str_replace(
			['Nám.', 'Nam.', 'Ul.', 'Ulice', 'Tesco', 'TGM'],
			['Náměstí ', 'Náměstí ', ' ', ' ', ' ', 'Tomáše G. Masaryka'],
			$address
		);

		$address = (string) preg_replace('/[\s,]*\([^\)]*\)\s*/', ' ', $address);
		$address = (string) preg_replace('/Hypermarket \S+[\s,]+/i', ' ', $address);
		$address = (string) preg_replace('/OC \S+[\s,]+/i', ' ', $address);
		$address = (string) preg_replace('/OD \S+[\s,]+/i', ' ', $address);

		return trim((string) preg_replace('/\s+/', ' ', $address), ' .,+*/');
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public static function nameSalutation(string $name): string
	{
		static $czechNames;

		if ($czechNames === null) {
			try {
				$czechNames = Json::decode(file_get_contents(__DIR__ . '/czechNames.json') ? : '{}', Json::FORCE_ARRAY);
			} catch (JsonException $e) {
				$czechNames = [];
			}
		}

		if ($czechNames === []) {
			return $name;
		}

		$return = '';
		foreach (explode(' ', trim($name)) as $nameItem) {
			$nameItem = Strings::firstUpper($nameItem);
			$return .= ($czechNames[$nameItem] ?? $nameItem) . ' ';
		}

		return trim($return);
	}

}