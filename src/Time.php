<?php

declare(strict_types=1);

namespace MatiCore\Utils;


use Nette\StaticClass;

/**
 * Class Time
 * @package MatiCore\Utils
 */
class Time
{

	use StaticClass;

	/**
	 * Porovna predany cas s tim aktualnim a v lidsky citelne podobe zobrazi, kolik casu uz uplynulo
	 * Kdyz je $moreAccurate = FALSE, zobrazi pouze jednu velicinu (napr. "2 dny", "4 minuty"),
	 * v pripade $moreAccurate=TRUE, zobrazi 2 veliciny (napr. "2 dny 21 hodin", nebo "4 minuty 5 sekund")
	 * Funkce aktualne podporuje preklad a sklonovani do 3 jazyku (cz, sk, en)
	 *
	 * @param int $time Cas jako unix timestamp, nebo jako retezec, ktery zvladne prevest strtotime()
	 * @param bool $moreAccurate
	 * @param string $lang (cz/sk/en)
	 * @return string
	 * @throws FunctionException
	 */
	public static function formatTimeAgo(int $time, bool $moreAccurate = true, string $lang = 'cz'): string
	{
		if ($lang === 'cz') {
			$labels = [
				['sekunda', 'sekundy', 'sekund'],
				['minuta', 'minuty', 'minut'],
				['hodina', 'hodiny', 'hodin'],
				['den', 'dny', 'dní'],
				['měsíc', 'měsíce', 'měsíců'],
				['rok', 'roky', 'let'],
			];
		} elseif ($lang === 'sk') {
			$labels = [
				['sekunda', 'sekundy', 'sekúnd'],
				['minúta', 'minúty', 'minút'],
				['hodina', 'hodiny', 'hodín'],
				['deň', 'dni', 'dní'],
				['mesiac', 'mesiace', 'mesiacov'],
				['rok', 'roky', 'rokov'],
			];
		} elseif ($lang === 'en') {
			$labels = ['second', 'minute', 'hour', 'day', 'month', 'year'];
		} else {
			throw new FunctionException('Unsupported lang "' . $lang . '" (supported languages: cz/sk/en)');
		}

		$currentTime = time();
		$diff = $currentTime - $time;
		$no = 0;
		$lengths = [1, 60, 3600, 86400, 2630880, 31570560];
		$v = \count($lengths) - 1;

		for (true; ($v >= 0) && (($no = $diff / $lengths[$v]) <= 1); true) {
			$v--;
		}

		if ($v < 0) {
			$v = 0;
		}

		$x = $currentTime - ($diff % $lengths[$v]);
		$no = (int) floor($no);
		$label = null;

		if (isset($labels[$v]) && \is_string($labels[$v])) {
			$label = $labels[$v];
			if ($lang === 'en' && $no !== 1) {
				$label .= 's';
			}
		} elseif (\is_array($labels[$v])) {
			if ($no === 1) {
				$label = $labels[$v][0];
			} elseif ($no >= 2 && $no <= 4) {
				$label = $labels[$v][1];
			} else {
				$label = $labels[$v][2];
			}
		}

		$result = $no . ' ' . $label . ' ';
		if ($moreAccurate && ($v >= 1) && (($currentTime - $x) > 0)) {
			$result .= self::formatTimeAgo($x, false, $lang);
		}

		return trim($result);
	}

	/**
	 * Return user-friendly formatted duration time from $fromMicroTime to now (or $nowMicroTime).
	 * Example: "7.43 ms", "1.64 s"
	 *
	 * @param int $fromMicroTime
	 * @param int|null $nowMicroTime -> if null use now timestamp
	 * @param int $msDecimals
	 * @param int $secDecimals
	 * @return string
	 */
	public static function formatDurationFrom(
		int $fromMicroTime,
		int $nowMicroTime = null,
		int $msDecimals = 2,
		int $secDecimals = 3
	): string
	{
		return self::formatMicroTime(
			($nowMicroTime ? : (int) microtime(true)) - $fromMicroTime,
			$msDecimals,
			$secDecimals
		);
	}

