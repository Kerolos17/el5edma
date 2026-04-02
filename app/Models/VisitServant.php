<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class VisitServant extends Pivot
{
    protected $table = 'visit_servants';

    public $incrementing = true;

    public $timestamps = false;
}
