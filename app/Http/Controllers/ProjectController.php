<?php

namespace App\Http\Controllers;


use App\Models\Project;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProjectController extends Controller
{
    private $error404 = [['message' => 'Project does not exist'], 404];
    private $error403 = [['message' => 'Permission denied'], 403];

    public function __construct()
    {
        $this->user = JWTAuth::user(JWTAuth::getToken());
        $this->admin = $this->user ? $this->user->role == 'admin' : false;
    }

    public function index()
    {
        return Project::where(['public' => true])->get(['name', 'thumbnail']);
    }

    public function store(Request $request)
    {
        return Project::create($request->all());
    }

    public function show(int $id)
    {
        if (!$project = Project::find($id))
            return response(...$this->error_404);
        return $project;
    }

    public function update(Request $request, int $id)
    {
        if (!$project = Project::find($id))
            return response(...$this->error_404);
        if (!$this->admin && $project->user->id != $this->user->id)
            return response(...$this->error_403);

        $data = $request->all();

        if ($request->file('thumbnail')) {
            $pimage = $project->thumbnail ? substr($project->thumbnail, 56) : null;
            if ($pimage && \Illuminate\Support\Facades\Storage::disk('s3')->exists('fuzzle/thumbnails/' . $pimage))
                \Illuminate\Support\Facades\Storage::disk('s3')->delete('fuzzle/thumbnails/' . $pimage);

            $data['thumbnail'] = "https://d3djy7pad2souj.cloudfront.net/fuzzle/thumbnails/" .
                explode('/', $request->file('thumbnail')->storeAs('fuzzle/thumbnails', $id .
                    $request->file('thumbnail')->getClientOriginalName(), 's3'))[2];
        }
        return $project->update($data);
    }

    public function destroy(int $id)
    {
        if (!$project = Project::find($id))
            return response(...$this->error404);

        if (!$this->admin && $project->user->id != $this->user->id)
            return response(...$this->error_403);

        return $project->delete($id);
    }
}
