<?php
/**
 * This file is part of the PPI Framework.
 *
 * @copyright  Copyright (c) 2011-2013 Paul Dragoonis <paul@ppi.io>
 * @license    http://opensource.org/licenses/mit-license.php MIT
 * @link       http://www.ppi.io
 */

namespace PPI\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A console command for retrieving information about services.
 *
 * @author      Vítor Brandão <vitor@ppi.io> <vitor@noiselabs.org>
 * @package     PPI
 * @subpackage  Console
 */
class ServiceManagerDebugCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('service-manager:debug')
            ->setDescription('Displays current services for an application')
            ->addOption(
                'invoke',
                null,
                InputOption::VALUE_NONE,
                'If set, we will actually try to invoke each service'
            )
            ->setHelp(<<<EOF
The <info>%command.name%</info> command displays all configured <comment>public</comment> services:

  <info>php %command.full_name%</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \LogicException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $verbose = OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity();
        $invoke = $input->getOption('invoke');

        $sm = $this->getServiceManager()->get('ServiceManager');
        $registeredServices = $sm->getRegisteredServicesReal();

        $lines = array();
        $pad = array(
            'id'    => 0,
            'type'  => strlen('Instance  '),
            'class' => strlen('Class name|type|alias')
        );
        $serviceTypeToColumnName = array(
            'invokableClasses'  => 'Invokable Class',
            'factories'         => 'Factory',
            'aliases'           => 'Alias',
            'instances'         => 'Instance'
        );

        foreach ($registeredServices as $type => $services) {
            foreach ($services as $key => $service) {
                $lines[$key]['type'] = $serviceTypeToColumnName[$type];
                if (strlen($key) > $pad['id']) {
                    $pad['id'] = strlen($key);
                }

                if (is_object($service)) {
                    $r = new \ReflectionObject($service);
                    $lines[$key]['class'] = $r->getName();
                } elseif (is_array($service)) {
                    $lines[$key]['class'] = 'Array';
                } elseif (is_string($service) && ($type != 'aliases')) {
                    $r = new \ReflectionClass($service);
                    $lines[$key]['class'] = $r->getName();
                } else { // Alias
                    $lines[$key]['class'] = $service;
                }

                $len = strlen($lines[$key]['class']);
                if ('aliases' == $type) {
                    $len += 10; // add the "alias for " prefix
                }
                if ($len > $pad['class']) {
                    $pad['class'] = $len;
                }
            }
        }

        ksort($lines);
        $output->write(sprintf('<comment>%s</comment> <comment>%s</comment> <comment>%s</comment>',
            str_pad('Service Id', $pad['id']),
            str_pad('Type', $pad['type']),
            str_pad('Class name|type|alias', $pad['class'])));
        $output->writeln($invoke ? '  <comment>Invokation status</comment>' : '');
        foreach ($lines as $id => $line) {
            $output->write(sprintf('<info>%s</info> ', str_pad($id, $pad['id'])));
            $output->write(sprintf('%s ', str_pad($line['type'], $pad['type'])));
            if ('Alias' == $line['type']) {
                $output->write(sprintf('<comment>alias for</comment> <info>%s </info>', str_pad($line['class'], $pad['class']-10)));
            } else {
                $output->write(sprintf('%s ', str_pad($line['class'], $pad['class'])));
            }
            if ($invoke) {
                try {
                    $sm->get($id);
                    $output->write(' <info>OK</info>');
                } catch (\Exception $e) {
                    $output->write(' <error>Failed</error> '.$e->getMessage());
                }
            }
            $output->writeln('');
        }
    }
}
