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

Class RegularExpression
{
    private static $email = '^[^0-9][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[@][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[.][a-zA-Z]{2,4}$';
    private static $username = '^[a-zA-Z\@]+[a-zA-Z\@0-9]*$';
    private static $password = '^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,15}$';
    private static $rif = '^([JGVE]{1})-([0-9]{8})-([0-9]{1})$';
    private static $telephone = '^([0-9]){4}\-([0-9]){7}+$';
    private static $url = '^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w\?\.\+\&\,\:=-]*)*\/?$';

    public static function isEmail( $string )
    {
        return preg_match( '/'.self::$email.'/', $string );
    }

    public static function isValidPassword( $string )
    {
        return preg_match( '/'.self::$password.'/', $string );
    }

    public static function get( $expresion )
    {
        switch( strtolower( $expresion ) )
        {
            case 'email':
                return self::$email;
                break;

            case 'username':
                return self::$username;
                break;

            case 'password':
                return self::$password;
                break;

            case 'rif':
                return self::$rif;
                break;

            case 'telephone':
                return self::$telephone;
                break;

            case 'url':
                return self::$url;
                break;

            default:
                throw new \Exception( "La expresion regular '{$expresion}' no fue encontrada." );
                break;
        }
    }
}