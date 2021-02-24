<?php

declare(strict_types=1);

namespace MatiCore\Utils;


use Nette\StaticClass;

class Date
{

	use StaticClass;

	/**
	 * @param string|int|\DateTime|null $datetime
	 * @return int
	 */
	public static function getTimestamp($datetime): int
	{
		if ($datetime instanceof \DateTime) {
			$time = $datetime->getTimestamp();
		} elseif ($datetime === null) {
			$time = \time();
		} elseif (is_numeric($datetime)) {
			$time = $datetime;
		} else {
			$time = @strtotime($datetime);

			if ($time === false && preg_match('/^(?<day>\d+)\.\s*(?<month>\d+)\.\s*(?<year>\d+)/', $datetime, $datetimeParser)) {
				$time = @strtotime($datetimeParser['year'] . '-' . $datetimeParser['month'] . '-' . $datetimeParser['day']);
			}
		}

		return $time ? : \time();
	}

	/**
	 * Format date to "Y-m-d", if null return current date.
	 *
	 * @param int|null $timestamp
	 * @return string (Y-m-d)
	 */
	public static function getDateIso(int $timestamp = null): string
	{
		return date('Y-m-d', $timestamp ?? \time());
	}

	/**
	 * Format datetime to "Y-m-d H:i:s", if null return current datetime.
	 *
	 * @param int|null $timestamp
	 * @return string (Y-m-d H:i:s)
	 */
	public static function getDateTimeIso(int $timestamp = null): string
	{
		return date('Y-m-d H:i:s', $timestamp ?? \time());
	}

	/**
	 * is valid ISO date? (YYYY-MM-DD)
	 *
	 * @param string $value
	 * @return bool
	 */
	public static function isDate(string $value): bool
	{
		return \strlen($value) === 10
			&& preg_match('/^\d{4}-\d{2}-\d{2}$/', $value, $matches) === 1
			&& @strtotime($value) !== false;
	}

	/**
	 * is valid ISO datetime? (YYYY-MM-DD HH:MM:SS)
	 *
	 * @param string $value
	 * @return bool
	 */
	public static function isDateTime(string $value): bool
	{
		return \strlen($value) === 19
			&& preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value, $matches) === 1
			&& @strtotime($value) !== false;
	}

	/**
	 * Format date from any format (\DateTime, string, int, timestamp) by \strtotime().
	 * If $moreUserFriendly = true, date can be replaced by "dnes", "včera", "předevčírem", "zítra", "pozítří".
	 * If date is invalid, return "#invalid-date".
	 *
	 * @param string|int|\DateTime|null $date
	 * @param bool|null $moreUserFriendly
	 * @return string
	 */
	public static function formatDate($date, ?bool $moreUserFriendly = null): string
	{
		$result = date('j. n. Y', self::getTimestamp($date));

		if ($moreUserFriendly === true) {
			if ($result === date('j. n. Y')) {
				$result = 'dnes';
			} elseif ($result === date('j. n. Y', strtotime('-1 day'))) {
				$result = 'včera';
			} elseif ($result === date('j. n. Y', strtotime('-2 day'))) {
				$result = 'předevčírem';
			} elseif ($result === date('j. n. Y', strtotime('+1 day'))) {
				$result = 'zítra';
			} elseif ($result === date('j. n. Y', strtotime('+2 day'))) {
				$result = 'pozítří';
			}
		}

		return $result;
	}

	/**
	 * Format date/datetime from any format (\DateTime, string, int, timestamp) by \strtotime().
	 * If $moreUserFriendly = true, date can be replaced by "dnes", "včera", "zítra".
	 * If datetime is invalid, return "#invalid-datetime".
	 *
	 * @param string|int|\DateTime|null $datetime
	 * @param bool|null $includeSeconds
	 * @param bool|null $moreUserFriendly
	 * @return string
	 */
	public static function formatDateTime($datetime, ?bool $includeSeconds = null, ?bool $moreUserFriendly = null): string
	{
		$result = date('j.n.Y H:i' . ($includeSeconds === true ? ':s' : ''), self::getTimestamp($datetime));

		if ($moreUserFriendly === true) {
			if (strpos($result, date('j.n.Y')) === 0) {
				$result = str_replace(date('j.n.Y'), 'dnes', $result);
			} elseif (strpos($result, date('j.n.Y', strtotime('-1 day'))) === 0) {
				$result = str_replace(date('j.n.Y', strtotime('-1 day')), 'včera', $result);
			} elseif (strpos($result, date('j.n.Y', strtotime('+1 day'))) === 0) {
				$result = str_replace(date('j.n.Y', strtotime('-1 day')), 'zítra', $result);
			}
		}

		return $result;
	}

	/**
	 * Returns day of week (1-7, 1 => monday)
	 *
	 * @param string $date
	 * @return int
	 */
	public static function dayOfWeek(?string $date = null): int
	{
		return (int) date('N', (int) strtotime($date ?? 'now'));
	}

	/**
	 * Returns TRUE if given day is monday
	 *
	 * @param string|null $date
	 * @return bool
	 */
	public static function isMonday(?string $date = null): bool
	{
		return self::dayOfWeek($date) === 1;
	}

	/**
	 * Returns TRUE if given day is tuesday
	 *
	 * @param string|null $date
	 * @return bool
	 */
	public static function isTuesday(?string $date = null): bool
	{
		return self::dayOfWeek($date) === 2;
	}

	/**
	 * Returns TRUE if given day is wednesday
	 *
	 * @param string|null $date
	 * @return bool
	 */
	public static function isWednesday(?string $date = null): bool
	{
		return self::dayOfWeek($date) === 3;
	}

	/**
	 * Returns TRUE if given day is thursday
	 *
	 * @param string|null $date
	 * @return bool
	 */
	public static function isThursday(?string $date = null): bool
	{
		return self::dayOfWeek($date) === 4;
	}

	/**
	 * Returns TRUE if given day is friday
	 *
	 * @param string|null $date
	 * @return bool
	 */
	public static function isFriday(?string $date = null): bool
	{
		return self::dayOfWeek($date) === 5;
	}

	/**
	 * Returns TRUE if given day is saturday
	 *
	 * @param string|null $date
	 * @return bool
	 */
	public static function isSaturday(?string $date = null): bool
	{
		return self::dayOfWeek($date) === 6;
	}

	/**
	 * Returns TRUE if given day is sunday
	 *
	 * @param string|null $date
	 * @return bool
	 */
	public static function isSunday(?string $date = null): bool
	{
		return self::dayOfWeek($date) === 7;
	}

	/**
	 * Return month name in czech
	 *
	 * @param \DateTime $dateTime
	 * @return string
	 */
	public static function getCzechDayName(\DateTime $dateTime): string
	{
		$monthNames = ['pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota', 'neděle'];
		$month = (int) $dateTime->format('N') - 1;

		return $monthNames[$month];
	}

	/**
	 * Return month name in czech
	 *
	 * @param \DateTime $dateTime
	 * @return string
	 */
	public static function getCzechMonthName(\DateTime $dateTime): string
	{
		$monthNames = ['leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec'];
		$month = (int) $dateTime->format('n') - 1;

		return $monthNames[$month];
	}

}
