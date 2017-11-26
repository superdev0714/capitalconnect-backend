<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['reference', 'success_url', 'failed_url', 'currency', 'first_name', 'last_name', 'company', 'email', 'phone', 'birthday', 'street', 'zip', 'city', 'country', 'amount', 'paygate_transaction', 'status'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['user_id'];

    public function user() {
        return $this->belongsTo('App\User');
    }
}
