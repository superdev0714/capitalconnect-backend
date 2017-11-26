<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Support\Facades\URL;

class User extends Model implements AuthenticatableContract,
                                    AuthorizableContract,
                                    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['first_name', 'last_name', 'email', 'password', 'mobile', 'photo'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token', 'created_at', 'updated_at'];

    public function getPhotoAttribute($value) {
        if (empty($value))
            return null;
        else if (filter_var($value, FILTER_VALIDATE_URL))
            return $value;
        else
            return URL::to(env('PATH_PHOTOS', '/') . $value);
    }

    public function removeOldPhoto() {
        if ($this->getOriginal('photo') != null) {
            $photo = $this->getOriginal('photo');
            if (!filter_var($photo, FILTER_VALIDATE_URL))
                @unlink(public_path(env('PATH_PHOTOS', '/')) . $photo);
        }
    }
}
