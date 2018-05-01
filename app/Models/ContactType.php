<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactType extends Model
{
    protected $table = 'contacts_types';
    protected $hidden = ['id', 'created_at', 'updated_at'];

    public function contacts() {
        return $this->hasMany('App\Models\Contact');
    }
}
