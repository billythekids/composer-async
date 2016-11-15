<?php
/**
 * Created by PhpStorm.
 * User: billy
 * Date: 4/25/16
 * Time: 22:29
 */

namespace Contemi\ComposerAsync\Queue;


interface IQueue
{
    /**
     * @return void
     */
    public function execute();

    /**
     * @return \Iterator
     */
    public function getStore();
}