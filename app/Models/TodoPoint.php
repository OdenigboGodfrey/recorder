<?php

namespace App\MOdels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TodoPoint extends Model
{
    use SoftDeletes;
    protected $guarded = [];
}
