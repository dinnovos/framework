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
use Symfony\Component\Console\Question\Question;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Finder\Finder;
use Kodazzi\Container\Service;

Class ModelCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('app:models')
			->setDescription('Crea las clases del Modelo en el Bundle especificado como argumento.')
			->addArgument(
				'namespace',
				InputArgument::REQUIRED,
				'namespace of bundle'
			)
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                'Version a utilizar'
            );
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
        $helper = $this->getHelper('question');
		$bundle = $input->getArgument('namespace');
		$version = $input->getArgument('version');
        $yaml = new Parser();

		$bundle = trim($bundle, '/');
		$bundle = trim($bundle, '\\');

        $path_schema = Ki_APP . 'src/storage/schemas/';

        if($version === null)
        {
            $question = new Question(PHP_EOL .' Por favor, ingrese la version del esquema que desea utilizar [current]: ', 'current');
            $version = $helper->ask( $input, $output, $question );
        }

        if($version != 'current')
        {
			while( !preg_match('/^([1-9][0-9\.]+[0-9])+$/', $version) )
			{
				$output->writeln( PHP_EOL .' <error>ATENCION: La version no tiene un formato valido, debe ingresar por ejemplo: 1.0</error>' );

                $question = new Question(PHP_EOL .' Por favor, ingrese la version del esquema que desea utilizar [current]: ', 'current');
                $version = $helper->ask( $input, $output, $question );

                if( $version == 'current' )
                {
                    break;
                }
			}
		}

		if( !is_dir( $path_schema . $version ) )
		{
			$output->writeln( PHP_EOL . " <error>No se encontro el esquema dentro del directorio {$version}/ del bundle {$bundle}</error>" . PHP_EOL );
			exit;
		}

		// Obtiene el contenido del archivo *.yml
		$schema = array();
		$schema_bundle = array();
        $schema_temporary = array();
        $file_schema = $path_schema . $version . '/schema.yml';

        if(is_file($file_schema))
        {
            $schema = $yaml->parse(file_get_contents($file_schema));
        }

        $finder = new Finder();
        $finder->files()->name('*.yml')->in(str_replace('\\', '/',$path_schema . $version . '/bundles/'.$bundle));

        // Une todos los archivos yml en un solo array
        foreach( $finder as $file )
        {
            $schema_file = $yaml->parse(file_get_contents($file));

            // Concatena el esquema de cada archivo conseguido
            $schema_temporary = array_merge($schema_temporary, $schema_file);
        }

        // Recorre todos los modelos buscando si alguno tiene el valor de model_translatable
        // Y asi agregar este modelo al esquema
        foreach($schema_temporary as $model => $config)
        {
            $schema_bundle = array_merge($schema_bundle, array($model => $schema[$model]));

            if(array_key_exists('translatable', $config['options']) && array_key_exists($model.'Translation', $schema))
            {
                $schema_bundle = array_merge($schema_bundle, array($model.'Translation' => $schema[$model.'Translation']));
            }
        }

		$this->generateModels( $input, $output, $schema_bundle, $bundle );
	}

	public function generateModels( $input, $output, $schema, $bundle )
	{
		$GenerateClass = Service::get('generate_class');

		$output->write( PHP_EOL . "Lista de clases para el modelo:" . PHP_EOL );

		$this->mkdir( Ki_BUNDLES . $bundle . '/Models/Base/' );

		foreach($schema as $table => $options)
        {
            if(strpos($table, ':'))
            {
                $p = explode(':', $table);

                $table = $p[1];
            }

			if(! is_file(Ki_BUNDLES . $bundle . '/Models/' . ucfirst( $table ).'Model.php') )
			{
				$GenerateClass->setTemplate('Model');
				$GenerateClass->setNameClass(ucfirst($table).'Model');
				$GenerateClass->setNamespace(ucfirst( str_replace('/', '\\', $bundle) ) . '\Models');
				$GenerateClass->setNameClassExtend( 'Base\\'.ucfirst($table).'ModelBase' );
				$GenerateClass->create(Ki_BUNDLES . $bundle . '/Models/' . ucfirst( $table ).'Model', $options);

				$output->write( " - Clase Model $table del Bundle $bundle, fue creada correctamente." . PHP_EOL );
			}

			$options['package'] = $bundle;
			$GenerateClass->setTemplate( 'BaseModel' );
			$GenerateClass->setNameClass( ucfirst($table).'ModelBase' );
			$GenerateClass->setNamespace( ucfirst( str_replace('/', '\\', $bundle) ) . '\Models\Base' );
            $GenerateClass->setValues( array(
                'namespace_bundle' => ucfirst(str_replace('/', '\\', $bundle)),
                'model'     => ucfirst($table)
            ) );

			$GenerateClass->create( Ki_BUNDLES . $bundle . '/Models/Base/' . ucfirst( $table ).'ModelBase', $options );

			$output->write( " - Clase Model Base$table del Bundle $bundle, fue creada correctamente." . PHP_EOL );
		}

		$output->write( PHP_EOL . PHP_EOL );
	}

	private function mkdir($path)
	{
		$fs = new Filesystem();

		try
		{
			$fs->mkdir($path);

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
		echo "

";
		foreach ( $errors as $error )
		{
			echo $error . "\n";
		}
		echo "

";
	}
}