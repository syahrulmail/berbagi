<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProgramCategoryTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeUser(string $role): User
    {
        return User::create([
            'name' => ucfirst($role) . ' ' . uniqid(),
            'username' => 'user_' . uniqid(),
            'slug' => 'user_' . uniqid(),
            'email' => uniqid() . '@test.local',
            'phone' => '628' . uniqid(),
            'password' => bcrypt('password'),
            'role' => $role,
        ]);
    }

    public function test_zakat_category_is_registered()
    {
        $this->assertSame('Zakat', Program::CATEGORIES['ZPP']);
    }

    public function test_zakat_category_is_listed_on_program_form()
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('programs.create'))
            ->assertOk()
            ->assertSee('Zakat (ZPP)');
    }
}
