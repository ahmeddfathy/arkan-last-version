<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SendSalaryEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $filePath;
    protected $fileName;
    public $tries = 3;
    public $timeout = 120;

    public function __construct($email, $filePath, $fileName)
    {
        $this->email = $email;
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }

    public function handle()
    {
        try {
            if (!Storage::exists($this->filePath)) {
                Log::error('Salary file not found', [
                    'path' => $this->filePath
                ]);
                return;
            }

            Mail::raw('مرفق كشف المرتب الخاص بك', function ($message) {
                $message->to($this->email)
                    ->subject('كشف المرتب')
                    ->attach(Storage::path($this->filePath), [
                        'as' => $this->fileName
                    ]);
            });

            Log::info('Salary email sent successfully', [
                'email' => $this->email,
                'file' => $this->fileName
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send salary email', [
                'error' => $e->getMessage(),
                'email' => $this->email,
                'file' => $this->fileName
            ]);

            throw $e;
        }
    }
}
