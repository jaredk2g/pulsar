<?php

namespace Pulsar\Tests\Models;

use Pulsar\Event\AbstractEvent;
use Pulsar\Model;
use Pulsar\Property;

class TransactionModel extends Model
{
    protected static function getProperties(): array
    {
        return [
            'name' => new Property(
                required: true,
                validate: ['string', 'min' => 5],
            ),
        ];
    }

    protected function initialize(): void
    {
        parent::initialize();

        self::deleting(function (AbstractEvent $modelEvent) {
            if ('delete fail' == $modelEvent->getModel()->name) {
                $modelEvent->stopPropagation();
            }
        });
    }

    protected function usesTransactions(): bool
    {
        return true;
    }
}
