<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Requests\RegisterTechnicianApplicantRequest;
use Illuminate\Http\JsonResponse;

class TechnicianApplicantAuthController extends Controller
{
    public function register(RegisterTechnicianApplicantRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => $request->string('password'),
            'auth_provider' => 'password',
            'role' => 'technician_applicant',
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Technician applicant account created successfully.',
            'user' => $user,
            'token' => $user->createToken('technician-application')->plainTextToken,
        ], 201);
    }
}
