<?php
/**
 * Created by PhpStorm.
 * User: billy
 * Date: 4/24/16
 * Time: 18:11
 */

namespace Contemi\ComposerAsync\Queue;


use Composer\IO\IOInterface;
use Contemi\ComposerAsync\Factory;
use Evenement\EventEmitter;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;

class PrimaryQueue extends EventEmitter implements IQueue
{
    const JOB_START = "job_start";
    const JOB_FINISH = "job_finish";

    private $maxExecution = 24;
    private $executing = 0;
    private $done = 0;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var \SplObjectStorage
     */
    private $store;

    public function __construct()
    {
        $this->store = new \SplObjectStorage();

        $this->on(self::JOB_START, array($this, 'onJobStart'));
        $this->on(self::JOB_FINISH, array($this, 'onJobFinish'));

        $this->loop = Factory::getLoop();
        $this->io = Factory::getIo();
    }

    public function getStore()
    {
        return $this->store;
    }

    public function onJobStart()
    {
        $this->executing++;
    }

    public function onJobFinish()
    {
        $this->executing--;
        $this->done++;

        $this->run();
    }

    private function run()
    {
        $remaining = $this->getStore()->count() - ($this->done + $this->executing);

        while($this->executing < $this->maxExecution && $remaining > 0)
        {
            $this->registerJob();
        }
        
        $this->output();
    }

    /**
     * Processing a queue
     */
    public function execute()
    {
        if($this->getStore()->count())
        {
            $this->getStore()->rewind();
            
            $this->run();
            $this->loop->run();
        }
    }

    /**
     * Register process to looper
     */
    private function registerJob()
    {
        if($this->getStore()->valid())
        {
            /** @var Process $process */
            $process = $this->getStore()->current(); // similar to current($s)
            $data   = $this->getStore()->getInfo();
            $callback = $data[0];
            $cmd = $data[1];

            $process->on('exit', function() {
                $this->emit(self::JOB_FINISH, func_get_args());
            });

            $this->loop->addTimer(0.000001, function($timer) use ($process, $callback, $cmd) {
                $process->start($timer->getLoop(), 0.000001);
                $process->stdout->on('data', function($output) use ($callback) {
                    if($output) {
                        //$this->io->writeError('    <info>Output: '.$output."</info>");
                        call_user_func_array($callback, [null, $output]);
                    }
                });

            });

            $this->emit(self::JOB_START, array($process));
            $this->getStore()->next();
        }
    }

    private function output()
    {
        $this->io->writeError(
            '    Total Queue: <info>'.$this->getStore()->count(). '</info>'.
            ' Running: <info>'. $this->executing . '</info>'.
            ' Remaining: <info>'. ($this->getStore()->count() - $this->done).'</info>');
    }
        
}