<?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <jgaitan@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Tools;

Class Inflector
{
	public static function camelize($string)
	{
		$string = 'x'.strtolower(trim($string));
		$string = ucwords(preg_replace('/[\s_]+/', ' ', $string));
		return substr(str_replace(' ', '', $string), 1);
	}

	public static function underscore( $word, $sep = '_' )
	{
		return  strtolower(preg_replace('/[^A-Z^a-z^0-9]+/',$sep,
			preg_replace('/([a-z\d])([A-Z])/','\1_\2',
				preg_replace('/([A-Z]+)([A-Z][a-z])/','\1_\2',$word))));
	}
}