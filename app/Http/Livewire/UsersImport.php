<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UsersImport;

class UsersImport extends Component
{
  use WithFileUploads;

  public $file;
  public $skippedRows = [];
  public $success = '';
  public $importErrors = [];

  public function import()
  {
    $this->validate([
      'file' => 'required|file|mimes:xlsx,xls,csv'
    ]);

    $import = new UsersImport();

    try {
      Excel::import($import, $this->file);
      $this->skippedRows = $import->getSkippedRows();
      $this->success = 'تم استيراد البيانات بنجاح';
    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
      $this->importErrors = $e->failures();
    }

    $this->file = null; // reset file input
  }

  public function render()
  {
    return view('livewire.users-import');
  }
}
