<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAppointment extends Model
{
    //
    public $timestamps = true;

    public $table = 'employee_appointments';

    public $fillable = [
        'product_id',
        'user_id'
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'product_id' => 'integer',
        'user_id' => 'integer'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function users()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function products()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id', 'id');
    }
}
