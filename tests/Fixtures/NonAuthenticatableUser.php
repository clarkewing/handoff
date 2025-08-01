<?php

namespace ClarkeWing\Handoff\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * A test model that does not implement Authenticatable interface
 * Used to test exception handling in HandoffController
 */
class NonAuthenticatableUser extends Model
{
    protected $fillable = ['email'];
}
