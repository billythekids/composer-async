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
    private $compose;

    public function activate(Composer $composer, IOInterface $io)
    {
        echo "Async call".PHP_EOL;

        $this->compose = $composer;
        $composer->getDownloadManager()->setDownloader('git', AsyncGitDownloader::getInstance($composer, $io));
        
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
        $ops = $ev->getOperations();
        if (count($ops))
        {
            foreach ($ops as $op)
            {
                $type = $op->getJobType();

                if ('install' === $type || 'update' === $type ) {
                    $this->compose->getInstallationManager()->execute(
                        $this->compose->getRepositoryManager()->getLocalRepository(), $op
                    );
                }
            }
        }

        Factory::getPipe(Factory::Primary)->execute();
        Factory::getPipe(Factory::Secondary)->execute();
    }
}