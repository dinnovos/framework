<?php
/**
 * This file is part of the Kodazzi Framework.
 *
 * (c) Jorge Gaitan <jgaitan@kodazzi.com>
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
use Symfony\Component\Console\Question\Question;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Finder\Finder;
use Kodazzi\Container\Service;

Class FormsCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('app:forms')
			->setDescription('Crea las clases para los formularios del Bundle especificado como argumento.')
			->addArgument(
				'namespace',
				InputArgument::REQUIRED,
				'namespace of bundle'
			)->addArgument(
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
				$output->writeln( PHP_EOL .' <error>ATENCION: La version no tiene un formato valido, debe ingrear por ejemplo: 1.0</error>' );

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

        $this->createForms( $input, $output, $bundle, $schema_bundle );
	}

	private function createForms( $input, $output, $bundle, $schema )
	{
		$GenerateClass = Service::get( 'generate_class' );

		/* Crea el directorio donde se crearan las clases de los formularios */
		$this->mkdir( Ki_BUNDLES . $bundle . '/Forms/Base' );

		$output->write( PHP_EOL . "Lista de Clases Forms:" . PHP_EOL );

		foreach ( $schema as $table => $options )
		{
			/* Si la clase extendida existe no la sobreescribe */
			if ( !is_file( Ki_BUNDLES . $bundle . '/Forms/' . ucfirst( $table ) . 'Form.php' ) )
			{
				$GenerateClass->setTemplate( 'Forms' );
				$GenerateClass->setNameClass(  ucfirst( $table ) . 'Form' );
				$GenerateClass->setNamespace( ucfirst( str_replace('/', '\\', $bundle) ) . '\Forms' );
                $GenerateClass->setNameClassExtend( 'Base\\'.ucfirst($table).'FormBase' );
				$GenerateClass->create( Ki_BUNDLES . $bundle . '/Forms/' . ucfirst( $table ) . 'Form', $options );

				$output->write( " - Clase Form '" . ucfirst( $table ) . "Form' fue creada correctamente." . PHP_EOL );
			}

			/* Genera la clase base */
			$GenerateClass->setTemplate( 'FormsBase' );
			$GenerateClass->setNameClass( ucfirst( $table ) . 'FormBase' );
			$GenerateClass->setNamespace( ucfirst( str_replace('/', '\\', $bundle) ) . '\Forms\Base' );
			$GenerateClass->setValues( array(
				'namespace_base_model'  => ucfirst( $bundle ) . '\Models\\',
				'model'		            => ucfirst( $bundle ) . '\Models\\' . ucfirst( $table ).'Model',
                'form_translation'      => ucfirst( str_replace('/', '\\', $bundle) ) . '\Forms\\'.ucfirst( $table )
			) );

			$GenerateClass->create( Ki_BUNDLES . $bundle . '/Forms/Base/' . ucfirst( $table ) . 'FormBase', $options );

			$output->write( " - Clase Form '" . ucfirst( $table ) . "FormBase' creada correctamente." . PHP_EOL );
		}

		$output->write( PHP_EOL . PHP_EOL );
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