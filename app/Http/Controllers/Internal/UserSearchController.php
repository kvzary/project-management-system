<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q    = trim($request->query('q', ''));
        $auth = $request->user();

        // Projects the authenticated user belongs to (as member or owner)
        $projectIds = $auth->projects()->pluck('projects.id')
            ->merge($auth->ownedProjects()->pluck('id'))
            ->unique();

        $users = User::where(function ($query) use ($projectIds) {
            $query->whereHas('projects', fn ($q) => $q->whereIn('projects.id', $projectIds))
                ->orWhereHas('ownedProjects', fn ($q) => $q->whereIn('id', $projectIds));
        })
            ->when($q !== '', fn ($query) => $query->where('name', 'like', '%' . $q . '%'))
            ->where('id', '!=', $auth->id)
            ->select('id', 'name')   // never expose email or sensitive fields
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json($users);
    }
}
