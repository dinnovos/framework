<?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Session;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

class TemporaryAttributeBag implements AttributeBagInterface, \IteratorAggregate, \Countable
{
	private $name = 'temporary';

	/**
	 * @var string
	 */
	private $storageKey;

	/**
	 * @var array
	 */
	protected $attributes = array();

	protected $trash = null;

	/**
	 * Constructor.
	 *
	 * @param string $storageKey The key used to store attributes in the session
	 */
	public function __construct( $storageKey = 'temporary' )
	{
		$this->storageKey = $storageKey;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * {@inheritdoc}
	 */
	public function initialize(array &$attributes)
	{
		$this->attributes = &$attributes;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getStorageKey()
	{
		return $this->storageKey;
	}

	/**
	 * {@inheritdoc}
	 */
	public function has($name)
	{
		if( $this->isNew() )
		{
			return array_key_exists($name, $this->attributes['container']);
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get( $name, $default = null, $forcing_all = false )
	{
		if( $forcing_all || ( !$forcing_all && $this->isNew() ) )
		{
			return (array_key_exists( 'container', $this->attributes) && array_key_exists($name, $this->attributes['container']) ) ? $this->attributes['container'][$name] : $default;
		}

		return $default;
	}

	/**
	 * {@inheritdoc}
	 */
	public function set( $name, $value )
	{
		if( $this->isNew() )
		{
			$this->attributes['container'][$name] = $value;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function all( $forcing_all = false )
	{
		if( $forcing_all || ( !$forcing_all && $this->isNew() ) )
		{
			return ( array_key_exists( 'container', $this->attributes ) ) ? $this->attributes['container'] : array();
		}

		return array();
	}

	/**
	 * {@inheritdoc}
	 */
	public function replace(array $attributes)
	{
		$this->attributes['container'] = array();

		foreach ( $attributes as $key => $value )
		{
			$this->set($key, $value);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function remove($name)
	{
		$retval = null;

		if (array_key_exists($name, $this->attributes['container']))
		{
			$retval = $this->attributes['container'][$name];
			unset($this->attributes['container'][$name]);
		}

		return $retval;
	}

	/**
	 * {@inheritdoc}
	 */
	public function clear()
	{
		$return = ( array_key_exists( 'container', $this->attributes ) ) ? $this->attributes['container'] : array();

		$this->attributes = array();

		return $return;
	}

	/**
	 * Returns an iterator for attributes.
	 *
	 * @return \ArrayIterator An \ArrayIterator instance
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->attributes['container']);
	}

	/**
	 * Returns the number of attributes.
	 *
	 * @return int     The number of attributes
	 */
	public function count()
	{
		return count($this->attributes['container']);
	}

	public function getTrash()
	{
		return $this->trash;
	}

	public function indentifier( $hash = null )
	{
		if( $hash )
		{
			if( $hash  && array_key_exists( '_indentifier', $this->attributes ) && $this->attributes['_indentifier'] === $hash )
			{
				$this->attributes['_status'] = 'new';
			}
			else
			{
				// Antes de eliminar la data la almacena en la papelera para poder ser usada en la misma peticion.
				$this->trash = $this->attributes;

				$this->clear();

				$this->attributes['_indentifier'] = $hash;
				$this->attributes['_status'] = 'new';
			}

			return;
		}

		// Crea un id por defecto.
		if( !array_key_exists( '_indentifier', $this->attributes ) || ( array_key_exists( '_indentifier', $this->attributes ) && $this->attributes['_indentifier'] == '' ) )
		{
			$this->attributes['_indentifier'] = \Kodazzi\Tools\Util::hash('h1a3sHhnf6bDgS');
		}

		$this->attributes['_status'] = 'new';
	}

	// Metodo llamado en Kodazzi\boostrap.php para verificar el estatus de la bolsa.
	public function checkStatus()
	{
		if( array_key_exists( '_status', $this->attributes ) && $this->attributes['_status'] == 'new' )
		{
			$this->attributes['_status'] = 'old';

			return;
		}

		$this->attributes = array();

		$this->clear();
	}

	public function isNew()
	{
		if( array_key_exists( '_status', $this->attributes ) && $this->attributes['_status'] == 'new' )
		{
			return true;
		}

		return false;
	}
}