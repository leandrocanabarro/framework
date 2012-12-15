<?php
/**
 * This file is part of the PPI Framework.
 *
 * @copyright   Copyright (c) 2012 Paul Dragoonis <paul@ppi.io>
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        http://www.ppi.io
 */

namespace PPI\ServiceManager;

use PPI\ServiceManager\Config\SessionConfig;
use PPI\ServiceManager\Config\ModuleConfig;
use PPI\ServiceManager\Config\RouterConfig;
use PPI\ServiceManager\Config\TemplatingConfig;
use PPI\ServiceManager\Config\ServiceManagerConfig;
use PPI\ServiceManager\ParameterBag;

/**
 * ServiceManager builder.
 *
 * @author     Vítor Brandão <vitor@ppi.io>
 * @package    PPI
 * @subpackage ServiceManager
 */
class ServiceManagerBuilder extends ServiceManager
{
    protected $config;

    public function __construct(array $config = array())
    {
        $this->config = $config;

        $smConfig = isset($this->config['service_manager']) ? $this->config['service_manager'] : array();
        parent::__construct(new ServiceManagerConfig($smConfig));
    }

    public function build()
    {
        $this->compile();

        $this->setService('ApplicationConfig', $this->config);

//         foreach(array(
//             //new SessionConfig(),
//             //new ModuleConfig(),
//             //new RouterConfig(),
//             //new TemplatingConfig()
//         ) as $serviceConfig) {
//             $serviceConfig->configureServiceManager($this);
//         }
        return $this;
    }

    /**
     * Resolves parameter values using Symfony's ParameterBag.
     */
    public function compile()
    {
        if (isset($this->config['parameters'])) {
            $parameterBag = new ParameterBag($this->config['parameters']);
            $parameterBag->resolve();
            $this->config['parameters'] = $parameterBag->all();
        } else {
            $this->config['parameters'] = array();
        }
    }
}