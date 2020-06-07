<?php

namespace Pulsar\Tests\Models;

use Pulsar\ACLModel;
use Pulsar\Model;

class TestModelNoPermission extends ACLModel
{
    protected function hasPermission($permission, Model $requester)
    {
        return false;
    }
}
