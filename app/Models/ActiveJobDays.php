<?php

namespace App\Models;

use Eloquent as Model;

class ActiveJobDays extends Model
{
    public $timestamps = true;

    public $table = 'active_job_days';

    public $fillable = [
        'employee_id'
    ];

    protected $casts = [
        'employee_id'
    ];

    protected $appends = [

    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function users()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }

    public function activeJobDaysMorph()
    {
        return $this->morphTo();
    }
}
