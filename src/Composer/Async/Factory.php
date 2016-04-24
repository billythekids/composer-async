<?php
/**
 * Created by PhpStorm.
 * User: billy
 * Date: 4/24/16
 * Time: 18:12
 */

namespace Contemi\ComposerAsync;


use React\EventLoop\LoopInterface;

class Factory
{
    
    const Primary = "PrimaryPipe";
    const Secondary = "SecondaryPipe";
    
    /**
     * @var Pipeline[]
     */
    static private $pipe = array();

    /**
     * @var LoopInterface
     */
    static private $loop;

    static private $io;

    /**
     * @param string $type
     * @return Pipeline
     */
    public static function getPipe($type = Factory::Primary)
    {
        if(!isset(self::$pipe[$type]))
        {
            self::$pipe[$type] = new Pipeline();
        }
        
        return self::$pipe[$type];
    }

    /**
     * @return \React\EventLoop\ExtEventLoop|\React\EventLoop\LibEventLoop|\React\EventLoop\LibEvLoop|LoopInterface|\React\EventLoop\StreamSelectLoop
     */
    static public function getLoop()
    {
        if(!self::$loop)
        {

            self::$loop = \React\EventLoop\Factory::create();            
        }
        
        return self::$loop;
    }
    
    static public function setIo($io)
    {
        return self::$io = $io;
    }

    static public function getIo()
    {
        return self::$io;
    }
}