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

Class Date
{
	public static function format( $date, $format )
	{
		$fecha = new \DateTime( $date );
		return $fecha->format( $format );
	}

    public static function getDate( $string = 'Y-m-d H:i:s' )
    {
        $DateTime = new \DateTime('NOW');
        return $DateTime->format( $string );
    }

	public static function diff( $datetime1, $datetime2, $diff = null )
	{
		$datetime1 = new \DateTime($datetime1);
		$datetime2 = new \DateTime($datetime2);

		$interval = $datetime1->diff( $datetime2 );

		switch( strtolower($diff) )
		{
			case 'y':
				return $interval->y;
				break;
			case 'm':
				return $interval->m;
				break;
			case 'd':
				return $interval->days;
				break;
			case 'h':
				return $interval->h;
				break;
			case 'i':
				return $interval->i;
				break;
			case 's':
				return $interval->s;
				break;
			default:
				return $interval;
				break;
		}
	}
}