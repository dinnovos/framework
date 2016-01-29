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
 * Ys_CreateCommand
 * 
 * @author Jorge Gaitan
 */

namespace Kodazzi\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;
use Kodazzi\Container\Service;

Class BundleCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('app:bundle')
			->setDescription('Crea un bundle basico')
			->addArgument(
				'action',
				InputArgument::REQUIRED,
				'create o delete'
			)
            ->addArgument(
                'namespace',
                InputArgument::REQUIRED,
                'namespace of bundle'
            );
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
        $path_schema = Ki_APP . 'src/storage/schemas/';
		$action = $input->getArgument('action');
		$namespace = $input->getArgument('namespace');
        $yaml = new Parser();

		if( !in_array($action, array('create', 'delete') ) )
		{
			$output->writeln( PHP_EOL . 'El parametro "action" debe ser create o delete. Por defecto es create.' . PHP_EOL );

			exit;
		}

		switch( strtoupper($action) )
		{
			case 'CREATE':

                if(is_dir(Ki_BUNDLES.$namespace))
                {
                    $output->writeln( PHP_EOL . 'Ya existe un bundle con el  mismo espacio de nombre.' . PHP_EOL );

                    exit;
                }

                // Crea el directorio config/
                $this->mkdir(Ki_BUNDLES.$namespace.'/config/schema');

                // Crea el directorio Controllers/
                $this->mkdir(Ki_BUNDLES.$namespace.'/Controllers');

                // Crea el directorio i18n/
                $this->mkdir(Ki_BUNDLES.$namespace.'/i18n');

                // Crea el directorio Main/
                $this->mkdir(Ki_BUNDLES.$namespace.'/Main');

                // Crea el directorio Providers/
                $this->mkdir(Ki_BUNDLES.$namespace.'/Providers');

                // Crea el directorio Services/
                $this->mkdir(Ki_BUNDLES.$namespace.'/Services');

                // Crea el directorio vies/
                $this->mkdir(Ki_BUNDLES.$namespace.'/views/home');

                $GenerateClass = Service::get('generate_class');

                // Crea la clase HomeController
                $GenerateClass->setTemplate('Controller');
                $GenerateClass->setValues( array(
                    'bundle' => str_replace('/', '\\', $namespace)
                ) );
                $GenerateClass->create(Ki_BUNDLES . $namespace . '/Controllers/HomeController');

                // Crea la clase BundleController
                $GenerateClass->setTemplate('BundleController');
                $GenerateClass->setValues( array(
                    'bundle' => str_replace('/', '\\', $namespace)
                ) );
                $GenerateClass->create(Ki_BUNDLES . $namespace . '/Main/BundleController');

                // Crea las rutas del bundle
                $GenerateClass->setTemplate('routes');
                $GenerateClass->setValues( array(
                    'bundle' => str_replace('/', '\\', $namespace),
                    'route' => str_replace(array('/', '\\'), '-', strtolower($namespace)),
                ) );
                $GenerateClass->create(Ki_BUNDLES . $namespace . '/config/routes.cf');

                // Crea la plantilla index.twig
                $GenerateClass->setTemplate('index');
                $GenerateClass->create(Ki_BUNDLES . $namespace . '/views/home/index', array(), '.twig');

                // Crea el HookBundle
                $GenerateClass->setTemplate('HookBundle');
                $GenerateClass->setValues( array(
                    'namespace' => str_replace('/', '\\', $namespace)
                ) );
                $GenerateClass->create(Ki_BUNDLES . $namespace . '/HookBundle');

                \Kodazzi\Tools\Util::bundle($namespace, 'new');

                $output->writeln( PHP_EOL . 'El bundle ' . str_replace('/', '\\', $namespace) . ' ha sido creado con exito' . PHP_EOL );

                exit;

				break;

			case 'DELETE':

                if(!is_dir(Ki_BUNDLES.$namespace))
                {
                    $output->writeln( PHP_EOL . 'No se ha encontrado el bundle' . PHP_EOL );

                    exit;
                }

                \Kodazzi\Tools\Util::bundle($namespace, 'delete');

                $output->writeln( PHP_EOL . 'El bundle ' . str_replace('/', '\\', $namespace) . ' ha sido eliminado con exito' . PHP_EOL );

				break;
		}
	}

	private function  mkdir( $path )
	{
		$fs = new Filesystem();

		try
		{
			$fs->mkdir( $path );

			return  true;
		}
		catch (IOException $e)
		{
			echo "Ha ocurrido un error mientras se generaba el directorio: $path";
		}
	}

	private function showErrors( $GeneratorSchema )
	{
		$errors = $GeneratorSchema->getErrors();

		echo <<<EOT

ATENCION: Se encontraron los siguientes errores en el esquema...

EOT;
		foreach ( $errors as $error )
		{
			echo $error . "\n";
		}
		echo "
";
	}
}