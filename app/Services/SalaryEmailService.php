<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SendSalaryEmail;

class SalaryEmailService
{
  public function sendSalarySheet($email, $filePath, $fileName)
  {
    try {
      if (!Storage::exists($filePath)) {
        throw new \Exception("File not found: {$filePath}");
      }

      SendSalaryEmail::dispatch($email, $filePath, $fileName)
        ->delay(now()->addSeconds(2));

      Log::info("Queued salary email", [
        'email' => $email,
        'file' => $fileName
      ]);

      return [
        'success' => true,
        'message' => "Email queued for sending to {$email}"
      ];
    } catch (\Exception $e) {
      Log::error('Failed to queue salary email', [
        'error' => $e->getMessage(),
        'email' => $email,
        'file' => $fileName
      ]);

      throw $e;
    }
  }
}
