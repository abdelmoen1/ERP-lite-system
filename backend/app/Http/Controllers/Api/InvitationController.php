<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateInvitationRequest;
use App\Services\AuthService;

class InvitationController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function store(CreateInvitationRequest $request)
    {
        [$invitation, $token] = $this->authService->createInvitation(
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'message' => 'تم إنشاء الدعوة بنجاح',
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'expires_at' => $invitation->expires_at,
                'token' => $token,
            ],
        ], 201);
    }
}
