<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Project;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    private $error404 = [['message' => 'User does not exist'], 404];
    private $error403 = [['message' => 'Permission denied'], 403];

    public function __construct()
    {
        $this->user = JWTAuth::user(JWTAuth::getToken());
        $this->admin = $this->user ? $this->user->role == 'admin' : false;
    }

    public function index()
    {
        return User::all();
    }

    public function store(\App\Http\Requests\SignUpRequest $request)
    {
        return User::create($request->all());
    }

    public function show(int|string $id)
    {
        $user = is_numeric($id) ? User::find($id) : User::where('name', $id)->first();
        if (!$user)
            return response(...$this->error404);

        return $user;
    }

    public function update(\App\Http\Requests\UpdateUserRequest $request, int $id)
    {
        if (!$user = User::find($id))
            return response(...$this->error404);

        if (!$this->admin && $this->user->id != $user->id)
            return response(...$this->error_403);

        return $user->update($request->all());
    }

    public function destroy(int $id)
    {
        if (!$user = User::find($id))
            return response(...$this->error404);

        if (!$this->admin && $this->user->id != $user->id)
            return response(...$this->error403);

        return $user->delete($id);
    }

    public function me()
    {
        if (!$user = User::find($this->user->id))
            return response(...$this->error404);

        return $user;
    }

    public function updateMe(\App\Http\Requests\UpdateUserRequest $request)
    {
        if (!$user = User::find($this->user->id))
            return response(...$this->error404);
        $user->update($request->all());

        return response([
            "message" => "Successfully updated.",
            'cookie' => json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'image' => $user->image,
                'role' => $user->role,
                'token' => $request->header('Authorization'),
                'ttl' => JWTAuth::factory()->getTTL() * 60
            ])
        ])->withCookie(cookie('user', json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'image' => $user->image,
            'role' => $user->role,
            'token' => $request->header('Authorization')
        ]), JWTAuth::factory()->getTTL()));
    }

    public function uploadAvatar(\App\Http\Requests\UploadImageRequest $request)
    {
        if ($request->file('image')) {
            $user = User::find($this->user->id);
            $uimage = substr($user->image, 53);

            if (\Illuminate\Support\Facades\Storage::disk('s3')->exists('fuzzle/' . $uimage) && !str_contains($uimage, 'fuzzle_H265P'))
                \Illuminate\Support\Facades\Storage::disk('s3')->delete('fuzzle/' . $uimage);

            $user->update([
                'image' => $image = "https://d3djy7pad2souj.cloudfront.net/fuzzle/avatars/" .
                    explode('/', $request->file('image')->storeAs('fuzzle/avatars', $user->id .
                        $request->file('image')->getClientOriginalName(), 's3'))[2]
            ]);

            return response([
                "message" => "Your avatar was uploaded.",
                'cookie' => json_encode([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'image' => $image,
                    'role' => $user->role,
                    'token' => $request->header('Authorization'),
                    'ttl' => JWTAuth::factory()->getTTL() * 60
                ])
            ], 201)->withCookie(cookie('user', json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'image' => $image,
                'role' => $user->role,
                'token' => $request->header('Authorization')
            ]), JWTAuth::factory()->getTTL()));
        }
    }

    public function getProjects()
    {
        return Project::where(['user_id' => $this->user->id])->get(['name', 'thumbnail']);
    }
}
