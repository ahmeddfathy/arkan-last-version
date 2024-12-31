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
}
