<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use App\Http\Controllers\MacAddressController;
use Carbon\Carbon;


class AttendanceController extends Controller
{
    /**
     * Display a listing of attendances.
     */
    public function index()
    {
        $attendances = Attendance::with('user')->get(); // Get all attendances with user info
        return view('attendances.index', compact('attendances')); // Return the index view
    }

    /**
     * Show the form for creating a new attendance record.
     */
    public function create()
    {
        $users = User::all(); // Get all users
        return view('attendances.create', compact('users')); // Return the create view
    }

    /**
     * Store a newly created attendance record in storage.
     */
    public function show($id)
    {
        $attendance = Attendance::with('user')->findOrFail($id); // Get the attendance record with user info
        return view('attendances.show', compact('attendance')); // Return the show view
    }
    public function store(Request $request)
    {
        // Get the authenticated user
        $user = auth()->user();

        // If the user's role is 'manager', skip the internet connection check and the 'already registered' check
        if ($user->role == 'manager') {
            // For manager, allow them to register attendance or leave freely without any checks
            $attendance = new Attendance();
            $attendance->user_id = $request->user_id;
            $attendance->check_in_time = Carbon::now(); // Set the check-in time to the current time
            $attendance->save();

            return redirect()->route('attendances.index')->with('success', 'Attendance successfully recorded.');
        }

        // Check network connection for non-managers
        $macController = new MacAddressController();
        $macData = $macController->getMacAddresses()->getData();

        if (!isset($macData->is_connected_to_router) || !$macData->is_connected_to_router) {

            return view('errors.custom', [
                'errorTitle' => 'Network Error',
                'errorMessage' => 'You must be connected to the network to register attendance.'
            ]);
        }

        // Validate the data
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // Check if the user has already registered today (if the user is not a manager)
        $today = Carbon::today(); // Get today's date
        $existingAttendance = Attendance::where('user_id', $request->user_id)
                                        ->whereDate('check_in_time', $today)
                                        ->first();

        if ($existingAttendance) {
            // If the user has already registered today, return a response
            
            return view('errors.custom', [
                'errorTitle' => 'Duplicate Entry',
                'errorMessage' => 'You have already registered your attendance for today.'
            ]);
        }

        // If not, create a new attendance record
        $attendance = new Attendance();
        $attendance->user_id = $request->user_id;
        $attendance->check_in_time = Carbon::now(); // Set the check-in time to the current time
        $attendance->save();
        return redirect()->route('dashboard')->with('success', 'Attendance successfully recorded.');

    }

    public function destroy($id)
    {
        $attendance = Attendance::findOrFail($id); // Find the attendance record by ID
        $attendance->delete(); // Delete the record
        return redirect()->route('attendances.index')->with('success', 'Attendance record deleted successfully.');
    }

}
