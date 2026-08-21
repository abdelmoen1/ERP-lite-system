<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureOwner($request);

        return User::query()
            ->where('store_id', $request->user()->store_id)
            ->select(['id', 'store_id', 'name', 'email', 'role', 'created_at'])
            ->latest()
            ->paginate(15);
    }

    public function show(Request $request, int $user)
    {
        $this->ensureOwner($request);

        return User::query()
            ->where('store_id', $request->user()->store_id)
            ->select(['id', 'store_id', 'name', 'email', 'role', 'created_at'])
            ->findOrFail($user);
    }

    public function store(StoreUserRequest $request)
    {
        $user = new User($request->safe()->only(['name', 'email', 'password', 'role']));
        $user->store_id = $request->user()->store_id;
        $user->save();

        return response()->json(['data' => $user->only([
            'id',
            'name',
            'email',
            'role',
            'store_id',
            'created_at',
        ])], 201);
    }

    public function update(UpdateUserRequest $request, int $user)
    {
        $target = $this->scopedUser($request, $user);

        if ($target->hasRole(UserRole::OWNER) && $request->has('role')) {
            return response()->json([
                'message' => 'لا يمكن تغيير دور مالك المتجر من خلال هذا المسار.',
            ], 422);
        }

        $target->update($request->safe()->only(['name', 'email', 'password', 'role']));

        return response()->json(['data' => $target->only([
            'id',
            'name',
            'email',
            'role',
            'store_id',
            'created_at',
        ])]);
    }

    public function destroy(Request $request, int $user)
    {
        $this->ensureOwner($request);
        $target = $this->scopedUser($request, $user);

        if ($target->is($request->user())) {
            return response()->json([
                'message' => 'لا يمكن للمالك حذف حسابه من خلال هذا المسار.',
            ], 422);
        }

        if ($target->hasRole(UserRole::OWNER)) {
            return response()->json([
                'message' => 'لا يمكن حذف مالك المتجر.',
            ], 422);
        }

        $target->delete();

        return response()->json(['message' => 'تم حذف المستخدم بنجاح']);
    }

    private function scopedUser(Request $request, int $user): User
    {
        return User::query()
            ->where('store_id', $request->user()->store_id)
            ->findOrFail($user);
    }

    private function ensureOwner(Request $request): void
    {
        abort_unless($request->user()?->hasRole([UserRole::OWNER, UserRole::MANAGER]), 403);
    }
}
