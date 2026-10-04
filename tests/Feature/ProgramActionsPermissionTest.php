<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProgramActionsPermissionTest extends TestCase
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

    protected function makeProgram(): Program
    {
        return Program::create([
            'name' => 'Program Aksi ' . uniqid(),
            'slug' => 'program-aksi-' . uniqid(),
            'program_category' => 'WAP',
            'is_active' => true,
        ]);
    }

    public function test_admin_sees_edit_and_delete_actions()
    {
        $admin = $this->makeUser('admin');
        $program = $this->makeProgram();

        $this->actingAs($admin)
            ->get(route('programs.index', ['search' => $program->name]))
            ->assertOk()
            ->assertSee(route('programs.edit', $program), false)
            ->assertSee(route('programs.destroy', $program), false);
    }

    public function test_supervisor_does_not_see_edit_and_delete_actions()
    {
        $branch = Branch::create(['code' => 'SA-' . uniqid(), 'name' => 'Cabang Supervisor', 'is_active' => true]);
        $supervisor = $this->makeUser('supervisor', $branch);
        $program = $this->makeProgram();

        $this->actingAs($supervisor)
            ->get(route('programs.index', ['search' => $program->name]))
            ->assertOk()
            ->assertDontSee(route('programs.edit', $program), false)
            ->assertDontSee(route('programs.destroy', $program), false);
    }

    public function test_agent_does_not_see_edit_and_delete_actions()
    {
        $branch = Branch::create(['code' => 'AG-' . uniqid(), 'name' => 'Cabang Agen', 'is_active' => true]);
        $agent = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();

        $this->actingAs($agent)
            ->get(route('programs.index', ['search' => $program->name]))
            ->assertOk()
            ->assertDontSee(route('programs.edit', $program), false)
            ->assertDontSee(route('programs.destroy', $program), false);
    }

    public function test_supervisor_cannot_open_edit_form()
    {
        $branch = Branch::create(['code' => 'SB-' . uniqid(), 'name' => 'Cabang Supervisor 2', 'is_active' => true]);
        $supervisor = $this->makeUser('supervisor', $branch);
        $program = $this->makeProgram();

        $this->actingAs($supervisor)
            ->get(route('programs.edit', $program))
            ->assertForbidden();
    }

    public function test_agent_cannot_update_program()
    {
        $branch = Branch::create(['code' => 'AC-' . uniqid(), 'name' => 'Cabang Agen 2', 'is_active' => true]);
        $agent = $this->makeUser('agen', $branch);
        $program = $this->makeProgram();

        $this->actingAs($agent)
            ->put(route('programs.update', $program), ['name' => 'Diubah Agen'])
            ->assertForbidden();

        $this->assertNotSame('Diubah Agen', $program->fresh()->name);
    }

    public function test_supervisor_cannot_delete_program()
    {
        $branch = Branch::create(['code' => 'SD-' . uniqid(), 'name' => 'Cabang Supervisor 3', 'is_active' => true]);
        $supervisor = $this->makeUser('supervisor', $branch);
        $program = $this->makeProgram();

        $this->actingAs($supervisor)
            ->delete(route('programs.destroy', $program))
            ->assertForbidden();

        $this->assertDatabaseHas('programs', ['id' => $program->id]);
    }

    public function test_admin_can_open_edit_form()
    {
        $admin = $this->makeUser('admin');
        $program = $this->makeProgram();

        $this->actingAs($admin)
            ->get(route('programs.edit', $program))
            ->assertOk();
    }

    public function test_admin_can_delete_program()
    {
        $admin = $this->makeUser('admin');
        $program = $this->makeProgram();

        $this->actingAs($admin)
            ->delete(route('programs.destroy', $program))
            ->assertRedirect(route('programs.index'));

        $this->assertDatabaseMissing('programs', ['id' => $program->id]);
    }
}
