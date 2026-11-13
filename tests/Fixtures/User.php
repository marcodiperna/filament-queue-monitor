<?php

declare(strict_types=1);

namespace MarcoDiPerna\FilamentQueueMonitor\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $guarded = [];

    protected $table = 'users';
}
