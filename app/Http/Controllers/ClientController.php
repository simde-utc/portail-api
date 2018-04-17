<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Laravel\Passport\Token;
use Lcobucci\JWT\Parser;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;

/**
 * @resource OAuth Client
 *
 * Gère le client OAuth
 */
class ClientController extends Controller
{
	/**
	 * Client Info
	 *
	 * Retourne les informations (scopes) sur le client en cours
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request) {
		$bearerToken = $request->bearerToken();
		$tokenId = (new Parser())->parse($bearerToken)->getHeader('jti');
		$client = Token::find($tokenId)->client->toArray();
		$client['scopes'] = json_decode($client['scopes'], true);

		return $client;
	}

	/**
	 * Client Users
	 *
	 * Retourne les users qui ont authorisé les actions du client en cours
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function getUsers(Request $request) {
		$bearerToken = $request->bearerToken();
		$tokenId = (new Parser())->parse($bearerToken)->getHeader('jti');
		$clientId = Token::find($tokenId)->client_id;
		$tokens = Token::where('client_id', $clientId);

		if ($request->input('revoked'))
			$tokens->where('revoked', $request->input('revoked') == 1 ? 1 : 0);

		$result = [];

		foreach ($tokens->get()->makeHidden('id')->makeHidden('session_id')->groupBy('user_id') as $userId => $tokenList) {
			$scopes = [];

			foreach ($tokenList as $token) {
				foreach ($token->scopes as $scope)
					$scopes[$scope] = '';
			}

			array_push($result, [
				'user_id' => $userId === '' ? null : $userId,
				'scopes' => array_keys($scopes),
			]);
		}

		return $result;
	}

	/**
	 * Client User info
	 *
	 * Retourne les users qui ont authorisé les actions du client en cours
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function getUser(Request $request, int $userId) {
		$bearerToken = $request->bearerToken();
		$tokenId = (new Parser())->parse($bearerToken)->getHeader('jti');
		$clientId = Token::find($tokenId)->client_id;
		$tokens = Token::where('client_id', $clientId)->where('user_id', $userId);

		if ($request->input('revoked'))
			$tokens->where('revoked', $request->input('revoked') == 1 ? 1 : 0);

		return $tokens->get()->makeHidden('id')->makeHidden('session_id');
	}

	/**
	 * Delete User Authorizations to Client
	 * 
	 * Supprime les autorisations d'un utilisateur pour le client
	 */
	public function destroy(Request $request, int $userId) {
		$bearerToken = $request->bearerToken();
		$tokenId = (new Parser())->parse($bearerToken)->getHeader('jti');
		$clientId = Token::find($tokenId)->client_id;

		if (Token::where('client_id', $clientId)->where('user_id', $userId)->where('revoked', false)->delete() === 0)
			return response()->json(['message' => 'Aucun token supprimé'], 404);
		else
			return response()->json(['message' => 'Tokens associés à l\'utilisateur supprimés avec succès'], 202);
	}

	/**
	 * Delete all Users Authorizations to Client
	 * 
	 * Suppression des autorisations de tous les utilisateurs pour le client
	 */
	public function destroyAll(Request $request) {
		$bearerToken = $request->bearerToken();
		$tokenId = (new Parser())->parse($bearerToken)->getHeader('jti');
		$clientId = Token::find($tokenId)->client_id;

		if (Token::where('client_id', $clientId)->where('revoked', false)->delete() === 0)
			return response()->json(['message' => 'Aucun token supprimé'], 404);
		else
			return response()->json(['message' => 'Tous vos tokens ont été supprimés avec succès'], 202);
	}

	/**
	 * Delete current User Authorizations to Client
	 * 
	 * Suppression des autorisations pour l'utilisateur courrant
	 */
	public function destroyCurrent(Request $request) {
		Token::where('client_id', $request->user()->token()->client_id)->where('user_id', $request->user()->id)->where('revoked', false)->delete();

		return response()->json(['message' => 'Tokens associés à l\'utilisateur supprimés avec succès'], 202);
	}
}
