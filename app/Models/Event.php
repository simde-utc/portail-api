<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Calendar;
use App\Models\Visibility;
use App\Models\User;
use App\Models\Asso;
use App\Models\Group;

class Event extends Model
{
    protected $fillable = [
        'name', 'begin_at', 'end_at', 'created_by',
    ];

    public function calendars() {
        return $this->hasMany(Calendars::class, 'calendars_events');
    }

	public function users() {
		return $this->morphTo(User::class, 'created_by');
	}

	public function assos() {
		return $this->morphTo(Asso::class, 'created_by');
	}

	public function groups() {
		return $this->morphTo(Group::class, 'created_by');
	}
}
