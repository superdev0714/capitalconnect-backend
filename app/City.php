<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class City extends Model
{

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
            return URL::to(env('PATH_CITIES', '/') . $value);
    }

    public function hotels() {
        return $this->hasMany('App\Hotel');
    }
}
