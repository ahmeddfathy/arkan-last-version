<?php

namespace App\Services;

use App\Models\SalarySheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Services\SalaryEmailService;

class SalarySheetService
{
  protected $notificationService;
  protected $emailService;

  public function __construct(
    SalaryNotificationService $notificationService,
    SalaryEmailService $emailService
  ) {
    $this->notificationService = $notificationService;
    $this->emailService = $emailService;
  }

  public function getAllSalarySheets()
  {
    return SalarySheet::with('user')
      ->orderBy('created_at', 'desc')
      ->get();
  }

  public function handleFileUpload($files): array
  {
    $results = [];
    $failedUploads = [];

    foreach ($files as $file) {
      try {
        $result = $this->processSingleFile($file);
        $results[] = $result;
      } catch (\Exception $e) {
        $failedUploads[] = [
          'filename' => $file->getClientOriginalName(),
          'error' => $e->getMessage()
        ];
      }
    }

    return [
      'success' => count($results) > 0,
      'processed' => $results,
      'failed' => $failedUploads
    ];
  }

  private function processSingleFile(UploadedFile $file): array
  {
    $employee_id = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    $month = Carbon::now()->format('Y-m');
    $extension = $file->getClientOriginalExtension();

    $path = "salary_sheets/{$employee_id}/{$month}";
    $fileName = "{$employee_id}_{$month}.{$extension}";

    $filePath = Storage::putFileAs($path, $file, $fileName);

    $salarySheet = SalarySheet::create([
      'employee_id' => $employee_id,
      'month' => $month,
      'file_path' => $filePath,
      'original_filename' => $file->getClientOriginalName()
    ]);

    $employee = User::where('employee_id', $employee_id)->first();

    if (!$employee) {
      throw new \Exception("No employee found with ID: {$employee_id}");
    }

    $this->notificationService->createSalarySheetNotification($employee, [
      'id' => $salarySheet->id,
      'month' => $month,
      'filename' => $fileName
    ]);

    $this->emailService->sendSalarySheet($employee->email, $filePath, $fileName);

    return [
      'id' => $salarySheet->id,
      'employee_id' => $employee_id,
      'email' => $employee->email,
      'month' => $month,
      'filename' => $fileName
    ];
  }
}
