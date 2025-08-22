<?php

namespace App\Http\Controllers;

use App\Services\GitHubService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GitHubController extends Controller
{
    protected $gitHubService;

    public function __construct(GitHubService $gitHubService)
    {
        $this->gitHubService = $gitHubService;
    }



    public function createRepository(Request $request)
    {
        // $validator = Validator::make($request->query(), [
        //     'name' => 'required|string|max:100|regex:/^[a-zA-Z0-9._-]+$/',
        //     'description' => 'nullable|string|max:255',
        //     'private' => 'nullable|boolean'
        // ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'status' => 400,
        //         'message' => 'Validation failed',
        //         'errors' => $validator->errors()
        //     ], 400);
        // }

        try {
            $name = $request->query('name');
            $description = $request->query('description', '');
            $private = $request->query('private', false);

            $result = $this->gitHubService->createRepository($name, $description, $private);

            if ($result['success']) {
                Log::info('GitHub repository created successfully', [
                    'repository' => $name,
                    'user' => auth()->id()
                ]);

                return response()->json([
                    'status' => 200,
                    'message' => 'Repository created successfully',
                    'data' => [
                        'clone_url' => $result['clone_url'],
                        'html_url' => $result['html_url'],
                        'repository' => $result['data']
                    ]
                ]);
            }

            return response()->json([
                'status' => 400,
                'message' => $result['error']
            ], 400);
        } catch (\Exception $e) {
            Log::error('Failed to create GitHub repository', [
                'error' => $e->getMessage(),
                'repository' => $request->query('name'),
                'user' => auth()->id()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Failed to create repository'
            ], 500);
        }
    }


    public function addCollaborator(Request $request)
    {
        // // FIX: Validate query parameters instead of request body
        // $validator = Validator::make($request->query(), [
        //     'repository' => 'required|string',
        //     'username' => 'required|string',
        //     'permission' => 'nullable|string|in:pull,push,admin'
        // ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'status' => 400,
        //         'message' => 'Validation failed',
        //         'errors' => $validator->errors()
        //     ], 400);
        // }

        try {
            // FIX: Get values from query parameters
            $repository = $request->query('repository');
            $username = trim($request->query('username'));
            $permission = $request->query('permission', 'push');

            // First check if user exists
            $userCheck = $this->gitHubService->checkUserExists($username);

            if (!$userCheck['success']) {
                return response()->json([
                    'status' => 400,
                    'message' => $userCheck['error']
                ], 400);
            }

            if (!$userCheck['exists']) {
                return response()->json([
                    'status' => 404,
                    'message' => 'GitHub user not found'
                ], 404);
            }

            $result = $this->gitHubService->addCollaborator(
                $repository,
                $username,
                $permission
            );

            if ($result['success']) {
                Log::info('Collaborator added successfully', [
                    'repository' => $repository,
                    'collaborator' => $username,
                    'permission' => $permission,
                    'user' => auth()->id()
                ]);

                return response()->json([
                    'status' => 200,
                    'message' => $result['message']
                ]);
            }

            return response()->json([
                'status' => 400,
                'message' => $result['error']
            ], 400);
        } catch (\Exception $e) {
            Log::error('Failed to add collaborator', [
                'error' => $e->getMessage(),
                'repository' => $request->query('repository'),
                'collaborator' => $request->query('username'),
                'user' => auth()->id()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Failed to add collaborator'
            ], 500);
        }
    }


    public function removeCollaborator(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'repository' => 'required|string',
        //     'username' => 'required|string'
        // ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'status' => 400,
        //         'message' => 'Validation failed',
        //         'errors' => $validator->errors()
        //     ], 400);
        // }

        try {

            $repository = $request->query('repository');
            $username = trim($request->query('username'));
            $result = $this->gitHubService->removeCollaborator(
                $repository,
                $username
            );

            if ($result['success']) {
                Log::info('Collaborator removed successfully', [
                    'repository' => $request->repository,
                    'collaborator' => $request->username,
                    'user' => auth()->id()
                ]);

                return response()->json([
                    'status' => 200,
                    'message' => $result['message']
                ]);
            }

            return response()->json([
                'status' => 400,
                'message' => $result['error']
            ], 400);
        } catch (\Exception $e) {
            Log::error('Failed to remove collaborator', [
                'error' => $e->getMessage(),
                'repository' => $request->repository,
                'collaborator' => $request->username,
                'user' => auth()->id()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Failed to remove collaborator'
            ], 500);
        }
    }
}
