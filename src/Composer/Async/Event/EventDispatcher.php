<?php
/**
 * Created by PhpStorm.
 * User: billy.nguyen
 * Date: 4/26/2016
 * Time: 2:04 PM
 */

namespace Contemi\ComposerAsync\Event;


use Composer\DependencyResolver\PolicyInterface;
use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\Request;
use Composer\EventDispatcher\EventDispatcher as ComposerEventDispatcher;
use Composer\Repository\CompositeRepository;

class EventDispatcher extends ComposerEventDispatcher
{
    /**
     * Dispatch a installer event.
     *
     * @param string              $eventName     The constant in InstallerEvents
     * @param bool                $devMode       Whether or not we are in dev mode
     * @param PolicyInterface     $policy        The policy
     * @param Pool                $pool          The pool
     * @param CompositeRepository $installedRepo The installed repository
     * @param Request             $request       The request
     * @param array               $operations    The list of operations
     *
     * @return int return code of the executed script if any, for php scripts a false return
     *             value is changed to 1, anything else to 0
     */
    public function dispatchInstallerEvent($eventName, $devMode, PolicyInterface $policy, Pool $pool, CompositeRepository $installedRepo, Request $request, array &$operations = array())
    {
        return $this->doDispatch(new InstallerEvent($eventName, $this->composer, $this->io, $devMode, $policy, $pool, $installedRepo, $request, $operations));
    }
}