<?php
/**
 * Created by PhpStorm.
 * User: billy
 * Date: 4/23/16
 * Time: 20:03
 */

namespace Contemi\ComposerAsync;


use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\InstallerEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Contemi\ComposerAsync\Event\InstallerEvent;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    private $enable = true;

    public function activate(Composer $composer, IOInterface $io)
    {
        if($this->enable)
        {
            $io->writeError('<info>Async initial.</info>');

            $this->composer = $composer;
            $this->io = $io;

            $composer->getDownloadManager()->setDownloader('git', AsyncGitDownloader::inject($composer, $io));
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            InstallerEvents::POST_DEPENDENCIES_SOLVING => array(
                array('onPostSolving', 0),
            )
        );
    }

    /**
     * @param InstallerEvent $ev
     */
    public function onPostSolving(InstallerEvent $ev)
    {
        if($this->enable)
        {
            $ops = $ev->getOperations();
            if (count($ops))
            {
                foreach ($ops as $key => $op)
                {
                    $type = $op->getJobType();
                    if ('install' === $type || 'update' === $type ) {
                        $clone = clone $op;
                        $this->composer->getInstallationManager()->execute(
                            $this->composer->getRepositoryManager()->getLocalRepository(), $clone
                        );
                        unset($ops[$key]);
                    }
                }
            }

            $start = microtime(true);

            $ev->operationsRef = $ops;
            Factory::getPrimaryQueue()->execute();
            Factory::getGroupQueue()->execute();

            //Restore to origin
            $this->composer->getDownloadManager()->setDownloader('git', AsyncGitDownloader::restore($this->composer));
            $this->io->writeError('<info>Async finish in: '.(microtime(true) - $start).'</info>');
        }
    }
}