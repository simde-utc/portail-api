<?php

namespace App\Models;

use Cog\Contracts\Ownership\Ownable as OwnableContract;
use Cog\Laravel\Ownership\Traits\HasMorphOwner;
use App\Traits\Model\HasCreatorSelection;
use App\Traits\Model\HasOwnerSelection;

class Calendar extends Model implements OwnableContract
{
    use HasMorphOwner, HasCreatorSelection, HasOwnerSelection;

    protected $fillable = [
        'name', 'description', 'color', 'visibility_id', 'created_by_id', 'created_by_type', 'owned_by_id', 'owned_by_type',
    ];

    protected $hidden = [
        'created_by_id', 'created_by_type', 'owned_by_id', 'owned_by_type', 'visibility_id',
    ];

    protected $with = [
        'created_by', 'owned_by', 'visibility',
    ];

	protected $withModelName = [
		'created_by', 'owned_by',
	];

    protected $must = [
        'description', 'color', 'owned_by',
    ];

    protected $selection = [
        'paginate' => null,
        'order' => null,
        'owner' => null,
        'creator' => null,
    ];

    public function events() {
        return $this->belongsToMany(Event::class, 'calendars_events')->withTimestamps();
    }

	public function visibility() {
    	return $this->belongsTo(Visibility::class);
    }

	public function user() {
		return $this->morphTo(User::class, 'owned_by');
	}

    public function followers() {
        return $this->belongsToMany(User::class, 'calendars_followers')->withTimestamps();
    }

	public function asso() {
		return $this->morphTo(Asso::class, 'owned_by');
	}

	public function client() {
		return $this->morphTo(Client::class, 'owned_by');
	}

	public function group() {
		return $this->morphTo(Group::class, 'owned_by');
	}

    public function isCalendarAccessibleBy(string $user_id): bool {
        return $this->owned_by->isCalendarAccessibleBy($user_id);
    }

    public function isCalendarManageableBy(string $user_id): bool {
        return $this->owned_by->isCalendarManageableBy($user_id);
    }
}