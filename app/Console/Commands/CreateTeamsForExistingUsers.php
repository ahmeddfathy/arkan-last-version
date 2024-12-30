<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\User;
use Illuminate\Console\Command;

class CreateTeamsForExistingUsers extends Command
{
  protected $signature = 'teams:create-for-existing-users';
  protected $description = 'Create teams for existing users who don\'t have one';

  public function handle()
  {
    $users = User::doesntHave('ownedTeams')->get();
    $count = 0;

    foreach ($users as $user) {
      $team = Team::forceCreate([
        'user_id' => $user->id,
        'name' => explode(' ', $user->name, 2)[0] . "'s Team",
        'personal_team' => true,
      ]);

      $user->current_team_id = $team->id;
      $user->save();

      $count++;
    }

    $this->info("{$count} teams created successfully!");
  }
}
