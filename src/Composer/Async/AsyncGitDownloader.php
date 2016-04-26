<?php
/**
 * Created by PhpStorm.
 * User: billy
 * Date: 4/23/16
 * Time: 20:03
 */

namespace Contemi\ComposerAsync;

use Composer\Composer;
use Composer\Config;
use Composer\Downloader\GitDownloader;
use Composer\IO\IOInterface;
use Composer\EventDispatcher\EventDispatcher as ComposerEventDispatcher;
use Contemi\ComposerAsync\Event\EventDispatcher;


class AsyncGitDownloader
{
    static private $instance;

    public static function inject(Composer $composer, IOInterface $io)
    {
        Factory::setIo($io);

        if(!self::$instance)
        {
            $instance = new GitDownloader($io, $composer->getConfig());
            
            $reflect = new \ReflectionClass($instance);

            $props = $reflect->getProperties(\ReflectionProperty::IS_PRIVATE|\ReflectionProperty::IS_PROTECTED|\ReflectionProperty::IS_PUBLIC);

            $filesystem = null; $processEx = null;
            while(count($props))
            {
                $prop = $props[0];
                $prop->setAccessible(true);

                if($prop->getName() === 'gitUtil')
                {
                    if(count($props) == 1)
                    {
                        $prop->setValue($instance, new GitProcess(
                            $io,
                            $composer->getConfig(),
                            $processEx,
                            $filesystem
                        ));
                        array_shift($props);
                    }
                    else
                    {
                        array_push($props, array_shift($props));
                        continue;
                    }                    
                }
                
                if($prop->getName() === 'process')
                {
                    $processEx = new ProcessExecutor(
                        $io
                    );
                    $prop->setValue($instance, $processEx);
                    array_shift($props);                    
                }
                else
                {
                    $filesystem = $prop->getName() === 'filesystem' ? $prop->getValue($instance) : null;
                    array_shift($props);
                }

            }

            self::$instance = $instance;

            //Event
            $errorLevel = error_reporting();
            error_reporting(0); //Hacking to prevent error compatible with parent class
            $composer->setEventDispatcher(new EventDispatcher($composer, $io));
            error_reporting($errorLevel);
        }

        return self::$instance;
    }

    public static function restore(Composer $composer)
    {
        //Restore default event
        $composer->setEventDispatcher(new ComposerEventDispatcher($composer, Factory::getIo()));

        //Restore git
        return new GitDownloader(Factory::getIo(), $composer->getConfig());
    }
}