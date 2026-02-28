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

        $users = User::where('id', '!=', $auth->id)
            ->when($q !== '', fn ($query) => $query->where('name', 'like', '%' . $q . '%'))
            ->select('id', 'name')
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json($users);
    }
}
