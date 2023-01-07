<?php

namespace Pulsar\Tests\Models;

use Pulsar\ACLModel;
use Pulsar\Model;

class AclObject extends ACLModel
{
    public $first = true;

    protected function hasPermission(string $permission, Model $requester): bool
    {
        if ('whatever' == $permission) {
            // always say no the first time
            if ($this->first) {
                $this->first = false;

                return false;
            }

            return true;
        } elseif ('do nothing' == $permission) {
            return 5 == $requester->id();
        }
    }
}
