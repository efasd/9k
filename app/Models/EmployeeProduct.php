<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeProduct extends Model
{
    //
    public $table = 'employee_product';

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
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'product_id' => 'required',
        'user_id' => 'required'
    ];

    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [

    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function employee()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }

    public function customizable()
    {
        return $this->morphTo();
    }
}
