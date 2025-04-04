<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasUuids;

    // Ensure the model knows it's using a string primary key
    public $incrementing = false;
    protected $keyType = "string";
}