	/**
	 * Vrati formatovany microtime (pocet millisekund, nebo sekund s millisekundama) spolu s jednotkou (priklady: 147.25 ms, 14.721 s)
	 * Typicky se to pouziva pri zobrazeni casu, jak dlouho trvala nejaka operace, typicky predavame hodnotu: [microtime(true) - $startTime]
	 *
	 * Kdyz je hodnota vetsi/rovna 1, tak se vrati v sekundach, kdyz mene nez 1, tak v millisekundach
	 *
	 * @param int $microTime (typicky se predava rozdil 2 microtimeu, kterych rozdil chceme naformatovat)
	 * @param int $msDecimals Na kolik des. mist se ma formatovat pocet v millisekundach (kdyz to vychazi na mene nez 1 sekundu)
	 * @param int $secDecimals Na kolik des. mist se ma formatovat pocet ve vterinach (kdyz to vychazi na vice, nez 1 sekundu)
	 * @return string
	 */
	public static function formatMicroTime(int $microTime, int $msDecimals = 2, int $secDecimals = 3): string
	{
		if ($microTime >= 1) {
			return number_format($microTime, $secDecimals, '.', ' ') . ' s';
		}

		return number_format($microTime * 1000, $msDecimals, '.', ' ') . ' ms';
	}

	/**
	 * Convert seconds to user-friendly format:
	 * Example: 7 seconds to "00:00:07", 70 seconds to "00:01:10"
	 *
	 * @param int $seconds
	 * @return string
	 */
	public static function convertDurationToHms(int $seconds): string
	{
		$result = null;

		if (!is_numeric($seconds) || $seconds < 0) {
			$result = '??:??:??';
		} elseif ($seconds === 0) {
			$result = '00:00:00';
		} else {
			$hours = (int) floor($seconds / 3600);
			$minutes = (int) floor(($seconds - ($hours * 3600)) / 60);
			$seconds2 = $seconds - ($hours * 3600) - ($minutes * 60);

			$result = sprintf('%1$02d:%2$02d:%3$02d', $hours, $minutes, $seconds2);
		}

		return $result;
	}

	/**
	 * @param int $seconds
	 * @return string
	 */
	public static function convertDurationToHmsHuman(int $seconds): string
	{
		return (string) preg_replace('/^00\:/', '', self::convertDurationToHms($seconds));
	}

	/**
	 * Prevede delku/dobu v sekundach na delsi user-friendly text s moznosti sklonovat hodiny/minuty/sekundy i v jinych jazycichs:
	 * Priklad: 7 minut 14 sekund.
	 *
	 * @param int $seconds
	 * @param bool $includeSeconds true
	 * @param string $labelHour1
	 * @param string $labelHours234
	 * @param string $labelHours
	 * @param string $labelMinute1
	 * @param string $labelMinutes234
	 * @param string $labelMinutes
	 * @param string $labelSecond1
	 * @param string $labelSeconds234
	 * @param string $labelSeconds
	 * @return string
	 */
	public static function convertDurationToHmsLong(
		int $seconds,
		bool $includeSeconds = true,
		$labelHour1 = 'hodina',
		$labelHours234 = 'hodiny',
		$labelHours = 'hodin',
		$labelMinute1 = 'minuta',
		$labelMinutes234 = 'minuty',
		$labelMinutes = 'minut',
		$labelSecond1 = 'sekunda',
		$labelSeconds234 = 'sekundy',
		$labelSeconds = 'sekund'
	): string
	{
		if ($seconds < 0) {
			$result = '?';
		} elseif ($seconds === 0) {
			$result = $includeSeconds ? '0 ' . $labelSeconds : '< 1 ' . $labelMinute1;
		} else {
			$hours = (int) floor($seconds / 3600);
			$minutes = (int) floor(($seconds - ($hours * 3600)) / 60);
			$seconds2 = $seconds - ($hours * 3600) - ($minutes * 60);

			$result = '';

			try {
				if ($hours > 0) {
					$result .= Czech::inflection($hours, [$labelHour1, $labelHours234, $labelHours]) . ' ';
				}
				if ($minutes > 0) {
					$result .= Czech::inflection($minutes, [$labelMinute1, $labelMinutes234, $labelMinutes]) . ' ';
				}
				if ($seconds2 > 0 && $includeSeconds) {
					$result .= Czech::inflection((int) floor($seconds2), [$labelSecond1, $labelSeconds234, $labelSeconds]) . ' ';
				}
			} catch (FunctionException $e) {
				$result = '';
			}

			if (trim($result) === '') {
				$result .= '< 1 ' . $labelMinute1;
			}

			$result = trim($result);
		}

		return $result;
	}

	/**
	 * Convert \DateTime|timestamp to time format /H:i(:s)?/.
	 *
	 * @param string|int|\DateTime $date
	 * @param bool|null $includeSeconds
	 * @return string
	 */
	public static function formatTime($date, ?bool $includeSeconds = null): string
	{
		if ($date instanceof \DateTime) {
			$time = $date->getTimestamp();
		} else {
			$time = is_numeric($date) ? $date : @strtotime($date);
		}

		if (!$time) {
			return '#invalid-date';
		}

		return date('H:i' . ($includeSeconds === true ? ':s' : ''), (int) $time);
	}

}