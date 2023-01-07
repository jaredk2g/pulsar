<?php

namespace Pulsar\Tests\Models;

use Pulsar\ACLModel;
use Pulsar\Model;

class TestModelNoPermission extends ACLModel
{
    protected function hasPermission(string $permission, Model $requester): bool
    {
        return false;
    }
}
