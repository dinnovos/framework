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
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;
use Kodazzi\Container\Service;

use Symfony\Component\Console\Style\SymfonyStyle;

Class RoutesCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('app:routes')
			->setDescription('Visualiza las rutas del proyecto')
            ->addArgument(
                'namespace',
                InputArgument::OPTIONAL,
                'namespace of bundle'
            );
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$namespace = $input->getArgument('namespace');

        /** @var RouteCollection $RouteCollection */
        $RouteCollection = Service::get('kernel.routes');

        $Routes = $RouteCollection->all();

        $io = new SymfonyStyle($input, $output);

        $io->newLine();

        $rows = array();

        /** @var Route $Route */
        foreach($Routes as $name => $Route)
        {
            $path = $Route->getPath();
            $local = $Route->getOption('_locale');
            $controller = $Route->getDefault('controller');
            $host = $Route->getHost();
            $methods = implode(', ', $Route->getMethods());
            $schemes = implode(', ', $Route->getSchemes());
            $_requirements = $Route->getRequirements();
            $requirements = null;

            $nameAndPath = ($local) ? str_replace("-{$local}", '', $name) : $name;
            $nameAndPath .= ":\n  $path \n";

            foreach($_requirements as $var => $patt)
            {
                $requirements .= "\"$var=$patt\" ";
            }

            $requirements = ($requirements) ? rtrim($requirements, ',') : '';

            $rows[] = array(
                $nameAndPath,
                $controller,
                $local,
                $methods,
                $host,
                $schemes,
                $requirements
            );

        }

        $io->table(
            array('Name and Path', 'Controller', 'Local', 'Methods', 'Host', 'Schemes', 'Requirements'),
            $rows
        );

        $io->success('Se han mostrado las rutas del proyecto exitosamente.');
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