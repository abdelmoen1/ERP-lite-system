<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display employees belonging to the authenticated owner's store.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->where('store_id', $request->user()->store_id)
            ->whereIn('role', [
                UserRole::EMPLOYEE->value,
                UserRole::MANAGER->value,
            ])
            ->select([
                'id',
                'name',
                'email',
                'role',
                'created_at',
            ])
            ->latest()
            ->paginate(20);

        return UserResource::collection($users);
    }

    /**
     * Update only the role of a user.
     */
    public function updateRole(
        UpdateUserRoleRequest $request,
        User $user
    ) {
        $this->authorize('updateRole', $user);

        $user->update([
            'role' => $request->validated('role'),
        ]);

        return new UserResource($user->refresh());
    }

    /**
     * Delete a user.
     */
    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json([
            'message' => 'تم حذف المستخدم بنجاح',
        ]);
    }
}
