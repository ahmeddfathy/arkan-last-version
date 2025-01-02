<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

use Spatie\Permission\Traits\HasRoles;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class User extends Authenticatable
{
  use HasApiTokens;

  /** @use HasFactory<\Database\Factories\UserFactory> */
  use HasFactory;
  use HasProfilePhoto;
  use HasTeams;
  use HasRoles;
  use Notifiable;
  use TwoFactorAuthenticatable;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'name',
    "employee_id",
    'email',
    'password',
    'employee_id',
    'age',
    'date_of_birth',
    'national_id_number',
    'phone_number',
    'start_date_of_employment',
    'last_contract_start_date',
    'last_contract_end_date',
    'job_progression',
    'department',
    'gender',
    'address',
    'education_level',
    'marital_status',
    'number_of_children',
    'employee_status',

  ];
  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'password',
    'remember_token',
    'two_factor_recovery_codes',
    'two_factor_secret',
  ];

  /**
   * The accessors to append to the model's array form.
   *
   * @var array<int, string>
   */
  protected $appends = [
    'profile_photo_url',
  ];

  /**
   * Get the attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
    ];
  }

  public function attendanceRecords()
  {
    return $this->hasMany(AttendanceRecord::class, 'employee_id', 'employee_id');
  }

  public function sentMessages()
  {
    return $this->hasMany(Message::class, 'sender_id');
  }

  public function receivedMessages()
  {
    return $this->hasMany(Message::class, 'receiver_id');
  }

  public function ownedTeams()
  {
    return $this->hasMany(Team::class, 'user_id');
  }

  public function teams()
  {
    return $this->belongsToMany(Team::class, 'team_user')
      ->withPivot('role')
      ->withTimestamps();
  }

  /**
   * Override hasPermissionTo from HasRoles trait
   */
  public function hasPermissionTo($permission, $guardName = null): bool
  {
    $permissionName = $permission instanceof Permission ? $permission->name : $permission;

    // التحقق من وجود حظر مباشر للصلاحية
    $permissionModel = Permission::where('name', $permissionName)->first();
    if (!$permissionModel) {
      return false;
    }

    $forbidden = DB::table('model_has_permissions')
      ->where([
        'model_type' => get_class($this),
        'model_id' => $this->id,
        'permission_id' => $permissionModel->id,
        'forbidden' => true
      ])
      ->exists();

    if ($forbidden) {
      return false;
    }

    // التحقق من الصلاحيات بشكل مباشر
    return $this->permissions->contains('name', $permissionName) ||
      $this->roles->flatMap->permissions->contains('name', $permissionName);
  }
}
