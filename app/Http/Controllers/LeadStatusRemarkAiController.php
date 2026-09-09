<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\OperationLead;
use App\Services\LeadStatusRemarkAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadStatusRemarkAiController extends Controller
{
    public function __construct(
        protected LeadStatusRemarkAiService $aiService
    ) {}

    public function generateForSales(Request $request, $id): JsonResponse
    {
        return $this->generate($request, $id, 'Sales', 'lead');
    }

    public function generateForManager(Request $request, $id): JsonResponse
    {
        return $this->generate($request, $id, 'Sales Manager', 'lead');
    }

    public function generateForOperation(Request $request, $id): JsonResponse
    {
        return $this->generate($request, $id, 'Operation', 'operation_lead');
    }

    public function generateForOperationManager(Request $request, $id): JsonResponse
    {
        return $this->generate($request, $id, 'Operation Manager', 'operation_lead');
    }

    protected function generate(Request $request, $id, string $role, string $scope = 'lead'): JsonResponse
    {
        $user = auth()->user();
        if (! $user || ! $user->hasRole($role)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($scope === 'operation_lead') {
            OperationLead::findOrFail($id);
        } else {
            Lead::findOrFail($id);
        }

        $draft = trim((string) $request->input('draft', ''));
        $status = $request->input('status');

        $result = $this->aiService->polish($draft, is_string($status) ? $status : null, (int) $id, $scope);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
