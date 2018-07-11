<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Asso;
use App\Models\Calendar;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\AbstractCalendarController;
use App\Services\Visible\Visible;
use App\Interfaces\CanHaveCalendars;
use App\Traits\HasVisibility;

/**
 * @resource Calendar
 *
 * Gestion des calendriers
 */
class CalendarController extends AbstractCalendarController
{
	public function __construct() {
		parent::__construct();

		$this->middleware(
			\Scopes::matchOne(array_merge(
				$this->populateScopes('user-get-calendars', 'created'),
				$this->populateScopes('user-get-calendars', 'owned-client'),
				$this->populateScopes('user-get-calendars', 'owned-asso')
			), array_merge(
				$this->populateScopes('client-get-calendars', 'created'),
				$this->populateScopes('client-get-calendars', 'owned-client'),
				$this->populateScopes('client-get-calendars', 'owned-asso')
			)),
			['only' => ['index', 'show']]
		);
		$this->middleware(
			\Scopes::matchOne(array_merge(
				$this->populateScopes('user-set-calendars', 'owned-client'),
				$this->populateScopes('user-set-calendars', 'owned-asso'),
				$this->populateScopes('user-create-calendars', 'owned'),
				$this->populateScopes('user-create-calendars', 'created')
			), array_merge(
				$this->populateScopes('client-set-calendars', 'owned-client'),
				$this->populateScopes('client-set-calendars', 'owned-asso'),
				$this->populateScopes('client-create-calendars', 'owned'),
				$this->populateScopes('client-create-calendars', 'created')
			)),
			['only' => ['store']]
		);
		$this->middleware(
			\Scopes::matchOne(array_merge(
				$this->populateScopes('user-set-calendars', 'created'),
				$this->populateScopes('user-set-calendars', 'owned-client'),
				$this->populateScopes('user-set-calendars', 'owned-asso')
			), array_merge(
				$this->populateScopes('client-set-calendars', 'created'),
				$this->populateScopes('client-set-calendars', 'owned-client'),
				$this->populateScopes('client-set-calendars', 'owned-asso')
			)),
			['only' => ['update']]
		);
		$this->middleware(
			\Scopes::matchOne(array_merge(
				$this->populateScopes('user-manage-calendars', 'created'),
				$this->populateScopes('user-manage-calendars', 'owned-client'),
				$this->populateScopes('user-manage-calendars', 'owned-asso')
			), array_merge(
				$this->populateScopes('client-manage-calendars', 'created'),
				$this->populateScopes('client-manage-calendars', 'owned-client'),
				$this->populateScopes('client-manage-calendars', 'owned-asso')
			)),
			['only' => ['destroy']]
		);
	}

	public function getCreaterOrOwner(Request $request, string $verb = 'create', string $type = 'created') {
		$scopeHead = \Scopes::isUserToken($request) ? 'user' : 'client';
		$scope = $scopeHead.'-'.$verb.'-calendars-'.$request->input($type.'_by_type',\Scopes::isClientToken($request) ? 'client' : 'user').'s-'.$type;

		if ($type === 'owned')
			$scope = array_keys(\Scopes::getRelatives($scopeHead.'-'.$verb.'-calendars-'.$request->input($type.'_by_type').'s-'.$type));

		if (!\Scopes::hasOne($request, $scope))
			abort(403, 'Il ne vous est pas autorisé de créer de calendriers');

		if ($request->filled($type.'_by_type')) {
			if ($request->filled($type.'_by_id')) {
				$createrOrOwner = resolve($this->types[$request->input($type.'_by_type')])->find($request->input($type.'_by_id'));

				if (\Auth::id() && !$createrOrOwner->isCalendarManageableBy(\Auth::id()))
					abort(403, 'L\'utilisateur n\'a pas les droits de création');
			}
			else if ($request->input($type.'_by_type', 'client') === 'client')
				$createrOrOwner = Client::find(\Scopes::getClient($request)->id);
			else if ($request->input($type.'_by_type', 'client') === 'asso')
				$createrOrOwner = \Scopes::getClient($request)->asso;
		}

		if (!isset($createrOrOwner))
			$createrOrOwner = \Scopes::isClientToken($request) ? \Scopes::getClient($request) : \Auth::user();

		if (!($createrOrOwner instanceof CanHaveCalendars))
			abort(400, 'La personne créatrice/possédeur doit au moins pouvoir avoir un calendrier');

		return $createrOrOwner;
	}

