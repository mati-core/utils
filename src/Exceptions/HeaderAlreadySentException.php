<?php

declare(strict_types=1);

namespace MatiCore\Utils;


/**
 * Class HeaderAlreadySentException
 * @package MatiCore\Utils
 */
class HeaderAlreadySentException extends FunctionException
{

	/**
	 * @param string $message
	 * @param string $file
	 * @param int $line
	 */
	public function __construct(
		string $message,
		string $file,
		int $line
	)
	{
		parent::__construct($message, 500, null);
		$this->file = $file;
		$this->line = $line;
	}

}