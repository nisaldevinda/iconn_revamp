<?php

namespace App\Http\Controllers;

use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use Laminas\Diactoros\Response as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;
use Dusterio\LumenPassport\LumenPassport;
use Illuminate\Support\Facades\DB;

/**
 * Class AccessTokenController
 * @package Dusterio\LumenPassport\Http\Controllers
 */
class AccessTokenController extends \Laravel\Passport\Http\Controllers\AccessTokenController
{
    /**
     * Authorize a client to access the user's account.
     *
     * @param  ServerRequestInterface  $request
     * @return Response
     */
    public function issueToken(ServerRequestInterface $request)
    {
        $response = $this->withErrorHandling(function () use ($request) {

            $input = (array) $request->getParsedBody();

            $clientId = isset($input['client_id']) ? $input['client_id'] : null;

            $clientSecret = isset($input['client_secret']) ? $input['client_secret'] : null;

            $requestedScopes = isset($input['scope']) ? explode(" ", $input['scope']) : [];

            $client = DB::table('oauth_clients')->where('id', $clientId)->first(['id', 'secret']);

            if (!is_null($client) && $clientSecret === $client->secret) {

                $clientScope = DB::table('oauth_client_scopes')->where('client_id', $client->id)->first(['allowed_scopes']);

                if (is_null($clientScope)) {
                    return response()->json(['message' => 'The requested scope is invalid, unknown, or malformed', 'data' => null], 500);
                }

                $allowedScopes = json_decode($clientScope->allowed_scopes, true);

                if (empty($allowedScopes)) {
                    return response()->json(['message' => 'The requested scope is invalid, unknown, or malformed', 'data' => null], 500);
                }

                $hasInvalidScope = false;

                foreach ($requestedScopes as $requestedScope) {
                    if (!in_array($requestedScope, $allowedScopes)) {
                        $hasInvalidScope = true;
                        break;
                    }
                }

                if ($hasInvalidScope) {
                    return response()->json(['message' => 'The requested scope is invalid, unknown, or malformed', 'data' => null], 500);
                }
                
            }

            // Overwrite password grant at the last minute to add support for customized TTLs
            $this->server->enableGrantType(
                $this->makePasswordGrant(), LumenPassport::tokensExpireIn(null, $clientId)
            );

            return $this->server->respondToAccessTokenRequest($request, new Psr7Response);
        });

        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            return $response;
        }

        $payload = json_decode($response->getBody()->__toString(), true);

        if (isset($payload['access_token'])) {
            /* @deprecated the jwt property will be removed in a future Laravel Passport release */
            $token = $this->jwt->parse($payload['access_token']);
            if (method_exists($token, 'getClaim')) {
                $tokenId = $token->getClaim('jti');
            } else if (method_exists($token, 'claims')) {
                $tokenId = $token->claims()->get('jti');
            } else {
                throw new \RuntimeException('This package is not compatible with the Laravel Passport version used');
            }

            $token = $this->tokens->find($tokenId);
            if (!$token instanceof Token) {
                return $response;
            }

            if ($token->client->firstParty() && LumenPassport::$allowMultipleTokens) {
                // We keep previous tokens for password clients
            } else {
                $this->revokeOrDeleteAccessTokens($token, $tokenId);
            }
        }

        return $response;
    }

    /**
     * Create and configure a Password grant instance.
     *
     * @return \League\OAuth2\Server\Grant\PasswordGrant
     */
    private function makePasswordGrant()
    {
        $grant = new \League\OAuth2\Server\Grant\PasswordGrant(
            app()->make(\Laravel\Passport\Bridge\UserRepository::class),
            app()->make(\Laravel\Passport\Bridge\RefreshTokenRepository::class)
        );

        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());

        return $grant;
    }

    /**
     * Revoke the user's other access tokens for the client.
     *
     * @param  Token $token
     * @param  string $tokenId
     * @return void
     */
    protected function revokeOrDeleteAccessTokens(Token $token, $tokenId)
    {
        $query = Token::where('user_id', $token->user_id)->where('client_id', $token->client_id);

        if ($tokenId) {
            $query->where('id', '<>', $tokenId);
        }

        $query->update(['revoked' => true]);
    }
}
