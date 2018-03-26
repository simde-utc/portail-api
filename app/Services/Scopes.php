<?php

namespace App\Services;

use Curl;

/**
 * Cette classe permet de récupérer des informations concernant un membre de l'UTC
 */
class Scopes {
	private $scopes;

	public function __construct() {
		$this->scopes = config('scopes');
	}

	/**
	 * Génère le scope et les hérédités
	 * @param  string $prefix
	 * @param  array $subScopes
	 * @return array
	 */
	private function generate(string $prefix, array $subScopes) {
		$scopes = [];

		foreach ($subScopes as $name => $data) {
			$prefix = $prefix.'-'.$name;

			if (isset($data['scopes']))
				$scopes = array_merge($scopes, $this->generate($prefix, $data['scopes']));

			$scopes[$prefix] = $data['description'];
		}

		return $scopes;
	}

	/**
	 * Renvoie tous les scopes et les hérédités
	 * @param  string $prefix
	 * @param  array $subScopes
	 * @return array
	 */
	public function all() {
		$scopes = [];

		foreach ($this->scopes as $type => $categories) {
			foreach ($categories as $name => $categorie) {
				foreach ($categorie['verbs'] as $verb => $data) {
					$prefix = $type.'-'.$verb.'-'.$name;

					if (isset($data['scopes']))
						$scopes = array_merge($scopes, $this->generate($prefix, $data['scopes']));

					$scopes[$prefix] = $data['description'];
				}
			}
		}

		return $scopes;
	}

	/**
	 * Donne le verbe qui suit par héridité montante ou descendante
	 * @param  string  $verb
	 * @param  boolean $up
	 * @return array        liste des verbes à suivre
	 */
	private function nextVerbs(string $verb, $up = false) {
		if ($up) {
			switch ($verb) {
				case 'get':
				case 'set':
				return ['manage'];
				break;

				case 'create':
				case 'edit':
				case 'remove':
				return ['set'];
				break;

				default:
				return [];
			}
		}
		else {
			switch ($verb) {
				case 'manage':
					return ['get', 'set'];
					break;

				case 'set':
					return ['create', 'edit', 'remove'];
					break;

				default:
					return [];
			}
		}
	}

	/**
	 * Recherche le scope existant (qui doit exister) et sa descendance
	 * @param  string $scope
	 * @return array
	 */
	private function find(string $scope) {
		$elements = explode('-', $scope);

		if (count($elements) < 3)
			throw new \Exception('Le scope '.$scope.' est incorrect et doit au moins posséder un système d\'authentification, un verbe et une catégorie');

		if (!isset($this->scopes[$elements[0]][$elements[2]]['verbs'][$elements[1]]))
			return [];

		$current = $this->scopes[$elements[0]][$elements[2]]['verbs'][$elements[1]];
		for ($i = 3; $i < count($elements); $i++) {
			if (!isset($current['scopes'][$elements[$i]]))
				return [];

			$current = $current['scopes'][$elements[$i]];
		}

		if ($current === [] || !isset($current['description']))
			throw new \Exception('Le scope '.$scope.' est mal défini dans le fichier de config');

		else
			return [
				$scope => $current,
			];
	}

	/**
	 * Renvoie le scope (doit exister !) avec sa description
	 * @param  string $scope
	 * @return array      scope => description
	 */
	public function get(string $scope) {
		$current = $this->find($scope);

		if ($current === [])
			return [];

		return [
			$scope => $current[$scope]['description'],
		];
	}

	/**
	 * Renvoie le scope et ses parents ou ses hérédités (prend en compte l'héridité des verbes)
	 *
	 * @param string $scope
	 * @param bool $down Permet de spécifier dans quel sens de l'héridité à générer
	 * @return array
	 */
	public function getRelatives(string $scope = null, $up = false) {
		if ($scope === null)
			return $this->all();

		$current = $this->find($scope);

		if ($current === [])
			return [];

		$scopes = [
			$scope => $current[$scope]['description'],
		];

		$elements = explode('-', $scope);

		if ($up) {
			for ($i = count($elements) - 1; $i > 2; $i--) {
				array_pop($elements);
				$scopes = array_merge($scopes, $this->getRelatives(implode('-', $elements), $up));
			}

			$elements = explode('-', $scope);
		}
		else if (isset($current[$scope]['scopes'])) {
			$scopes = array_merge($scopes, $this->generate($scope, $current[$scope]['scopes']));
		}

		$nextVerbs = $this->nextVerbs($elements[1], $up);

		if ($nextVerbs !== []) {
			foreach($nextVerbs as $nextVerb) {
				$elements[1] = $nextVerb;
				$scopes = array_merge($scopes, $this->getRelatives(implode('-', $elements), $up));
			}
		}

		return $scopes;
	}

