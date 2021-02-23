<?php

declare(strict_types=1);

namespace MatiCore\Utils;


class FunctionException extends \Exception
{

	/**
	 * @param string $file
	 * @param int $line
	 * @param int $statusCode
	 * @throws FunctionException
	 */
	public static function headersWasAlreadySend(string $file, int $line, int $statusCode): void
	{
		$fileCapture = '';
		if (\is_file($file)) {
			$fileParser = explode("\n", str_replace(["\r\n", "\r"], "\n", (string) file_get_contents($file)));
			$start = $line > 8 ? $line - 8 : 0;

			for ($i = $start; $i <= $start + 15; $i++) {
				if (isset($fileParser[$i]) === false) {
					break;
				}

				$currentLine = $i + 1;

				$fileCapture .= str_pad(' ' . $currentLine . ': ', 6, ' ')
					. str_replace("\t", '    ', $fileParser[$i])
					. ($line === $currentLine ? ' <-------' : '') . "\n";
			}
		}

		throw new self(
			'Too late, headers already sent from "' . $file . '" on line #' . $line
			. "\n\n"
			. $fileCapture,
			$statusCode,
			new HeaderAlreadySentException($file, $file, $line)
		);
	}

}