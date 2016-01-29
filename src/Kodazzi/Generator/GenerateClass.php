<?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <info@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Ys_GenerateClass
 * 
 * @author Jorge Gaitan
 */

namespace Kodazzi\Generator;

Class GenerateClass
{

	private $template = null;
	private $name_class = null;
	private $name_class_extend = null;
	private $namespace = null;
	private $data = array();

	public function __construct()
	{
		
	}

	public function setValues($data)
	{
		$this->data = $data;
	}

	public function setTemplate($template)
	{
		$this->template = $template;
	}

	public function setNameClass($class)
	{
		$this->name_class = $class;
	}

	public function setNameSpace($namespace)
	{
		$this->namespace = $namespace;
	}

	public function setNameClassExtend($class)
	{
		$this->name_class_extend = $class;
	}

	public function create($path_file_generate, $data = array(), $ext = '.php')
	{
		$trans = array();
		$options = $this->data;

		$path_template = __DIR__ . '/templates/' . $this->template . '.php';

		if ( !is_file($path_template) )
		{
			throw new \Exception("La plantilla \"$this->template\" no existe para generar.");
		}

		ob_start();
		require $path_template;
		$content = ob_get_clean();

		$trans['##NAMESPACE##'] = ($this->namespace) ? $this->namespace : '';
		$trans['##CLASS##'] = ($this->name_class) ? $this->name_class : '';
		$trans['##EXTENDS##'] = ($this->name_class_extend) ? 'extends ' . $this->name_class_extend : '';

		$content = strtr($content, $trans);

		$content = str_replace(array('[?php', '[?=', '?]'), array('<?php', '<?php echo', '?>'), $content);

		\Kodazzi\Tools\File::write($path_file_generate . $ext, $content);
	}

}
