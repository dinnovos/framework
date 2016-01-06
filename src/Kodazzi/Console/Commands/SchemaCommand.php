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
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;
use Kodazzi\Container\Service;

Class SchemaCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('app:schema')
			->setDescription('Crea el esquema desde todos los bundles configurados en AppKernel.')
			->addArgument(
				'action',
				InputArgument::OPTIONAL,
				'create o freeze',
				'create'
			)
            ->addArgument(
                'behavior',
                InputArgument::OPTIONAL,
                'overwrite'
            );
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
        $path_schema = Ki_APP . 'src/storage/schemas/';
		$action = $input->getArgument('action');
		$behavior = $input->getArgument('behavior');
        $yaml = new Parser();

		if( !in_array($action, array('create', 'freeze') ) )
		{
			$output->writeln( PHP_EOL . 'El parametro "action" debe ser create o freeze. Por defecto es create.' . PHP_EOL );

			exit;
		}

        $bundles = Service::getNamespacesBundles();

        $_schema = array();
        $mirrors = array();

        foreach($bundles as $bundle)
        {
            $dir_schema = str_replace('\\', '/', Ki_BUNDLES.$bundle.'config/schema');

            if(is_dir($dir_schema))
            {
                $mirrors[$bundle] = $bundle;

                $finder = new Finder();
                $finder->files()->name('*.yml')->in($dir_schema);

                // Une todos los esquemas en un solo array
                foreach( $finder as $file )
                {
                    // Concatena el esquema de cada archivo conseguido
                    $_schema = array_merge($_schema, $yaml->parse(file_get_contents($file)));
                }
            }
        }

        if(count($mirrors) == 0)
        {
            $output->write( PHP_EOL." <info>No se ha encontrado al menos un esquema en los bundles registrados.</info>" . PHP_EOL );

            exit;
        }

		$ValidateSchema = Service::get('validate_schema');

		/* Valida los archivos *.yml y muestra los posibles errores */
		if ( !$ValidateSchema->isValid( $_schema ) )
		{
			$this->showErrors( $ValidateSchema );

			exit;
		}

		/* Obtiene el array() del esquema */
		$schema = $ValidateSchema->getSchema();

		switch( strtoupper($action) )
		{
			case 'CREATE':

				$this->createSchema($input, $output, $path_schema, $schema, $behavior);

                $this->mkdir($path_schema.'current/bundles');
                $this->mkdir($path_schema.'current/tmp');

                $fs = new Filesystem();

                $_path_schema_bundle = str_replace('\\', '/',$path_schema.'current/bundles/');
                $_path_schema_tmp =  str_replace('\\', '/',$path_schema.'current/tmp/');

                foreach($mirrors as $namespace => $mirror)
                {
                    $path_source = str_replace('\\', '/',Ki_BUNDLES.$namespace.'config/schema');

                    // Hace un espejo desde el esquema del bundle al directorio temporal
                    $fs->mirror($path_source, $_path_schema_tmp.$namespace, null, array('override'=>true,'delete'=>true));
                }

                // Hace un espejo desde el directorio temporal al current/bundles/
                $fs->mirror($_path_schema_tmp, $_path_schema_bundle, null, array('override'=>true,'delete'=>true));

                // Elimina el directorio temporal
                $fs->remove($_path_schema_tmp);

				break;

			case 'FREEZE':

				$this->freezeSchema( $input, $output, $path_schema, $schema, $action );
				break;
		}
	}

	private function createSchema( $input, $output, $path_schema, $schema, $behavior )
	{
		$GenerateClass = Service::get('generate_class');
        $prefix = Service::get('config')->get('db', 'prefix', '');
        $helper = $this->getHelper('question');
		$fs = new Filesystem();
		$dateTime = new \DateTime();

		$output->write(PHP_EOL . " Creando el esquema..." . PHP_EOL.PHP_EOL);

		if($behavior != 'overwrite' && is_file($path_schema.'current/schema.php'))
		{
            $question = new ConfirmationQuestion(' <question>Ya existe una version del esquema, desea reemplazarlo [n]?</question> ', false);

			if ( !$helper->ask($input, $output, $question) )
			{
				$output->writeln( " <error>ATENCION: El esquema no fue creado.</error>" . PHP_EOL );

				return;
			}
		}

		// Crea el directorio src/storage/schema/current
		$this->mkdir( $path_schema.'current' );

		// vuelca el arreglo PHP a YAML
		$dumper = new Dumper();
		$content_yaml = $dumper->dump( $schema );

		// Crea el archivo yml dentro de src/storage/schema/current.
		$fs->dumpFile( $path_schema.'current/schema.yml', $content_yaml);
		$output->writeln( PHP_EOL." <info>- Se genero el archivo schema.yml correctamente.</info>" );

		// Crea el archivo yml dentro de src/storage/schema/current.
		$content_readme = 'Creado: '.$dateTime->format('Y-m-d H:i:s');
		$fs->dumpFile($path_schema.'current/readme.md', $content_readme);
		$output->writeln(" <info>- Se genero el archivo readme.md correctamente.</info>");

		$GenerateClass->setTemplate( 'Doctrine' );
        $GenerateClass->setValues(array('_prefix'=>$prefix));
		$GenerateClass->create( $path_schema.'current/schema', $schema );
		$output->writeln(" <info>- Se genero el archivo schema.php correctamente.</info>" );

		// Se Obtiene el objeto del esquema creado.
		$schema = include $path_schema.'current/schema.php';

		// Se crea el archivo .sql que contendra la estructura del esquema para la base de datos.
		$querys = $schema->toSql(Service::get('db')->getDriverManager()->getDatabasePlatform());

		$sql = "";
		foreach( $querys as $query )
		{
			$sql .= "$query;\n";
		}

		$fs->dumpFile($path_schema.'current/database.sql',$sql);
		$output->writeln(" <info>- Se genero el archivo database.sql correctamente.</info>");

		$output->writeln( PHP_EOL." <info>EL esquema fue creado correctamente en: ".Ki_SYSTEM."app/src/storage/schemas/current/</info>" );
	}

	private function freezeSchema( $input, $output, $path_schema, $schema )
	{
        $helper = $this->getHelper('question');
		$first = true;

		$fs = new Filesystem();

		do
		{
			if($first)
			{
                $question = new Question(PHP_EOL .' Por favor, ingrese la version del esquema: ', null);
                $version = $helper->ask( $input, $output, $question );
			}
			else
			{
				$output->writeln( PHP_EOL .' <error>ATENCION: La version no tiene un formato valido, debe ingrear por ejemplo: 1.0</error>'. PHP_EOL );

                $question = new Question(PHP_EOL .' Por favor, ingrese la version del esquema: ', null);
                $version = $helper->ask( $input, $output, $question );
			}

			$first = false;

			while( preg_match('/^([1-9][0-9\.]+[0-9])+$/', $version) && is_file($path_schema.$version.'/schema.php') )
			{
				$output->write( PHP_EOL .' <error>ATENCION: Ya existe un esquema con la misma version.</error>'. PHP_EOL );
				$first = true;
				$version = null;
			}
		}
		while( !preg_match('/^([1-9][0-9\.]+[0-9])+$/', $version) );

		// Crea el directorio para la version del esquema.
		$this->mkdir($path_schema.$version);

        $fs->mirror($path_schema.'current', $path_schema.$version, null, array('override'=>true,'delete'=>true));

		$output->write( " <info>Se ha congelado correctamente su esquema actual a la version $version.</info>" . PHP_EOL );
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