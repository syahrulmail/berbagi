<?php

namespace Database\Seeders;

use App\Models\CampaignTag;
use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    /**
     * Seed program wakaf dan campaign tags.
     *
     * @return void
     */
    public function run()
    {
        $tags = [
            ['name' => 'Wakaf Al-Quran', 'slug' => 'wakaf-al-quran', 'color' => '#2ECC71'],
            ['name' => 'Wakaf Mushaf', 'slug' => 'wakaf-mushaf', 'color' => '#1E3A5F'],
            ['name' => 'Program Da\'i', 'slug' => 'program-dai', 'color' => '#E67E22'],
            ['name' => 'Bantuan Ummat', 'slug' => 'bantuan-ummat', 'color' => '#E74C3C'],
        ];

        foreach ($tags as $tag) {
            CampaignTag::updateOrCreate(['slug' => $tag['slug']], $tag);
        }

        $programs = [
            [
                'name' => 'Wakaf Al-Quran untuk Daerah Terpencil',
                'slug' => 'wakaf-alquran-daerah-terpencil',
                'description' => 'Penyediaan Al-Quran untuk masjid dan pesantren di daerah terpencil.',
                'goal_amount' => 250000000,
                'is_active' => true,
                'tags' => ['wakaf-al-quran'],
            ],
            [
                'name' => 'Program Wakaf Mushaf Mushaf Nusantara',
                'slug' => 'wakaf-mushaf-nusantara',
                'description' => 'Pencetakan dan distribusi mushaf Al-Quran terjemah.',
                'goal_amount' => 150000000,
                'is_active' => true,
                'tags' => ['wakaf-mushaf'],
            ],
            [
                'name' => 'Dukungan Program Da\'i Nusantara',
                'slug' => 'program-dai-nusantara',
                'description' => 'Pendukung biaya hidup dan operasional para da\'i di pelosok.',
                'goal_amount' => 300000000,
                'is_active' => true,
                'tags' => ['program-dai'],
            ],
            [
                'name' => 'Bantuan Ummat untuk Korban Bencana',
                'slug' => 'bantuan-ummat-bencana',
                'description' => 'Bantuan darurat untuk korban bencana alam.',
                'goal_amount' => 100000000,
                'is_active' => true,
                'tags' => ['bantuan-ummat'],
            ],
        ];

        foreach ($programs as $programData) {
            $tags = $programData['tags'];
            unset($programData['tags']);

            $program = Program::updateOrCreate(['slug' => $programData['slug']], $programData);
            $program->campaignTags()->sync(
                CampaignTag::whereIn('slug', $tags)->pluck('id')->toArray()
            );
        }
    }
}
