<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\User;
use App\Http\Controllers\MacAddressController; // Include the MacAddressController
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function index()
    {
        $leaves = Leave::with('user')->get();
        return view('leaves.index', compact('leaves'));
    }

    public function create()
    {
        $users = User::all();
        return view('leaves.create', compact('users'));
    }

    public function store(Request $request)
    {
        // Get the authenticated user
        $user = auth()->user();


        if ($user->role == 'manager') {

            $leave = new Leave();
            $leave->user_id = $request->user_id;
            $leave->check_out_time = now();

            $leave->save();

            return redirect()->route('leaves.index')->with('success', 'CheckOut created successfully!');
        }


        $macController = new MacAddressController();
        $macData = $macController->getMacAddresses()->getData();

        if (!isset($macData->is_connected_to_router) || !$macData->is_connected_to_router) {
            return view('errors.custom', [
                'errorTitle' => 'Netwok Connection',
                'errorMessage' => 'You Must Connected To Company Network To Register'
            ]);
        }


        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);


        $today = Carbon::today();
        $existingLeave = Leave::where('user_id', $request->user_id)
                              ->whereDate('check_out_time', $today)
                              ->first();

        if ($existingLeave) {

            return view('errors.custom', [
                'errorTitle' => 'Duplicate Entry',
                'errorMessage' => 'You have already registered your Check Out for today.'
            ]);
        }


        $leave = new Leave();
        $leave->user_id = $request->user_id;
        $leave->check_out_time = now();

        $leave->save();

        return redirect()->route('dashboard')->with('success', 'Leave marked created successfully!');
    }


    public function show($id)
    {

        $leave = Leave::with('user')->findOrFail($id);
        return view('leaves.show', compact('leave'));
    }

    public function destroy($id)
    {

        $leave = Leave::findOrFail($id);


        $leave->delete();

        return redirect()->route('leaves.index')->with('success', 'Leave record deleted successfully!');
    }
}
