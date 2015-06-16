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
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;

Class DatabaseCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('app:database')
			->setDescription('Crea las tablas en la base de datos desde la version del esquema especificado.')
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                'Version a utilizar'
            );
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
        $version = $input->getArgument('version');
        $helper = $this->getHelper('question');

        $path_schema = YS_APP . 'storage/schemas/';

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
			$output->writeln( PHP_EOL . " <error>No se encontro el esquema dentro del directorio: ".YS_SYSTEM."app/storage/schemas/{$version}</error>" . PHP_EOL );
			exit;
		}

		$this->createDatabase( $input, $output, $path_schema . $version );
	}

	private function createDatabase( $input, $output, $path_schema )
	{
		$fs = new Filesystem();
		$dateTime = new \DateTime();

		$output->write( PHP_EOL . " Actualizando la base de datos..." . PHP_EOL.PHP_EOL );

		if( !is_file( $path_schema.'/schema.php' ) )
		{
			$output->writeln( " <error>ATENCION: El esquema no fue creado.</error>" . PHP_EOL );

			return;
		}

        $DriverManager = \Db::model()->getDriverManager();
        $sm = $DriverManager->getSchemaManager();

        // Obtiene el esquema de la base de datos.
        $schema_current = $sm->createSchema();

		// Se Obtiene el objeto del esquema creado.
		$schema = include $path_schema.'/schema.php';

        $comparator = new \Doctrine\DBAL\Schema\Comparator();
        $schemaDiff = $comparator->compareSchemas( $schema_current, $schema);

        $queries = $schemaDiff->toSql($DriverManager->getDatabasePlatform());

		if(count($queries) == 0)
		{
			$output->writeln( PHP_EOL." <info>No hay nada para actualizar.</info>" );
			return;
		}

		$_q = "set foreign_key_checks = 0;";
		$DriverManager->query( $_q );
		$output->writeln( PHP_EOL." $_q".PHP_EOL );

		foreach($queries as $query)
		{
			$DriverManager->query($query);
			$output->writeln(" - $query");
		}

		$_q = "set foreign_key_checks = 1;";
		$output->writeln(PHP_EOL." $_q".PHP_EOL);
		$DriverManager->query($_q);

		$output->writeln("<info>La base de datos fue actualizada correctamente.</info>");
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