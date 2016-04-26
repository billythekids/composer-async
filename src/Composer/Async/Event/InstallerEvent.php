<?php
/**
 * Created by PhpStorm.
 * User: billy.nguyen
 * Date: 4/26/2016
 * Time: 2:07 PM
 */

namespace Contemi\ComposerAsync\Event;

use Composer\Composer;
use Composer\DependencyResolver\PolicyInterface;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\Request;
use Composer\Installer\InstallerEvent as ComposerInstallerEvent;
use Composer\IO\IOInterface;
use Composer\Repository\CompositeRepository;

/**
 * An event for all installer.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class InstallerEvent extends ComposerInstallerEvent
{

    public $operations;
    public $operationsRef;

    public function __construct($eventName, Composer $composer, IOInterface $io, $devMode, PolicyInterface $policy, Pool $pool, CompositeRepository $installedRepo, Request $request, &$operations)
    {
        $this->operations = $operations;
        $this->operationsRef = &$operations;

        parent::__construct($eventName, $composer, $io, $devMode, $policy, $pool, $installedRepo, $request);
    }

    /**
     * @return OperationInterface[]
     */
    public function getOperations()
    {
        return $this->operations;
    }
}