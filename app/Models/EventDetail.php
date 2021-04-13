<?php
/**
 * Model corresponding to event details.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\Model\HasKeyValue;
use App\Models\Event;

class EventDetail extends Model
{
    use HasKeyValue;

    protected $table = 'events_details';

    protected $fillable = [
        'event_id', 'key', 'value', 'type',
    ];

    protected $must = [
        'key', 'value'
    ];

    /**
     * Relation with an event.
     * @return mixed
     */
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
