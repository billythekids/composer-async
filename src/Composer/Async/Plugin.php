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
use Composer\Installer\InstallerEvent;
use Composer\Installer\InstallerEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PreFileDownloadEvent;

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
            echo "Async call".PHP_EOL;

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
            ),
            PluginEvents::PRE_FILE_DOWNLOAD => array(
                array('onPreFileDownload', 0),
            ),
        );
    }

    public function onPreFileDownload(PreFileDownloadEvent $ev)
    {
        echo "Process url: ";
        echo $ev->getProcessedUrl().PHP_EOL;
    }

    public function onPostSolving(InstallerEvent $ev)
    {
        if($this->enable)
        {
            $ops = $ev->getOperations();
            if (count($ops))
            {
                foreach ($ops as $op)
                {
                    $type = $op->getJobType();

                    if ('install' === $type || 'update' === $type ) {
                        $this->composer->getInstallationManager()->execute(
                            $this->composer->getRepositoryManager()->getLocalRepository(), $op
                        );
                    }
                }
            }

            Factory::getPrimaryQueue()->execute();
            Factory::getGroupQueue()->execute();

            $this->io->writeError('<info>Async finish.</info>');

            //Restore to origin
            $this->composer->getDownloadManager()->setDownloader('git', AsyncGitDownloader::restore($this->composer));
        }
    }
}