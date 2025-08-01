<?php

namespace ClarkeWing\Handoff\Tests\Fixtures;

/**
 * A test user model which provides a custom handoff identifier
 * Used to test exception handling in HandoffController
 */
class UserWithCustomIdentifier extends User
{
    public function getHandoffIdentifierName(): string
    {
        return 'email';
    }
}
