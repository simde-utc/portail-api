<?php

namespace App\Http\Controllers\v1\User;

use App\Http\Controllers\v1\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\Controller\v1\HasUsers;

/**
 * @resource User
 *
 * Gestion des utilisateurs
 */
class UserController extends Controller
{
	use HasUsers;

	public function __construct() {
		$this->middleware(
			\Scopes::matchOneOfDeepestChildren('client-get-users'),
			['only' => 'index']
		);
		$this->middleware(
			\Scopes::matchOneOfDeepestChildren('client-create-users'),
			['only' => 'store']
		);
		$this->middleware(
			\Scopes::matchAnyUser(),
			['only' => 'show']
		);
		$this->middleware(
			\Scopes::matchOneOfDeepestChildren('user-set-info', 'client-edit-users'),
			['only' => 'update']
		);
		$this->middleware(
			\Scopes::matchOneOfDeepestChildren('client-manage-users'),
			['only' => 'destroy']
		);
	}

	/**
	 * List Users
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request) {
		$choices = [];

		if (\Scopes::hasOne($request, 'client-get-users-active'))
			$choices[] = 'active';

		if (\Scopes::hasOne($request, 'client-get-users-inactive'))
			$choices[] = 'inactive';

		$choices = $this->getChoices($request, $choices);

		if (count($choices) === 2)
			$users = new User;
		else
			$users = User::where('active', \Scopes::hasOne($request, 'client-get-users-active'));

		$users = $users->getSelection()->map(function ($user) {
			return $this->hideData();
		});

		return response()->json($users, 200);
	}

	/**
	 * Create User
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request) {
		$active = $request->input('is_active');

		if ($active) {
			if (!\Scopes::hasOne($request, 'client-get-users-'.($active ? 'active' : 'inactive')))
				abort(403, 'Vous n\'avez pas le droit de créer ce type de compte');
		}
		else
			$active = \Scopes::hasOne($request, 'client-get-users-active');

		$user = User::create([
			'email' => $request->input('email'),
			'lastname' => strtoupper($request->input('lastname')),
			'firstname' => $request->input('firstname'),
			'is_active' => $active,
		]);

		// Envoyer un mail et vérifier en fonction de scopes

		if ($request->filled('details')) {
			if (!\Scopes::hasOne($request, 'client-create-info-details'))
				abort(403, 'Vous ne pouvez pas créer de détails');

			foreach ($request->input('details') as $key => $value) {
				$user->details()->create([
					'key' => $key,
					'value' => $value
				]);
			}
		}

		if ($request->filled('preferences')) {
			if (!\Scopes::hasOne($request, 'client-create-info-preferences'))
				abort(403, 'Vous ne pouvez pas créer de préférences');

			foreach ($request->input('preferences') as $key => $value) {
				$user->preferences()->create([
					'key' => $key,
					'value' => $value
				]);
			}
		}

		return response()->json($user, 201);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  string $id
	 * @return \Illuminate\Http\Response
	 */
	public function show(Request $request, string $user_id = null) {
		$user = $this->getUser($request, $user_id, true);
		$rightsOnUser = is_null($user_id) || (\Auth::id() && $user->id === \Auth::id());

		if ($rightsOnUser) {
			if (!\Scopes::has($request, 'user-get-info-identity-email'))
				$user->makeHidden('email');

			if (\Scopes::has($request, 'user-get-info-identity-type'))
				$user->type = $user->type();

			if ($request->has('allTypes')) {
				if (!\Scopes::has($request, 'user-get-info-identity-type'))
					abort(403, 'Vous n\'avez pas le droit d\'avoir accès aux types de l\'utilisateur');

				foreach ($user->types as $type) {
					$method = 'is'.ucfirst($type);
					$type = 'is_'.$type;

					if (method_exists($user, $method) && $user->$method())
						$user->$type = true;
					else
						$user->$type = false;
				}
			}
			else if ($request->has('withTypes')) {
				foreach (explode(',', $request->input('withTypes')) as $type) {
					try {
						if (!\Scopes::has($request, 'user-get-info-identity-type-'.$type))
							continue;

						$method = 'is'.ucfirst($type);
						$type = 'is_'.$type;

						if (method_exists($user, $method) && $user->$method())
							$user->$type = true;
						else
							$user->$type = false;
					} catch (PortailException $e) {
						abort(400, 'Le type '.$type.' n\'existe pas !');
					}
				}
			}

			if (!\Scopes::has($request, 'user-get-info-identity-timestamps'))
				$user->makeHidden('last_login_at')->makeHidden('created_at')->makeHidden('updated_at');

			if ($request->has('allDetails')) {
				if (!\Scopes::has($request, 'user-get-info-details'))
					abort(403, 'Il est nécessaire soit d\'avoir la permission d\'avoir tous les détails soient de spécifier lesquels voir');

				$user->details = $user->details()->allToArray();
			}
			else if ($request->filled('withDetails')) {
				$details = [];

				foreach (explode(',', $request->input('withDetails')) as $key) {
					try {
						if (!\Scopes::has($request, 'user-get-info-details-'.$key))
							abort(403, 'Vous n\'avez pas le droit d\'avoir accès à cette information');
					} catch (PortailException $e) {
						abort(403, 'Il n\'existe pas de détail utilisateur de ce nom: '.$key);
					}

					try {
						$details[$key] = $user->details()->valueOf($key);
					} catch (PortailException $e) {
						$details[$key] = null;
					}
				}

				$user->details = $details;
			}
		}
		else
			$user = $user->hideData();

		// Par défaut, on retourne au moins l'id de la personne et son nom
		return response()->json($user);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  string $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, string $user_id = null) {
		$user = $this->getUser($request, $user_id);

		$user->email = $request->input('email', $user->email);
		$user->lastname = $request->input('lastname', $user->lastname);
		$user->firstname = $request->input('firstname', $user->firstname);
		$user->is_active = $request->input('is_active', $user->is_active);
		$user->save();

		return response()->json($user, 200);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  string $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(string $user_id) {
		abort(403, "Wow l'ami, patience, c'est galère ça...");
	}
}