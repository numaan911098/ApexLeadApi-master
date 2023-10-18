<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Plan;

class OneToolUser extends Model
{
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'role_type',
        'in_trial',
        'plan_id',
        'user_id',
        'status',
    ];

    /**
     * Get user.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Get user array.
     *
     * @return array
     */
    public function toOneToolUserArray()
    {
        return [
            'id'         => $this->user_id,
            'email'      => $this->user->email,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'plan_id'    => Plan::find($this->plan_id)->onetool_plan_id,
            'role_type'  => $this->role_type,
            'in_trial'   => $this->in_trial,
            'company_id' => null,
            'created_on' => $this->created_at->timestamp * 1000,
            'status'     => $this->status,
        ];
    }
}
