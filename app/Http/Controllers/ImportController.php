<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UsersImport;
use App\Models\User;
class ImportController extends Controller
{
     public function index(){
        $users = User::all();
        return view('excel');
       }
    public function import(Request $request)
    {
        // التحقق من صحة الملف المرفوع
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,ods',  // التأكد أن الملف هو Excel أو CSV
        ]);

        $file = $request->file('file');


        Excel::import(new UsersImport, $file);

        return back()->with('success', 'تم استيراد البيانات بنجاح!');
    }
}
