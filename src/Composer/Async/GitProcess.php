<?php
/**
 * Created by PhpStorm.
 * User: billy
 * Date: 4/23/16
 * Time: 22:57
 */

namespace Contemi\ComposerAsync;


use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Composer\Util\Git;

class GitProcess extends Git
{
    /** @var IOInterface */
    protected $io;
    /** @var Config */
    protected $config;
    /** @var ProcessExecutor */
    protected $process;
    /** @var Filesystem */
    protected $filesystem;

    public function __construct(IOInterface $io, Config $config, ProcessExecutor $processExe, Filesystem $fs)
    {
        $this->io = $io;
        $this->config = $config;
        $this->process = $processExe;        
        $this->filesystem = $fs;
    }
}