	/**
	 * List Calendars
	 *
	 * @return JsonResponse
	 */
	public function index(Request $request): JsonResponse {
		$calendars = Calendar::with(['owned_by', 'created_by', 'visibility'])->get()->filter(function ($calendar) use ($request) {
			return $this->tokenCanSee($request, $calendar, 'get');
		})->values()->map(function ($calendar) use ($request) {
			return $this->hideCalendarData($request, $calendar);
		});

		return response()->json($calendars, 200);
	}

	/**
	 * Create Calendar
	 *
	 * @param Request $request
	 * @return JsonResponse
	 */
	public function store(Request $request): JsonResponse {
		$inputs = $request->all();

		$owner = $this->getCreaterOrOwner($request, 'create', 'owned');

		if ($request->input('created_by_type') === 'client'
			&& $request->input('created_by_id', \Scopes::getClient($request)->id) === \Scopes::getClient($request)->id
			&& \Scopes::hasOne($request, (\Scopes::isClientToken($request) ? 'client' : 'user').'-create-calendars-'.$this->classToType(get_class($owner)).'s-owned-client'))
			$creater = Client::find(\Scopes::getClient($request)->id);
		else if ($request->input('created_by_type') === 'asso'
			&& $request->input('created_by_id', \Scopes::getClient($request)->asso->id) === \Scopes::getClient($request)->asso->id
			&& \Scopes::hasOne($request, (\Scopes::isClientToken($request) ? 'client' : 'user').'-create-calendars-'.$this->classToType(get_class($owner)).'s-owned-client'))
			$creater = \Scopes::getClient($request)->asso;
		else
			$creater = $this->getCreaterOrOwner($request, 'create', 'created');

		$inputs['created_by_id'] = $creater->id;
		$inputs['created_by_type'] = get_class($creater);
		$inputs['owned_by_id'] = $owner->id;
		$inputs['owned_by_type'] = get_class($owner);

		$calendar = Calendar::create($inputs);

		if ($calendar) {
			$calendar = $this->getCalendar($request, \Auth::user(), $calendar->id);
			$calendar = $this->hideCalendarData($request, $calendar);
			return response()->json($calendar, 201);
		}
		else
			return response()->json(['message' => 'Impossible de créer le calendrier'], 500);
	}

	/**
	 * Show Calendar
	 *
	 * @param  int $id
	 * @return JsonResponse
	 */
	public function show(Request $request, int $id): JsonResponse {
		$calendar = $this->getCalendar($request, \Auth::user(), $id);
		$calendar = $this->hideCalendarData($request, $calendar);

		return response()->json($calendar, 200);
	}

	/**
	 * Update Calendar
	 *
	 * @param Request $request
	 * @param  int $id
	 * @return JsonResponse
	 */
	public function update(Request $request, $id): JsonResponse {
		$calendar = $this->getCalendar($request, \Auth::user(), $id, 'set', true);
		$inputs = $request->all();

		if ($request->filled('owned_by_type')) {
			$owner = $this->getCreaterOrOwner($request, 'set', 'owned');

			$inputs['owned_by_id'] = $owner->id;
			$inputs['owned_by_type'] = get_class($owner);
		}

		if ($calendar->update($inputs))
			return response()->json($this->hideCalendarData($calendar), 200);
		else
			abort(500, 'Impossible de modifier le calendrier');
	}

	/**
	 * Delete Calendar
	 *
	 * @param  int $id
	 * @return JsonResponse
	 */
	public function destroy(Request $request, int $id): JsonResponse {
		$calendar = $this->getCalendar($request, \Auth::user(), $id, 'manage', true);
		$calendar->softDelete();

		abort(204);
	}
}
