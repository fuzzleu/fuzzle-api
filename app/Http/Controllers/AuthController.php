<?php

namespace App\Http\Controllers;

use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function signUp(\App\Http\Requests\SignUpRequest $request)
    {
        try {
            $user = \App\Models\User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'image' => 'https://d3djy7pad2souj.cloudfront.net/fuzzle/avatars/avatar' . rand(1, 5) . '_fuzzle_H265P.png',
                'password' => \Illuminate\Support\Facades\Hash::make($request->input('password')),
                'role' => 'user'
            ]);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], 400);
        }

        return response([
            'message' => 'User registered. Logging in ...',
            'cookie' => json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'image' => $user->image,
                'role' => $user->role,
                'token' => "Bearer " . JWTAuth::attempt($request->only(['email', 'password'])),
                'ttl' => JWTAuth::factory()->getTTL() * 60
            ])
        ], 200);
    }

    public function signIn(\App\Http\Requests\SignInRequest $request)
    {
        try {
            $credentials = $request->only(['email', 'password']);
            if ($token = JWTAuth::attempt($credentials)) {
                $user = JWTAuth::user();
                return response([
                    'message' => 'Signed in',
                    'cookie' => json_encode([
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'image' => $user->image,
                        'role' => $user->role,
                        'token' => "Bearer " . $token,
                        'ttl' => JWTAuth::factory()->getTTL() * 60
                    ])
                ])->withCookie(cookie('user', json_encode([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'image' => $user->image,
                    'role' => $user->role,
                    'token' => "Bearer " . $token
                ]), JWTAuth::factory()->getTTL()));
            }

            return response(['message' => 'Incorrect password!'], 400);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response(['message' => $e->getMessage()], 401);
        }
    }

    public function signInGoogle(\Illuminate\Http\Request $request)
    {
        $client = new \Google\Client();
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);

        $client->addScope(\Google\Service\Oauth2::USERINFO_PROFILE . ' email');
        $client->setAccessType('offline');
        $client->setRedirectUri('https://fuzzleapi.herokuapp.com/api/auth/signin/google/callback');

        if ($request->header('access_token'))
            return $this->authGoogle($client, $request->header('access_token'));
        $headers = [header('Location: ' . filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL))];

        return response(null, 301, $headers);
    }

    public function signInGoogleCallback()
    {
        $client = new \Google\Client();
        $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $client->setRedirectUri('https://fuzzleapi.herokuapp.com/api/auth/signin/google/callback');

        if (!isset($_GET['code'])) {
            $auth_url = $client->createAuthUrl();
            $headers = [header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL))];
            return response(null, 301, $headers);
        } else {
            $client->fetchAccessTokenWithAuthCode($_GET['code']);
            return $this->authGoogle($client);
        }
    }

    private function authGoogle(\Google\Client $client, string|array $access_token = null)
    {
        if ($access_token)
            $client->setAccessToken($access_token);
        $auth = new \Google\Service\Oauth2($client);
        $data = $auth->userinfo->get();

        try {
            if (!$user = \App\Models\User::where('email', $data['email'])->first())
                $user = \App\Models\User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'image' => $data['picture']
                ]);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], 400);
        }

        return response([
            'message' => 'Logging in ...',
            'cookie' => json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'image' => $user->image,
                'role' => $user->role,
                'access_token' => $client->getAccessToken()['access_token'],
                'token' => "Bearer " . JWTAuth::attempt(['email' => $user->email, 'password' => '0']),
                'ttl' => JWTAuth::factory()->getTTL() * 60
            ])
        ], 200);
    }

    public function signOut()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response(['message' => 'Successfully signed out'])->withoutCookie('user');
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response(['error' => $e->getMessage()], 401);
        }
    }

    public function refreshToken()
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            return response(['token' => $newToken]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response(['error' => $e->getMessage()], 401);
        }
    }
}
