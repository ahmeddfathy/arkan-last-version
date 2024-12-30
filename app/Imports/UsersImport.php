<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use App\Models\Team;

class UsersImport implements ToModel, WithEvents
{
    protected $duplicates = [];
    protected $skippedRows = [];

    public function model(array $row)
    {
        if (empty($row[1]) || empty($row[6]) || empty($row[10])) {
            $this->skippedRows[] = [
                'employee_name' => $row[1] ?? 'غير متوفر',
                'reason' => 'بيانات إلزامية مفقودة (الاسم أو رقم الهاتف أو الرقم الوطني)'
            ];
            return null;
        }

        // التحقق من وجود الموظف مسبقاً
        $existingUser = User::where('employee_id', $row[2])
            ->orWhere('national_id_number', $row[11])
            ->orWhere('email', $row[7])
            ->first();

        if ($existingUser) {
            $this->duplicates[] = [
                'employee_name' => $row[1],
                'employee_id' => $row[2],
                'reason' => 'الموظف موجود مسبقاً'
            ];
            return null;
        }

        // Validate and format the date_of_birth
        $dateOfBirth = null;
        if (!empty($row[8]) && is_numeric($row[8])) {
            try {
                $dateOfBirth = Carbon::createFromFormat('Y-m-d', Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[8]))->format('Y-m-d'));
            } catch (\Exception $e) {
                $dateOfBirth = null;
            }
        }

        // Validate and convert Excel serial number to date for start_date_of_employment
        $startDateOfEmployment = null;
        if (!empty($row[16]) && is_numeric($row[16])) {
            $startDateOfEmployment = Carbon::createFromFormat('Y-m-d', Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[16]))->format('Y-m-d'));
        }

        $age = 0;
        if ($dateOfBirth) {
            $now = Carbon::now();
            $age = abs($now->diffInYears($dateOfBirth));
            if ($now->month < $dateOfBirth->month || ($now->month == $dateOfBirth->month && $now->day < $dateOfBirth->day)) {
                $age--;
            }
        }

        $user = new User([
            'employee_id' => $row[2],
            'name' => $row[1] ?? null,
            'gender' => $row[4] ?? null,
            'password' => bcrypt($row[11] ?? 'default_password'),
            'address' => $row[5] ?? null,
            'phone_number' => $row[6] ?? null,
            'email' => $row[7] ?? null,
            'date_of_birth' => $dateOfBirth ?? '1900-01-01',
            'age' => intval($age) ?? null,
            'national_id_number' => $row[11] ?? null,
            'education_level' => $row[12] ?? null,
            'marital_status' => $row[13] ?? null,
            'number_of_children' => isset($row[14]) && is_numeric($row[14]) ? (int)$row[14] : 0,
            'department' => $row[15] ?? null,
            'start_date_of_employment' => $startDateOfEmployment ?? '1900-01-01',
            'employee_status' => $row[20] ?? 'active',
            'last_contract_start_date' => null,
            'last_contract_end_date' => null,
            'job_progression' => null,
        ]);

        if ($user->save()) {
            $this->createTeam($user);
        }

        return $user;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                $message = '';

                if (!empty($this->duplicates)) {
                    $message .= "تم تخطي الموظفين المكررين التالية:\n";
                    foreach ($this->duplicates as $duplicate) {
                        $message .= "- {$duplicate['employee_name']} (#{$duplicate['employee_id']}): {$duplicate['reason']}\n";
                    }
                }

                if (!empty($this->skippedRows)) {
                    $message .= "\nتم تخطي السجلات التالية:\n";
                    foreach ($this->skippedRows as $skipped) {
                        $message .= "- {$skipped['employee_name']}: {$skipped['reason']}\n";
                    }
                }

                if (!empty($message)) {
                    Session::flash('import_summary', $message);
                    Session::flash('skipped_count', count($this->duplicates) + count($this->skippedRows));
                }
            }
        ];
    }

    protected function createTeam(User $user): void
    {
        $team = $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0] . "'s Team",
            'personal_team' => true,
        ]));

        // تحديث current_team_id للمستخدم
        $user->forceFill([
            'current_team_id' => $team->id,
        ])->save();
    }
}
