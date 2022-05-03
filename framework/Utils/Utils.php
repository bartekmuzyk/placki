<?php

namespace Framework\Utils;

class Utils
{
	/**
	 * @param int $bytes
	 * @return string a file size string in a human-readable format from the number of bytes
	 */
	public static function getHumanReadableSize(int $bytes): string
	{
		$i = floor(log($bytes) / log(1024));
		$sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

		return sprintf('%.02F', $bytes / pow(1024, $i)) * 1 . ' ' . $sizes[$i];
	}
}