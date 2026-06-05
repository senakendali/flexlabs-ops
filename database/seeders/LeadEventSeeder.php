<?php

namespace Database\Seeders;

use App\Models\LeadEvent;
use Illuminate\Database\Seeder;

class LeadEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LeadEvent::query()->updateOrCreate(
            [
                'slug' => 'job-fair-dan-edu-fair-smk-media-informatika-2026',
            ],
            [
                'title' => 'Job Fair dan Edu Fair SMK Media Informatika 2026',

                /*
                |--------------------------------------------------------------------------
                | Event Identity
                |--------------------------------------------------------------------------
                */
                'event_type' => 'attended',
                'event_mode' => 'offline',
                'location' => 'SMK Media Informatika',
                'event_url' => null,

                /*
                |--------------------------------------------------------------------------
                | Content
                |--------------------------------------------------------------------------
                */
                'image' => null,
                'short_description' => 'Flexlabs hadir di Job Fair dan Edu Fair SMK Media Informatika 2026 untuk mengenalkan peluang belajar, karier, dan pengembangan skill digital.',
                'description' => 'Job Fair dan Edu Fair SMK Media Informatika 2026 adalah kegiatan yang mempertemukan siswa, alumni, industri, dan institusi pendidikan untuk membuka peluang karier, magang, studi lanjutan, serta pengembangan skill. Flexlabs hadir untuk memperkenalkan program pembelajaran digital, software engineering, AI productivity, dan peluang pengembangan karier di bidang teknologi.',

                /*
                |--------------------------------------------------------------------------
                | Schedule
                |--------------------------------------------------------------------------
                | Isi nanti kalau tanggal dan jam sudah fix.
                |--------------------------------------------------------------------------
                */
                'start_date' => null,
                'end_date' => null,
                'start_time' => null,
                'end_time' => null,

                /*
                |--------------------------------------------------------------------------
                | CTA
                |--------------------------------------------------------------------------
                */
                'cta_label' => 'Daftar Minat',
                'external_registration_url' => null,

                /*
                |--------------------------------------------------------------------------
                | Display Setting
                |--------------------------------------------------------------------------
                */
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 1,

                /*
                |--------------------------------------------------------------------------
                | Extra Data
                |--------------------------------------------------------------------------
                */
                'metadata' => [
                    'school' => 'SMK Media Informatika',
                    'year' => 2026,
                    'purpose' => 'lead_capture',
                    'audience' => [
                        'students',
                        'alumni',
                        'teachers',
                        'parents',
                    ],
                    'program_focus' => [
                        'Software Engineering',
                        'AI Productivity',
                        'Web Development',
                        'Career Preparation',
                    ],
                ],
            ]
        );
    }
}