<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Str;
use \Firebase\JWT\JWT;
use Illuminate\Support\Facades\Hash;
use App\Traits\ServiceResponser;

trait JWTUtil
{
    use ServiceResponser;

    protected function getAccessToken($user, $issuedTime = null, $device)
    {
        if (!isset($user)) {
            return $this->error(500, null, "Unknown error.");
        }

        $tokenId = Str::uuid();
        $issuedAt = empty($issuedTime) ? time() : $issuedTime;
        $expire = null;

        $privateKey = config('jwt.private_key');

        $tokenData = [
            "jti" => $tokenId,
            "iat" => $issuedAt,
            "userId" => $user->id
        ];

        if ($device !== 'mobile') {
            $expire = $issuedAt + config('jwt.token_expiration_threshold');
            $tokenData["exp"] = $expire;
        }

        $accessToken = JWT::encode($tokenData, $privateKey, 'RS256');

        return [
            "token_id" => $tokenId,
            "access_token" => $accessToken,
            "access_token_expire_at" => $expire,
        ];
    }

    protected function getRefreshToken($aceessToken)
    {
        $refreshTokenSecret = Str::uuid() . $aceessToken['token_id'];
        $expire = time() + config('jwt.refresh_token_expiration_threshold');

        return [
            "refresh_token" => Hash::make($refreshTokenSecret),
            "refresh_token_expire_at" => $expire,
        ];
    }

    protected function decodeAuthHeader($authToken, $isExpiredToken = false)
    {
        if (empty($authToken)) {
            return $this->error(401, "Authorization header not found", null);
        }

        try {
            $token = $authToken;
            $publicKey = config('jwt.public_key');

            if ($isExpiredToken) {
                $tokenSegment = explode('.', $token);

                if (count($tokenSegment) != 3) {
                    return $this->error(401, "Invalid authorization token.", null);
                }

                list($headb64, $bodyb64, $cryptob64) = $tokenSegment;
                $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64));
            } else {
                $payload = JWT::decode($token, $publicKey, array('RS256'));
            }

            return $this->success(200, "Success", $payload);
        } catch (Exception $e) {
            return $this->error(401, $e->getMessage(), null);
        }
    }

    protected function getAuthToken($authHeader)
    {
        $authorizeArtifacts = explode(" ", $authHeader);
        if ($authorizeArtifacts[0] != 'Bearer') {
            return null;
        }
        return $authorizeArtifacts[1];
    }
}
