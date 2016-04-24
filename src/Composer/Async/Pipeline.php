<?php
/**
 * Created by PhpStorm.
 * User: billy
 * Date: 4/24/16
 * Time: 18:11
 */

namespace Contemi\ComposerAsync;


use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;

class Pipeline extends \SplObjectStorage
{
    private $maxLoop = 24;

    public function execute()
    {
        $this->rewind();
        $loop = Factory::getLoop();
        $io = Factory::getIo();

        $runCounter = 0; $done = 0;

        $io->writeError('    <info>Total Queue: '.$this->count()."</info>");

        while($this->valid()) 
        {
            $process = $this->current(); // similar to current($s)
            $data   = $this->getInfo();
            $callback = $data[0];
            $cmd = $data[1];

            $process->on('exit', function() use (&$runCounter, $loop, $io, &$done) {
                $runCounter--; $done++;
                $io->writeError(
                    '    <info>Total Queue: '.$this->count(). ' Running: '. $runCounter . ' Remainning: '. ($this->count() - $done)
                    .'</info>');
            });

            $this->registerJob($loop, $process, $callback, $cmd, $io);

            if($runCounter++ == $this->maxLoop)
            {
                $io->writeError(
                    '    <info>Total Queue: '.$this->count(). ' Running: '. $runCounter . ' Remainning: '. ($this->count() - $done)
                    .'</info>');

                $loop->run();
            }

            $this->next();
        }
        
        $loop->run();
    }

    /**
     * @param LoopInterface $loop
     * @param Process $process
     * @param callable $callback
     * @param string $cmd
     * @param IOInterface $io
     */
    private function registerJob($loop, $process, $callback, $cmd, $io)
    {
        $loop->addTimer(0.000001, function($timer) use ($process, $callback, $cmd, $io) {

            $process->start($timer->getLoop(), 0.000001);

            $process->stdout->on('data', function($output) use ($callback, $io) {
                if($output) {
                    $io->writeError('    <info>Output: '.$output."</info>");
                    call_user_func_array($callback, [null, $output]);
                }
            });

        });
    }
        
}