<?php

namespace DirectoryTree\Metrics\Tests;

use DirectoryTree\Metrics\HasMetrics;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasMetrics;

    protected $guarded = [];
}
