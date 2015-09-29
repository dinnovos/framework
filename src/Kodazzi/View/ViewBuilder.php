<?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <jgaitan@kodazzi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\View;

use Service;
use Kodazzi\Config\ConfigBuilderInterface;
use Kodazzi\Session\User;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

Class ViewBuilder
{
	private $Twig_Loader_Filesystem = null;
	private $Twig = null;
	private $data = array();

	/**
	 * @var User
	 * @see User
	 */
	protected $User;

    /**
     * @var Config
     * @see Config
     */
    protected $Config;

    /**
     * @var UrlGenerator
     * @see UrlGenerator
     */
    protected $UrlGenerator;

	public function __construct(ConfigBuilderInterface $config, SessionInterface $user, UrlGenerator $url_generator)
	{
		$this->User = $user;
        $this->Config = $config;
        $this->UrlGenerator = $url_generator;

        $namespaces = Service::getNamespacesBundles();

        $theme_web = $config->get('app', 'theme_web');
        $theme_admin = $config->get('app', 'theme_admin');
        $enabled_path_themes = $config->get('app', 'enabled_path_themes');

		$path_templates = array(
			YS_APP.'src/layouts',
			YS_APP.'src/templates'
		);

        if($enabled_path_themes)
        {
            if(is_dir(YS_THEMES.$theme_web.'/layouts'))
            {
                $path_templates[] = YS_THEMES.$theme_web.'/layouts';
            }

            if(is_dir(YS_THEMES.$theme_web.'/templates'))
            {
                $path_templates[] = YS_THEMES.$theme_web.'/templates';
            }

            if(is_dir(YS_THEMES.$theme_admin.'/layouts'))
            {
                $path_templates[] = YS_THEMES.$theme_admin.'/layouts';
            }

            if(is_dir(YS_THEMES.$theme_admin.'/templates'))
            {
                $path_templates[] = YS_THEMES.$theme_admin.'/templates';
            }
        }

        foreach($namespaces as $namespace)
        {
            $path_bundles_templates = str_replace('\\', '/', YS_BUNDLES.$namespace.'templates' );

            if(is_dir($path_bundles_templates))
            {
                $path_templates[] = $path_bundles_templates;
            }
        }

		$Twig_Loader_Filesystem = new \Twig_Loader_Filesystem( $path_templates );

		$Twig = new \Twig_Environment( null, array(
			'cache' => YS_CACHE.'views',
			'debug' => YS_DEBUG,
		));

		// Funcion para construir las url
		$build_url = new \Twig_SimpleFunction('build_url', function ( $name_route, $parameters = array() ) {
			return \Kodazzi\Tools\Util::buildUrl( $name_route , $parameters );
		});

        // Funcion para construir las url
        $cut_text = new \Twig_SimpleFunction('cut_text', function ( $string, $limit = 100, $end_char = '...' ) {
            return \Kodazzi\Tools\String::cutText( $string, $limit, $end_char);
        });

		// Funcion para cortar texto muy largo.
		$resume = new \Twig_SimpleFunction('resume', function ( $string, $limit = 100, $end_char = '...' ) {
			return \Kodazzi\Tools\String::resume( $string, $limit, $end_char);
		});

		// Funcion para dar formato a un numero
		$number_format = new \Twig_SimpleFunction('number_format', function ( $number , $decimals = 0 , $dec_point = ',' , $thousands_sep = '.'  ) {
			return number_format( $number, $decimals, $dec_point, $thousands_sep);
		});

		// Funcion para dar formato a un numero
		$date_format = new \Twig_SimpleFunction('date_format', function ( $date, $format  ) {
			return \Kodazzi\Tools\Date::format( $date, $format );
		});

        // Funcion para dar formato a un numero
        $get_date = new \Twig_SimpleFunction('get_date', function ( $string ) {
            return \Kodazzi\Tools\Date::getDate( $string );
        });

		// Funcion para indicar si existe un archivo
		$isFile = new \Twig_SimpleFunction('isFile', function ( $path , $file ) {
			return \Kodazzi\Tools\Util::isFile( $path, $file );
		});

		// Funcion para indicar si existe un archivo
		$hash = new \Twig_SimpleFunction('hash', function ( $id, $str = 'z6i5v36h3F5', $position = 5, $prefix = '' ) {
			return \Kodazzi\Tools\Util::hash( $id, $str, $position, $prefix);
		});

        // Funcion para indicar si existe un archivo
        $ucfirst = new \Twig_SimpleFunction('ucfirst', function ( $string ) {
            return ucfirst($string);
        });

        // Funcion para indicar si existe un archivo
        $dump = new \Twig_SimpleFunction('dump', function ( $var ) {
            ob_start();
            var_dump($var);
            $a=ob_get_contents();
            ob_end_clean();
            return $a;
        });

		$Twig->addFunction( $build_url );
		$Twig->addFunction( $cut_text );
		$Twig->addFunction( $get_date );
		$Twig->addFunction( $resume );
		$Twig->addFunction( $number_format );
		$Twig->addFunction( $isFile );
		$Twig->addFunction( $date_format );
		$Twig->addFunction( $hash );
		$Twig->addFunction( $ucfirst );
		$Twig->addFunction( $dump );

		$this->Twig_Loader_Filesystem = $Twig_Loader_Filesystem;
		$this->Twig = $Twig;
	}

    public function addFunction($function, \Closure $closure)
    {
        $closure = new \Twig_SimpleFunction($function, $closure);

        $this->Twig->addFunction($closure);
    }

	public function set( $keys, $value = null )
	{
		if (is_array($keys))
		{
			foreach ($keys as $key => $val)
			{
				$this->data[$key] = $val;
			}

			return;
		}

		$this->data[$keys] = $value;
	}

	public function addPath( $path )
	{
		$path = str_replace('\\', '/', $path);

		$this->Twig_Loader_Filesystem->addPath( $path );
	}

	public function render( $template, $data = array(), $path = null )
	{
		$data = array_merge( $this->data, $data);

		return $this->_render( $template, $data, $path );
	}

	private function _render( $template, $data, $path = null )
	{
		$template = str_replace('\\', '/', $template);

		if( $path )
		{
			$this->Twig_Loader_Filesystem->addPath( $path );
			$this->Twig->setLoader( $this->Twig_Loader_Filesystem );
		}
		else
		{
			$parts = explode(':', $template );
            $tpl = '';

            if( is_array( $parts ) && count( $parts ) == 3 )
			{
                // Verifica si se busca un subdirectorio dentro de la vista
                if(stripos($parts[1],'/'))
                {
                    $p = explode('/', $parts[1]);

                    foreach($p as $_p)
                    {
                        $tpl .= \Kodazzi\Tools\Inflector::underscore($_p).'/';
                    }

                    $tpl = trim($tpl, '/');
                }
                else
                {
                    $tpl = \Kodazzi\Tools\Inflector::underscore($parts[1]);
                }

                $path = YS_BUNDLES.$parts[0].'/views/'.strtolower($tpl);

                $enabled_path_themes = $this->Config->get('app', 'enabled_path_themes');

                // Si empieza con @ y el key config "path_default_view" == false, busca el theme con el key config "theme_web"
                if(preg_match('/^(@web|@admin)(.)+/', $parts[0]) && $enabled_path_themes)
                {
                    if(preg_match('/^(@web)(.)+/', $parts[0]))
                    {
                        $theme = $this->Config->get('app', 'theme_web');
                    }
                    else
                    {
                        $theme = $this->Config->get('app', 'theme_admin');
                    }

                    $parts[0] = ltrim(str_replace(array('@admin', '@web'), '', $parts[0]), '/');
                    $path = YS_THEMES.$theme.'/'.$parts[0].'/views/'.strtolower($tpl);
                }

				$this->Twig_Loader_Filesystem->addPath($path);
				$this->Twig->setLoader( $this->Twig_Loader_Filesystem );

				$template = $parts[2];
			}
		}

		return $this->Twig->render( $template.YS_EXT_TEMPLATE, $data );
	}

	public function msgSuccess( $msg )
	{
		$this->User->getFlashBag()->add( 'alert-msg', array('type' => 'alert-success', 'msg' => $msg) );
	}

	public function msgError( $msg )
	{
		$this->User->getFlashBag()->add( 'alert-msg', array('type' => 'alert-danger', 'msg' => $msg) );
	}

	public function msgInformation( $msg )
	{
		$this->User->getFlashBag()->add( 'alert-msg', array('type' => 'alert-info', 'msg' => $msg) );
	}

	public function msgWarning( $msg )
	{
		$this->User->getFlashBag()->add( 'alert-msg', array('type' => 'alert-warning', 'msg' => $msg) );
	}

	public function showMessage()
	{
		$FlashBag = $this->User->getFlashBag();

		if( $FlashBag->has('alert-msg') )
		{
			foreach ( $FlashBag->get('alert-msg', array()) as $flash)
			{
				echo '<div id="ds-alert" class="alert '.$flash['type'].'">'.$flash['msg'].'</div>';
			}
		}
	}
}