<?php

namespace App\Http\Controllers\Brand;

use App\Http\Controllers\Controller;
use App\Services\ApplicationService;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function __construct(
        private ApplicationService $applicationService
    ) {}

    // GET /api/brand/applications
    public function index(Request $request)
    {
        $status       = $request->query('status'); // ?status=pending
        $applications = $this->applicationService->getBrandApplications(
            $request->user()->id,
            $status
        );

        return response()->json($applications);
    }

    // PATCH /api/brand/applications/{id}/approve
    public function approve(int $id, Request $request)
    {
        $application = $this->applicationService->approveApplication(
            $id,
            $request->user()->id
        );

        return response()->json($application);
    }

    // PATCH /api/brand/applications/{id}/reject
    public function reject(int $id, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $application = $this->applicationService->rejectApplication(
            $id,
            $request->user()->id,
            $request->reason
        );

        return response()->json($application);
    }
}