<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProgramShareTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeUser(string $role, ?Branch $branch = null): User
    {
        $data = [
            'name' => ucfirst($role) . ' ' . uniqid(),
            'username' => 'user_' . $role . '_' . uniqid(),
            'slug' => 'user_' . $role . '_' . uniqid(),
            'email' => $role . '_' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'role' => $role,
        ];

        if ($branch) {
            $data['branch_id'] = $branch->id;
        }

        return User::create($data);
    }

    protected function makeProgram(bool $active = true): Program
    {
        return Program::create([
            'name' => 'Program ' . uniqid(),
            'slug' => 'program-' . uniqid(),
            'program_category' => 'WAP',
            'is_active' => $active,
        ]);
    }

    public function test_agent_sees_share_button_for_active_program()
    {
        $branch = Branch::create([
            'code' => 'BR-' . uniqid(),
            'name' => 'Cabang Share ' . uniqid(),
            'is_active' => true,
        ]);

        $agent = $this->makeUser('agen', $branch);
        $program = $this->makeProgram(true);

        $this->actingAs($agent)->get(route('programs.index', ['search' => $program->name]))
            ->assertOk()
            ->assertSee('data-share-url=', false)
            ->assertSee('/cs/' . $agent->slug . '/program/' . $program->slug, false);
    }

    public function test_agent_does_not_see_share_button_for_inactive_program()
    {
        $branch = Branch::create([
            'code' => 'BR-' . uniqid(),
            'name' => 'Cabang Nonaktif ' . uniqid(),
            'is_active' => true,
        ]);

        $agent = $this->makeUser('agen', $branch);
        $program = $this->makeProgram(false);

        $this->actingAs($agent)->get(route('programs.index', ['search' => $program->name]))
            ->assertOk()
            ->assertDontSee('data-share-url=', false)
            ->assertDontSee('/cs/' . $agent->slug . '/program/' . $program->slug, false);
    }

    public function test_admin_sees_share_button_with_public_program_url()
    {
        $admin = $this->makeUser('admin');
        $program = $this->makeProgram(true);

        $this->actingAs($admin)->get(route('programs.index', ['search' => $program->name]))
            ->assertOk()
            ->assertSee('data-share-url=', false)
            ->assertSee('/program/' . $program->slug, false)
            ->assertDontSee('/cs/' . $admin->slug . '/program/' . $program->slug, false);
    }

    public function test_progress_column_is_hidden()
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('programs.index'))
            ->assertOk()
            ->assertDontSee('Progress');
    }
}
