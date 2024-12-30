<?php

namespace App\Http\Controllers;

use App\Models\AbsenceRequest;
use App\Services\AbsenceRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;

class AbsenceRequestController extends Controller
{
    protected $absenceRequestService;

    public function __construct(AbsenceRequestService $absenceRequestService)
    {
        $this->absenceRequestService = $absenceRequestService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'manager') {
            $employeeName = $request->input('employee_name');
            $status = $request->input('status');

            $requests = $this->absenceRequestService->getFilteredRequests($employeeName, $status);
            $users = User::select('id', 'name')->get();
            $absenceDays = null;

            // If searching for specific employee, calculate their absence days
            if ($employeeName) {
                $searchedUser = User::where('name', 'like', "%{$employeeName}%")->first();
                if ($searchedUser) {
                    $absenceDays = $this->absenceRequestService->calculateAbsenceDays($searchedUser->id);
                }
            }

            return view('absence-requests.index', compact('users', 'requests', 'absenceDays', 'employeeName', 'status'));
        } else {
            $requests = $this->absenceRequestService->getUserRequests();
            $absenceDays = $this->absenceRequestService->calculateAbsenceDays($user->id);
            return view('absence-requests.index', compact('requests', 'absenceDays'));
        }
    }




    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'employee' && $user->role !== 'manager') {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        // تحديد المستخدم المستهدف بناءً على دور المدير أو الموظف
        $targetUserId = $user->role === 'manager' && $request->input('user_id')
            ? $request->input('user_id')
            : $user->id;

        // حساب عدد الأيام الحالية (pending أو approved) للسنة الحالية
        $pendingOrApprovedDays = AbsenceRequest::where('user_id', $targetUserId)
            ->whereIn('status', ['pending', 'approved'])
            ->whereYear('absence_date', Carbon::now()->year) // فقط للسنة الحالية
            ->count();

        if ($pendingOrApprovedDays >= 5) {
            return redirect()->back()->with('error', 'You cannot request more than 5 absence days in a year while your pending or approved requests exist.');
        }

        // تحقق من صحة البيانات المدخلة
        $validated = $request->validate([
            'absence_date' => 'required|date|after:today',
            'reason' => 'required|string|max:255',
            'user_id' => 'required_if:role,manager|exists:users,id|nullable',
        ]);

        // التحقق من أن الطلب الجديد لا يتجاوز الحد
        if ($pendingOrApprovedDays + 1 > 5) {
            return redirect()->back()->with('error', 'This request exceeds the allowed limit of 5 days per year.');
        }

        // إنشاء الطلب بناءً على دور المستخدم
        if ($user->role === 'manager') {
            if ($request->input('user_id') && $request->input('user_id') !== $user->id) {
                $this->absenceRequestService->createRequestForUser($validated['user_id'], $validated);
            } else {
                $this->absenceRequestService->createRequest($validated);
            }
        } else {
            $this->absenceRequestService->createRequest($validated);
        }

        return redirect()->route('absence-requests.index')
            ->with('success', 'Absence request submitted successfully.');
    }


    public function update(Request $request, AbsenceRequest $absenceRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager' && $user->id !== $absenceRequest->user_id) {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'absence_date' => 'required|date|after:today',
            'reason' => 'required|string|max:255'
        ]);

        $this->absenceRequestService->updateRequest($absenceRequest, $validated);

        return redirect()->route('absence-requests.index')
            ->with('success', 'Absence request updated successfully.');
    }

    public function destroy(AbsenceRequest $absenceRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager' && $user->id !== $absenceRequest->user_id) {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $this->absenceRequestService->deleteRequest($absenceRequest);

        return redirect()->route('absence-requests.index')
            ->with('success', 'Absence request deleted successfully.');
    }

    public function updateStatus(Request $request, AbsenceRequest $absenceRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager') {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:255'
        ]);

        $this->absenceRequestService->updateStatus($absenceRequest, $validated);

        return redirect()->route('absence-requests.index')
            ->with('success', 'Request status updated successfully.');
    }

    public function modifyResponse(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role !== 'manager') {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $absenceRequest = AbsenceRequest::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:255'
        ]);

        $this->absenceRequestService->modifyResponse($absenceRequest, $validated);

        return redirect()->route('absence-requests.index')
            ->with('success', 'Response updated successfully');
    }

    public function resetStatus(AbsenceRequest $absenceRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager') {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $this->absenceRequestService->resetStatus($absenceRequest);

        return redirect()->route('absence-requests.index')
            ->with('success', 'Request status reset to pending successfully.');
    }
}
