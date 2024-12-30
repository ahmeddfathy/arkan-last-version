<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PermissionRequest;
use App\Services\PermissionRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionRequestController extends Controller
{
    protected $permissionRequestService;

    public function __construct(PermissionRequestService $permissionRequestService)
    {
        $this->permissionRequestService = $permissionRequestService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $filters = [
            'employee_name' => $request->get('employee_name'),
            'status' => $request->get('status', 'all')
        ];

        if ($user->role === 'manager') {
            $requests = $this->permissionRequestService->getAllRequests($filters);
            $users = User::select('id', 'name')->get();

            $remainingMinutes = [];
            foreach ($users as $userData) {
                $remainingMinutes[$userData->id] = $this->permissionRequestService->getRemainingMinutes($userData->id);
            }

            return view('permission-requests.index', compact('requests', 'users', 'remainingMinutes', 'filters'));
        } elseif ($user->role === 'employee') {
            $requests = $this->permissionRequestService->getAllRequests();
            $remainingMinutes = $this->permissionRequestService->getRemainingMinutes($user->id);

            return view('permission-requests.index', compact('requests', 'remainingMinutes'));
        }

        return redirect()->route('welcome');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'employee' && $user->role !== 'manager') {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'departure_time' => 'required|date|after:now',
            'return_time' => 'required|date|after:departure_time',
            'reason' => 'required|string|max:255',
            'user_id' => 'required_if:role,manager|exists:users,id|nullable'
        ]);

        if ($user->role === 'manager' && $request->input('user_id') && $request->input('user_id') !== $user->id) {
            $result = $this->permissionRequestService->createRequestForUser($validated['user_id'], $validated);
        } else {
            $result = $this->permissionRequestService->createRequest($validated);
        }

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->route('permission-requests.index')
            ->with('success', 'Permission request submitted successfully.');
    }

    public function resetStatus(PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager') {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $this->permissionRequestService->resetStatus($permissionRequest);

        return redirect()->route('permission-requests.index')
            ->with('success', 'Request status reset to pending successfully.');
    }

    public function modifyResponse(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager') {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:255'
        ]);

        $this->permissionRequestService->modifyResponse($permissionRequest, $validated);

        return redirect()->route('permission-requests.index')
            ->with('success', 'Response modified successfully.');
    }

    public function update(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager' && $user->id !== $permissionRequest->user_id) {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'departure_time' => 'required|date|after:now',
            'return_time' => 'required|date|after:departure_time',

            'reason' => 'required|string|max:255',
            'returned_on_time' => 'nullable|boolean',
            'minutes_used' => 'nullable|integer'
        ]);

        $result = $this->permissionRequestService->updateRequest($permissionRequest, $validated);

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->route('permission-requests.index')
            ->with('success', 'Permission request updated successfully.');
    }

    public function destroy(PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager' && $user->id !== $permissionRequest->user_id) {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $this->permissionRequestService->deleteRequest($permissionRequest);

        return redirect()->route('permission-requests.index')
            ->with('success', 'Permission request deleted successfully.');
    }



    public function updateStatus(Request $request, PermissionRequest $permissionRequest)
{
    $user = Auth::user();

    if ($user->role !== 'manager') {
        return redirect()->route('welcome')->with('error', 'Unauthorized action.');
    }

    $validated = $request->validate([
        'status' => 'required|in:approved,rejected',
        'rejection_reason' => 'required_if:status,rejected|nullable|string|max:255',
    ]);

    $this->permissionRequestService->updateStatus($permissionRequest, $validated);

    return redirect()->route('permission-requests.index')
        ->with('success', 'Request status updated successfully.');
}

public function updateReturnStatus(Request $request, PermissionRequest $permissionRequest)
{
    $user = Auth::user();

    if ($user->role !== 'manager') {
        return redirect()->route('welcome')->with('error', 'Unauthorized action.');
    }

    $validated = $request->validate([
        'return_status' => 'required|in:0,1,2',
    ]);

    $this->permissionRequestService->updateReturnStatus($permissionRequest, (int)$validated['return_status']);

    return redirect()->route('permission-requests.index')
        ->with('success', 'Return status updated successfully.');
}



}
