<?php

namespace App\Modules\Audit\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:100'],
            'entity_type' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $query = AuditLog::with('user:id,name,email')
            ->latest();

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['entity_type'])) {
            $query->where(
                'entity_type',
                $filters['entity_type']
            );
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return response()->json([
            'data' => $query->paginate(25),
        ]);
    }
}
