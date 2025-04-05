<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasUuids;

    // Ensure the model knows it's using a string primary key
    public $incrementing = false;

    protected $keyType = 'string';
}
