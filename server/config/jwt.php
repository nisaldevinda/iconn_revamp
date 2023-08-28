<?php

try {
    $privateKey = file_get_contents('../storage/jwt-private.key');
} catch (\Exception $e) {
    $privateKey = "-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQDadek9k9sjVGcD6qgMe/hoRk65HabPTGKteJhfeXuI2algGBiN
kMKlae+mcVKiAHT90CxbCB5qec2JfubYw0e50ndSBZHcxbrx+Tp/aG+ORtwJoSqT
NFczdvG7pJEFN6mh28MXpgX8gPYTz4b2HqQNRlWqyrc5srznWM1fiTOzeQIDAQAB
AoGAcEPt894a3GIQenvJlnjS5EtVQ15QjfFUOI9jxpS4flBU8XQQkheVX8o8BM3x
yBzyAklLshBPCSrFDGaxnS1lms4rnfWgaoFoPTeFKkhfBoS9HDVlNH+PKEgNKODr
TIXRAOZEzap72cEinoUIHyW4Dl6WvydVxchBgE5mkQ6RpHECQQD+mUYJdUCimTc+
ylSGeyTKZ7Vccg5cpS11J+5PY4eOuEWRLad2UQ1a7Zgn5k9DgSoIoe5GdgXe4xEA
FNlYwq+1AkEA26m4JkuhOUiU9blzsqSGR/x/PdyC5A66hpXHuPMKxGy27Po+2kAc
IechtnXNdq12ykcA513y/zS0f9CKWJXnNQJBAMguKYQ8PfLatzZWjbkjT90ZR98F
CsfLU/+ewuCG6EnOF1y74cTEm9SXpaARlNcF0s/wylF6cMk8Ddzbh70jblUCQHOw
S0BADMVyqKFR7tFjPSWkog8emAEskKhZMjsCJeWVrDHbCkdABJEGfgbuvCuI6EtY
Ye142YX3aCj42CQXrxECQEQCHepiDUhqTsiaThmnIgN9Q4Ivsg232cts75K+LzA8
XNpAVQwhGMvE5Xy4J1/lovo3hD+zJdkrF5VDFsMkIu8=
-----END RSA PRIVATE KEY-----";
}

try {
    $publicKey = file_get_contents('../storage/jwt-public.key');
} catch (\Exception $e) {
    $publicKey = "-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDadek9k9sjVGcD6qgMe/hoRk65
HabPTGKteJhfeXuI2algGBiNkMKlae+mcVKiAHT90CxbCB5qec2JfubYw0e50ndS
BZHcxbrx+Tp/aG+ORtwJoSqTNFczdvG7pJEFN6mh28MXpgX8gPYTz4b2HqQNRlWq
yrc5srznWM1fiTOzeQIDAQAB
-----END PUBLIC KEY-----";
}

return [

    'private_key' => $privateKey,
    'public_key' => $publicKey,
    'token_expiration_threshold' => env('JWT_TOKEN_EXPIRATION_THRESHOLD', 60 * 60),
    'refresh_token_expiration_threshold' => env('JWT_REFRESH_TOKEN_EXPIRATION_THRESHOLD', 24 * 60 * 60),

];
