<?php
/**
 * Created by PhpStorm.
 * User: billy
 * Date: 4/24/16
 * Time: 18:12
 */

namespace Contemi\ComposerAsync;


use Composer\IO\IOInterface;
use Contemi\ComposerAsync\Queue\GroupQueue;
use Contemi\ComposerAsync\Queue\IQueue;
use Contemi\ComposerAsync\Queue\PrimaryQueue;
use React\EventLoop\LoopInterface;

class Factory
{
    
    const Primary = "PrimaryPipe";
    const Secondary = "SecondaryPipe";
    
    /**
     * @var IQueue[]
     */
    private static $pipe = array();

    /**
     * @var LoopInterface
     */
    private static $loop;

    /**
     * @var IOInterface
     */
    private static $io;

    /**
     * @param string $type
     * @return IQueue
     */
    private static function getPipe($type = Factory::Primary)
    {
        if(!isset(self::$pipe[$type]))
        {
            self::$pipe[$type] = ($type == Factory::Primary) ? new PrimaryQueue() : new GroupQueue();
        }
        
        return self::$pipe[$type];
    }

    /**
     * @return PrimaryQueue
     */
    public static function getPrimaryQueue()
    {
        return self::getPipe(self::Primary);
    }

    /**
     * @return GroupQueue
     */
    public static function getGroupQueue()
    {
        return self::getPipe(self::Secondary);
    }

    /**
     * @return LoopInterface
     */
    public static function getLoop()
    {
        if(!self::$loop)
        {
            self::$loop = \React\EventLoop\Factory::create();
        }
        
        return self::$loop;
    }
    
    public static function setIo(IOInterface $io)
    {
        return self::$io = $io;
    }

    /**
     * @return IOInterface
     */
    public static function getIo()
    {
        return self::$io;
    }
}