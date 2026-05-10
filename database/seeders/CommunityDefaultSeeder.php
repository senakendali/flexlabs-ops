<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\CommunityChannel;
use App\Models\CommunityGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CommunityDefaultSeeder extends Seeder
{
    public function run(): void
    {
        $batches = Batch::query()
            ->with('program')
            ->get();

        foreach ($batches as $batch) {
            $programName = $batch->program->name ?? 'Program';
            $batchName = $batch->name ?? ('Batch ' . $batch->id);

            $group = CommunityGroup::query()->firstOrCreate(
                [
                    'batch_id' => $batch->id,
                ],
                [
                    'program_id' => $batch->program_id ?? null,
                    'name' => $programName . ' - ' . $batchName,
                    'slug' => Str::slug($programName . '-' . $batchName . '-' . $batch->id),
                    'description' => 'Official learning community for ' . $programName . ' - ' . $batchName . '.',
                    'is_default' => true,
                    'is_active' => true,
                ]
            );

            $channels = [
                [
                    'name' => 'Announcements',
                    'slug' => 'announcements',
                    'type' => 'announcement',
                    'description' => 'Official announcements from Flexlabs.',
                    'is_readonly' => true,
                    'is_pinned' => true,
                    'sort_order' => 1,
                ],
                [
                    'name' => 'General Discussion',
                    'slug' => 'general-discussion',
                    'type' => 'general',
                    'description' => 'General discussion space for Pioneers.',
                    'is_readonly' => false,
                    'is_pinned' => false,
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Coding Help',
                    'slug' => 'coding-help',
                    'type' => 'coding_help',
                    'description' => 'Ask questions about coding, errors, and technical problems.',
                    'is_readonly' => false,
                    'is_pinned' => false,
                    'sort_order' => 3,
                ],
                [
                    'name' => 'Project Discussion',
                    'slug' => 'project-discussion',
                    'type' => 'project',
                    'description' => 'Discuss projects, portfolio, and implementation ideas.',
                    'is_readonly' => false,
                    'is_pinned' => false,
                    'sort_order' => 4,
                ],
                [
                    'name' => 'Career & Portfolio',
                    'slug' => 'career-portfolio',
                    'type' => 'career',
                    'description' => 'Career preparation, CV, portfolio, and interview discussion.',
                    'is_readonly' => false,
                    'is_pinned' => false,
                    'sort_order' => 5,
                ],
            ];

            foreach ($channels as $channel) {
                CommunityChannel::query()->firstOrCreate(
                    [
                        'community_group_id' => $group->id,
                        'slug' => $channel['slug'],
                    ],
                    [
                        'name' => $channel['name'],
                        'type' => $channel['type'],
                        'description' => $channel['description'],
                        'is_readonly' => $channel['is_readonly'],
                        'is_pinned' => $channel['is_pinned'],
                        'is_active' => true,
                        'sort_order' => $channel['sort_order'],
                    ]
                );
            }
        }
    }
}