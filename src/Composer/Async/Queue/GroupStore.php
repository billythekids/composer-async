<?php
/**
 * Created by PhpStorm.
 * User: billy
 * Date: 4/25/16
 * Time: 23:42
 */

namespace Contemi\ComposerAsync\Queue;


class GroupStore
{
    private $data = array();

    public function add($key)
    {
        $this->data[$key] = new \SplDoublyLinkedList();
    }

    public function get($key)
    {
        if(isset($this->data[$key]))
        {
            return $this->data[$key];
        }

        return false;
    }

    public function shift()
    {
        return array_shift($this->data);
    }

    public function count()
    {
        return sizeof($this->data);
    }
}