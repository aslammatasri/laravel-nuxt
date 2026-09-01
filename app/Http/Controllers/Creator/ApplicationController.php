<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Creator\StoreApplicationRequest;
use App\Services\ApplicationService;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function __construct(
        private ApplicationService $applicationService
    ) {}

    // GET /api/creator/applications
    public function index(Request $request)
    {
        $status       = $request->query('status'); // ?status=pending
        $applications = $this->applicationService->getCreatorApplications(
            $request->user()->id,
            $status
        );

        return response()->json($applications);
    }

    // POST /api/creator/products/{id}/apply
    public function store(StoreApplicationRequest $request, int $productId)
    {
        try {
            $application = $this->applicationService->applyToProduct(
                $request->user()->id,
                $productId,
                $request->validated()['pitch_message']
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }

        return response()->json($application, 201);
    }
}