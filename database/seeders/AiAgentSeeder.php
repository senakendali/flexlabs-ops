<?php

namespace Database\Seeders;

use App\Models\AiAgent;
use Illuminate\Database\Seeder;

class AiAgentSeeder extends Seeder
{
    public function run(): void
    {
        $agents = [
            [
                'division' => 'academic',
                'code' => 'luna',
                'name' => 'Luna',
                'role' => 'Academic Secretary',
                'description' => 'Orchestrates academic requests, coordinates AI agents, monitors workflows, and communicates results to the user.',
                'system_prompt' => null,
                'agent_type' => 'orchestrator',
                'status' => 'active',
                'runtime_state' => 'idle',
                'avatar' => 'images/agent/01.png',
                'can_delegate' => true,
            ],

            [
                'division' => 'academic',
                'code' => 'raka',
                'name' => 'Raka',
                'role' => 'Academic Admin',
                'description' => 'Handles academic administration, class monitoring, schedules, attendance, student progress, assignments, quizzes, and operational academic data.',
                'system_prompt' => null,
                'agent_type' => 'specialist',
                'status' => 'active',
                'runtime_state' => 'idle',
                'avatar' => null,
                'can_delegate' => false,
            ],

            [
                'division' => 'academic',
                'code' => 'bagas',
                'name' => 'Bagas',
                'role' => 'Learning Content Developer',
                'description' => 'Develops learning content including session plans, teaching materials, slide outlines, worksheets, assignments, and instructor support materials.',
                'system_prompt' => null,
                'agent_type' => 'specialist',
                'status' => 'active',
                'runtime_state' => 'idle',
                'avatar' => null,
                'can_delegate' => false,
            ],

            [
                'division' => 'academic',
                'code' => 'nova',
                'name' => 'Nova',
                'role' => 'Program Development Specialist',
                'description' => 'Supports program research, curriculum development, competency mapping, program structure, and program improvement analysis.',
                'system_prompt' => null,
                'agent_type' => 'specialist',
                'status' => 'active',
                'runtime_state' => 'idle',
                'avatar' => null,
                'can_delegate' => false,
            ],
        ];

        foreach ($agents as $agent) {
            AiAgent::updateOrCreate(
                [
                    'code' => $agent['code'],
                ],
                $agent
            );
        }
    }
}