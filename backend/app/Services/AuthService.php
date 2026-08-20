<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\StoreInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function registerStore(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $store = Store::create([
                'name' => $data['store_name'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
            ]);

            $owner = new User([
                'name' => $data['owner_name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            $owner->store_id = $store->id;
            $owner->role = UserRole::OWNER;
            $owner->save();

            return $owner->load('store');
        });
    }

    public function createInvitation(User $inviter, array $data): array
    {
        $token = Str::random(64);
        $invitation = StoreInvitation::create([
            'store_id' => $inviter->store_id,
            'invited_by' => $inviter->id,
            'email' => $data['email'] ?? null,
            'role' => $data['role'],
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHours($data['expires_in_hours'] ?? 72),
        ]);

        return [$invitation, $token];
    }

    public function acceptInvitation(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $invitation = StoreInvitation::where('token_hash', hash('sha256', $data['token']))
                ->lockForUpdate()
                ->first();

            if (!$invitation || $invitation->accepted_at || $invitation->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'token' => 'الدعوة غير صالحة أو منتهية.',
                ]);
            }

            if ($invitation->email && !hash_equals(strtolower($invitation->email), strtolower($data['email']))) {
                throw ValidationException::withMessages([
                    'email' => 'البريد الإلكتروني لا يطابق الدعوة.',
                ]);
            }

            $user = new User([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            $user->store_id = $invitation->store_id;
            $user->role = $invitation->role;
            $user->save();

            $invitation->forceFill(['accepted_at' => now()])->save();

            return $user->load('store');
        });
    }
}
