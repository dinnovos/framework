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
 * Ys_Shell
 * 
 * @author Jorge Gaitan
 */

namespace Kodazzi\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Kodazzi\Container\Service;

Class Shell
{
	public function console()
	{
		$cli = new Application('======= Kodazzi - Lista de Comandos =======');

		$cli->add(Service::get('command.schema'));
		$cli->add(Service::get('command.database'));
		$cli->add(Service::get('command.model'));
		$cli->add(Service::get('command.form'));
		$cli->add(Service::get('command.bundle'));
		$cli->add(Service::get('command.routes'));

		$cli->run();
	}

    public function execute(Command $command, array $input, array $options = array())
    {
        // set the command name automatically if the application requires
        // this argument and no command name was passed
        if (!isset($input['command'])
            && (null !== $application = $this->command->getApplication())
            && $application->getDefinition()->hasArgument('command')
        ) {
            $input = array_merge(array('command' => $this->command->getName()), $input);
        }

        $this->input = new ArrayInput($input);
        if (isset($options['interactive'])) {
            $this->input->setInteractive($options['interactive']);
        }

        $this->output = new StreamOutput(fopen('php://memory', 'w', false));
        if (isset($options['decorated'])) {
            $this->output->setDecorated($options['decorated']);
        }
        if (isset($options['verbosity'])) {
            $this->output->setVerbosity($options['verbosity']);
        }

        return $this->statusCode = $command->run($this->input, $this->output);
    }
}
