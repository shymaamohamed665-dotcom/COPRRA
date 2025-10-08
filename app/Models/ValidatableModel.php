<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class ValidatableModel extends Model
{
    protected ?\Illuminate\Support\MessageBag $errors = null;
}
