<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Hotel extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'image', 'address', 'price', 'latitude', 'longitude'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['created_at', 'updated_at'];

    public function getImageAttribute($value) {
        if (empty($value))
            return null;
        else if (filter_var($value, FILTER_VALIDATE_URL))
            return $value;
        else
            return URL::to(env('PATH_HOTELS', '/') . $value);
    }

    public function city() {
        return $this->belongsTo('App\City');
    }
}
