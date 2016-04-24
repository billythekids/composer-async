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


class AsyncGitDownloader
{
    static private $instance;

    static public function getInstance(Composer $composer, IOInterface $io)
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
        }

        return self::$instance;
    }
}