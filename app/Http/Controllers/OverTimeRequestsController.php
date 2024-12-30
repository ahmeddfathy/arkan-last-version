<?php

namespace App\Http\Controllers;

use App\Models\OverTimeRequests;
use App\Services\OverTimeRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class OverTimeRequestsController extends Controller
{
    protected $overTimeRequestService;

    public function __construct(OverTimeRequestService $overTimeRequestService)
    {
        $this->overTimeRequestService = $overTimeRequestService;
    }


    public function index(Request $request)
    {
        $user = Auth::user();
        $employeeName = $request->query('employee_name');
        $status = $request->query('status');

        if ($user->role === 'manager') {
            $requests = $this->overTimeRequestService->getFilteredRequests($employeeName, $status);
            $users = User::select('id', 'name')->get()->map(function ($user) {
                $user->overtime_hours = $this->overTimeRequestService->calculateOvertimeHours($user->id);
                return $user;
            });
        } else {
            $requests = $this->overTimeRequestService->getUserRequests();
        }

        return view('overtime-requests.index', [
            'requests' => $requests,
            'users' => $users ?? [],
            'statuses' => ['pending', 'approved', 'rejected'],
            'selectedStatus' => $status,
            'employeeName' => $employeeName,
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'overtime_date' => 'required|date|after:today',
            'reason' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'user_id' => 'sometimes|exists:users,id',
        ]);

        try {
            $targetUserId = $request->input('user_id', Auth::id());

            if (Auth::user()->role !== 'manager' && $targetUserId !== Auth::id()) {
                return $this->unauthorized();
            }

            $this->overTimeRequestService->createRequest($request->all());

            return $this->successResponse('overtime-requests.index', 'Overtime request submitted successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'overtime_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'reason' => 'required|string|max:255',
        ]);

        try {
            $overtimeRequest = OverTimeRequests::findOrFail($id);

            // Call the service method to validate and update the overtime request
            $this->overTimeRequestService->update($overtimeRequest, $validated);

            return redirect()->route('overtime-requests.index')->with('success', 'Overtime request updated successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }


    public function destroy($id)
    {
        try {
            $overTimeRequest = OverTimeRequests::findOrFail($id);

            $this->overTimeRequestService->deleteRequest($overTimeRequest);

            return redirect()->route('overtime-requests.index')
                ->with('success', 'Overtime request deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Overtime request not found.');
        }
    }


    public function updateStatus(Request $request, OverTimeRequests $overTimeRequest)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:255'
        ]);

        try {
            if (Auth::user()->role !== 'manager') {
                return $this->unauthorized();
            }

            $this->overTimeRequestService->updateStatus($overTimeRequest, $request->all());

            return $this->successResponse('overtime-requests.index', 'Request status updated successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }


    public function modifyResponse(Request $request, OverTimeRequests $overTimeRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:255'
        ]);

        try {
            if (Auth::user()->role !== 'manager') {
                return $this->unauthorized();
            }

            $this->overTimeRequestService->modifyResponse($overTimeRequest, $validated);

            return $this->successResponse('overtime-requests.index', 'Response updated successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function resetStatus(OverTimeRequests $overTimeRequest)
    {
        try {
            if (Auth::user()->role !== 'manager') {
                return $this->unauthorized();
            }

            $this->overTimeRequestService->resetStatus($overTimeRequest);

            return $this->successResponse('overtime-requests.index', 'Request status reset to pending successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    private function unauthorized()
    {
        return redirect()->route('welcome')->with('error', 'Unauthorized action.');
    }

    private function successResponse($route, $message)
    {
        return redirect()->route($route)->with('success', $message);
    }

    private function errorResponse($message)
    {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Failed to process request. ' . $message);
    }
}
