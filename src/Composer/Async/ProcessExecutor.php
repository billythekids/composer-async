<?php
/**
 * Created by PhpStorm.
 * User: billy
 * Date: 4/24/16
 * Time: 14:50
 */

namespace Contemi\ComposerAsync;

use Composer\Util\Platform;
use React\ChildProcess\Process as ChildProcess;
use Symfony\Component\Process\Process;


class ProcessExecutor extends \Composer\Util\ProcessExecutor
{
    /**
     * runs a process on the commandline
     *
     * @param  string $command the command to execute
     * @param  mixed  $output  the output will be written into this var if passed by ref
     *                         if a callable is passed it will be used as output handler
     * @param  string $cwd     the working directory
     * @return int    statuscode
     */
    public function execute($command, &$output = null, $cwd = null)
    {

        if ($this->io && $this->io->isDebug()) {
            $safeCommand = preg_replace('{(://[^:/\s]+:)[^@\s/]+}i', '$1****', $command);
            $this->io->writeError('Executing command ('.($cwd ?: 'CWD').'): '.$safeCommand);
        }
        
        $pipeType = isset($cwd) && !empty($cwd) ? Factory::Secondary : Factory::Primary;

        // make sure that null translate to the proper directory in case the dir is a symlink
        // and we call a git command, because msysgit does not handle symlinks properly
        if (null === $cwd && Platform::isWindows() && false !== strpos($command, 'git') && getcwd()) {
            $cwd = realpath(getcwd());
        }

        $this->captureOutput = count(func_get_args()) > 1;
        $this->errorOutput = null;

        $callback = is_callable($output) ? $output : array($this, 'outputHandler');

        if ($this->io && $this->io->isDebug()) {
            // Put all "git clone command" on Primary queue to allow fetching at once
            $this->io->writeError('     Register job into Queue ' . $pipeType);
        }

        if($pipeType === Factory::Primary) {
            
            $process = new ChildProcess($command, $cwd);
            Factory::getPrimaryQueue()->getStore()->attach($process, array($callback, $command, $cwd));
        }
        else {
            Factory::getGroupQueue()->getSubStore($cwd)->push(array(
                $command,
                $callback,
                $cwd,
                static::getTimeout()
            ));

        }
        
        return 0;
    }
}