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
use React\EventLoop\LoopInterface;
use Symfony\Component\Process\Process;

class GroupQueue extends EventEmitter implements IQueue
{
    const JOB_START = "job_secondary_start";
    const JOB_FINISH = "job_secondary_finish";

    private $maxExecution = 4;
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
     * @var GroupStore
     */
    private $store;

    public function __construct($maxExcute = 4)
    {
        $this->store = new GroupStore();

        $this->on(self::JOB_START, array($this, 'onJobStart'));
        $this->on(self::JOB_FINISH, array($this, 'onJobFinish'));

        $this->maxExecution = $maxExcute;
        $this->loop = Factory::getLoop();
        $this->io = Factory::getIo();
    }

    /**
     * @return GroupStore
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * @param string $key
     * @return \SplDoublyLinkedList
     */
    public function getSubStore($key)
    {
        if(false === $this->store->get($key))
        {
            $this->store->add($key);
        }

        return $this->store->get($key);
    }

    public function onJobStart(\SplDoublyLinkedList $queue)
    {
        $this->executing++;
        $queue->rewind();

        while ($queue->count())
        {
            $item = $queue->shift();

            $command = $item[0];
            $output = $item[1];
            $cwd = $item[2];
            $timeout = $item[3];

            if ($this->io && $this->io->isDebug()) {
                $safeCommand = preg_replace('{(://[^:/\s]+:)[^@\s/]+}i', '$1****', $command);
                $this->io->writeError('<info>SAsync Executing command ('.($cwd ?: 'CWD').'): '.$safeCommand.'</info>');
            }

            $process = new Process($command, $cwd, null, null, $timeout);
            $process->run($output);

            if($process->isSuccessful())
            {
                if ($this->io && $this->io->isDebug())
                {
                    $this->io->writeError('Command output: (' . ($cwd ?: 'CWD') . '): 
                    <info>' . $process->getOutput() . '</info>');
                }
            }
            else
            {
                if(preg_match("/^git checkout ['|\"](.*?)['|\"] --/", $command, $match))
                {
                    if($branch = $match[1]) {

                        if (preg_match('{^[a-f0-9]{40}$}', $branch))
                        {
                            //TODO implement get hash
                        }
                        else
                        {
                            $command = "git checkout -B \"branchName\" \"composer/branchName\" --";
                            $command = str_replace("branchName", $branch, $command);

                            $process = new Process($command, $cwd, null, null, $timeout);
                            $process->run($output);
                        }

                        $this->io->writeError('<info>Running fallback: (' . ($cwd ?: 'CWD') . '): 
                        ' . $command . '</info>');
                    }
                }
                else
                {
                    throw new \RuntimeException('Failed to execute ' . $command . "\n\n" . $process->getErrorOutput());
                }
            }
        }

        $this->emit(self::JOB_FINISH);
    }

    public function onJobFinish()
    {
        $this->executing--;
        $this->done++;
    }

    private function run()
    {
        while($this->executing < $this->maxExecution && $this->store->count())
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
        $this->output();

        if($this->getStore()->count())
        {
            $this->run();
            $this->loop->run();
        }
    }

    /**
     * Register process to looper
     */
    private function registerJob()
    {
        /** @var \SplDoublyLinkedList $item */
        $item = $this->store->shift();

        $this->loop->addTimer(0.000001, function() use ($item) {
            $this->emit(self::JOB_START, array($item));
        });

    }

    private function output()
    {
        $this->io->writeError(
            '    Remaining Queue: <info>'.$this->getStore()->count(). '</info>'.
            ' Running: <info>'. $this->executing . '</info>');
    }
        
}