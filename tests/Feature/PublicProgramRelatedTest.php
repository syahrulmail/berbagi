<?php

namespace Tests\Feature;

use App\Models\CampaignTag;
use App\Models\Program;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PublicProgramRelatedTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeProgram(string $name): Program
    {
        return Program::create([
            'name' => $name,
            'slug' => 'program-' . uniqid(),
            'program_category' => 'WAP',
            'is_active' => true,
        ]);
    }

    public function test_public_program_shows_four_tag_related_programs(): void
    {
        $tag = CampaignTag::create([
            'name' => 'Peduli ' . uniqid(),
            'slug' => 'peduli-' . uniqid(),
            'color' => '#08A899',
        ]);

        $main = $this->makeProgram('Program Utama');
        $main->campaignTags()->sync([$tag->id]);

        for ($i = 1; $i <= 5; $i++) {
            $this->makeProgram('Program Serupa ' . $i)->campaignTags()->sync([$tag->id]);
        }

        $response = $this->get(route('public.program', $main->slug));

        $response->assertOk();
        $this->assertSame(4, substr_count($response->getContent(), 'program-card group'));
    }

    public function test_public_program_fills_related_section_without_tags(): void
    {
        $main = $this->makeProgram('Program Tanpa Label');

        for ($i = 1; $i <= 5; $i++) {
            $this->makeProgram('Program Lain ' . $i);
        }

        $response = $this->get(route('public.program', $main->slug));

        $response->assertOk();
        $this->assertSame(4, substr_count($response->getContent(), 'program-card group'));
    }
}
