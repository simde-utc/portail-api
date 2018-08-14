<?php

namespace App\Traits\Controller\v1;

use App\Exceptions\PortailException;
use App\Models\User;
use App\Models\Calendar;
use App\Models\Event;
use App\Facades\Ginger;
use App\Models\Model;
use Illuminate\Http\Request;

trait HasCalendars
{
	use HasEvents {
		HasEvents::isPrivate as isEventPrivate;
		HasEvents::tokenCanSee as tokenCanSeeEvent;
	}

	public function isPrivate($user_id, $model = null) {
		if ($model === null)
			return false;

		if ($model instanceof Event)
			return $this->isEventPrivate($user_id, $model);

		return $model->owned_by->isCalendarAccessibleBy($user_id);
    }

	// Uniquement les followers et ceux qui possèdent le droit peuvent le voir
	protected function isCalendarFollowed(Request $request, Calendar $calendar, string $user_id) {
		return (
			$calendar->followers()->wherePivot('user_id', $user_id)->exists()
			&& \Scopes::hasOne($request, \Scopes::getTokenType($request).'-get-calendars-users-followed-'.\ModelResolver::getName($calendar->owned_by_type))
		);
	}

	protected function getCalendar(Request $request, User $user = null, string $id, string $verb = 'get') {
		$calendar = Calendar::find($id);

		if ($calendar) {
			if (!$this->isCalendarFollowed($request, $calendar, $user->id)) {
				if (!$this->tokenCanSee($request, $calendar, $verb))
					abort(403, 'L\'application n\'a pas les droits sur ce calendrier');

				if ($user && !$this->isVisible($calendar, $user->id))
					abort(403, 'Vous n\'avez pas les droits sur ce calendrier');
			}

			if ($verb !== 'get' && \Scopes::isUserToken($request) && !$calendar->owned_by->isCalendarManageableBy(\Auth::id()))
				abort(403, 'Vous n\'avez pas les droits suffisants');

			return $calendar;
		}

		abort(404, 'Impossible de trouver le calendrier');
	}

	protected function getEventFromCalendar(Request $request, User $user, Calendar $calendar, int $id, string $verb = 'get') {
		$event = $calendar->events()->find($id);

		if ($event) {
			if (!$this->tokenCanSee($request, $event, $verb, 'events'))
				abort(403, 'L\'application n\'a pas les droits sur cet évènenement');

			if ($user && !$this->isVisible($event, $user->id))
				abort(403, 'Vous n\'avez pas les droits sur cet évènenement');

			return $event;
		}

		abort(404, 'L\'évènement n\'existe pas ou ne fait pas parti du calendrier');
	}

	protected function tokenCanSee(Request $request, Model $model, string $verb, string $type = 'calendars') {
		return $this->tokenCanSeeEvent($request, $model, $verb, $type);
	}
}