	/**
	 * Retourne la liste des scopes et des ses parents (prise en compte de l'héridité des verbes)
	 *
	 * @param string $scope
	 * @param array $scopes
	 * @return array
	 */
	private function getMatchingScopes(array $scopes = []) {
		if ($scopes === [])
			throw new \Exception('Il est nécessaire de définir au moins un scope ou d\'utiliser matchAny([$userMustBeConnected])');

		$matchingScopes = [];

		foreach ($scopes as $scope) {
			$elements = explode('-', $scope);

			if (!isset($middleware))
				$middleware = $elements[0];
			elseif ($middleware !== $elements[0])
				throw new \Exception('Les scopes ne sont pas définis avec les mêmes types d\'authentification !'); // Des scopes commençant par c- et u-

			$current = $this->getRelatives($scope, true);

			if ($current === [])
				throw new \Exception('Le scope '.$scope.' n\'existe pas !');

			$matchingScopes = array_merge($matchingScopes, $current);
		}

		return array_keys($matchingScopes);
	}

	/**
	 * Retourne les Middleware d'authentification
	 *
	 * @param string $scope
	 * @param array $scopes
	 * @return array
	 */
	public function matchAny(bool $userMustBeConnected = false) {
		return $userMustBeConnected ? 'auth:api' : 'auth.client';
	}

	/**
	 * Retourne les Middleware à utiliser pour accéder à une route en matchant au moins un scope parmi la liste
	 *
	 * @param string/array $scopes
	 * @return array
	 */
	public function matchOne($scopes = []) {
		if (is_array($scopes))
			$scopeList = $this->getMatchingScopes($scopes);
		else
			$scopeList = $this->getMatchingScopes([$scopes]);

		return [
			$this->matchAny(explode('-', $scopeList[0])[0] === 'u'),
			'scope:'.implode(',', $scopeList),
			'auth.check',
		];
	}

	/**
	 * Retourne les Middleware à utiliser pour accéder à une route en matchant tous les scopes ou leurs parents de la liste
	 *
	 * @param string/array $scopes
	 * @return array
	 */
	public function matchAll(array $scopes = []) {
		if (count($scopes) < 2)
			return $this->matchOne($scope, $scopes);

		$middlewares = [];

		foreach ($scopes as $scope) {
			$scopeList = $this->getMatchingScopes([$scope]);

			$elements = explode('-', $scopeList[0]);

			if (!isset($middleware))
				$middleware = $elements[0];
			elseif ($middleware !== $elements[0])
				throw new \Exception('Les scopes ne sont pas définis avec les mêmes types d\'authentification !'); // Des scopes commençant par c- et u-

			array_push($middlewares, 'scope:'.implode(',', $scopeList));
		}

		if ($middleware !== 'a') {
			$middlewares = array_merge([
				$this->matchAny($middleware === 'u')
			], $middlewares);
		}

		array_push($middlewares, 'auth.check');
		return $middlewares;
	}

	/**
	 * Génère une exception si les scopes ne sont correspondent pas au bon type d'authentification
	 * @param  array  $scopes
	 * @param  string $grantType
	 */
	public function checkScopesForGrantType(array $scopes, string $grantType = null) {
		if ($scopes === [])
			return;

		foreach ($scopes as $scope) {
			$elements = explode('-', $scope);

			if (!isset($middleware))
				$middleware = $elements[0];
			elseif ($middleware !== $elements[0])
				throw new \Exception('Les scopes ne sont pas définis avec les mêmes types d\'authentification !'); // Des scopes commençant par c- et u-
		}

		if ($middleware === 'c' && $grantType !== 'client_credentials' || $grantType === 'client_credentials' && $middleware !== 'c')
			throw new \Exception('Les scopes ne sont pas définis pour le bon type d\'authentification !'); // Des scopes commençant par c- et u-
	}
}