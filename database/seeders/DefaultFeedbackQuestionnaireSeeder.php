<?php

namespace Database\Seeders;

use App\Models\FeedbackForm;
use App\Models\FeedbackQuestion;
use Illuminate\Database\Seeder;

class DefaultFeedbackQuestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        $form = FeedbackForm::query()->updateOrCreate(
            [
                'slug' => 'default-program-feedback',
            ],
            [
                'title' => 'Feedback Program Belajar FlexLabs',
                'description' => 'Bantu kami memahami pengalaman belajar kamu selama mengikuti program ini. Jawaban kamu akan digunakan untuk meningkatkan kualitas materi, instructor, platform, support, dan pengalaman belajar student berikutnya.',
                'type' => 'program',
                'program_id' => null,
                'batch_id' => null,
                'is_active' => true,
                'starts_at' => null,
                'ends_at' => null,
                'settings' => [
                    'is_template' => true,
                    'allow_anonymous' => false,
                    'show_progress' => true,
                    'thank_you_title' => 'Terima kasih atas feedback kamu!',
                    'thank_you_message' => 'Masukan kamu membantu FlexLabs meningkatkan kualitas program belajar berikutnya.',
                ],
            ]
        );

        $questions = [
            [
                'section' => 'Program',
                'question_text' => 'Seberapa puas kamu dengan program ini secara keseluruhan?',
                'help_text' => null,
                'question_type' => 'rating_1_5',
                'rating_scale' => 5,
                'options' => null,
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'section' => 'Program',
                'question_text' => 'Apakah program ini sesuai dengan ekspektasi kamu sebelum bergabung?',
                'help_text' => null,
                'question_type' => 'rating_1_5',
                'rating_scale' => 5,
                'options' => null,
                'is_required' => true,
                'sort_order' => 2,
            ],
            [
                'section' => 'Materi',
                'question_text' => 'Apakah materi program sesuai dengan kebutuhan kamu?',
                'help_text' => null,
                'question_type' => 'rating_1_5',
                'rating_scale' => 5,
                'options' => null,
                'is_required' => true,
                'sort_order' => 3,
            ],
            [
                'section' => 'Materi',
                'question_text' => 'Seberapa mudah materi dipahami?',
                'help_text' => null,
                'question_type' => 'rating_1_5',
                'rating_scale' => 5,
                'options' => null,
                'is_required' => true,
                'sort_order' => 4,
            ],
            [
                'section' => 'Instructor',
                'question_text' => 'Seberapa jelas instructor menjelaskan materi?',
                'help_text' => null,
                'question_type' => 'rating_1_5',
                'rating_scale' => 5,
                'options' => null,
                'is_required' => true,
                'sort_order' => 5,
            ],
            [
                'section' => 'Instructor',
                'question_text' => 'Seberapa membantu instructor saat kamu mengalami kesulitan?',
                'help_text' => null,
                'question_type' => 'rating_1_5',
                'rating_scale' => 5,
                'options' => null,
                'is_required' => true,
                'sort_order' => 6,
            ],
            [
                'section' => 'Praktik',
                'question_text' => 'Apakah praktik/tugas membantu kamu memahami materi?',
                'help_text' => null,
                'question_type' => 'rating_1_5',
                'rating_scale' => 5,
                'options' => null,
                'is_required' => true,
                'sort_order' => 7,
            ],
            [
                'section' => 'Platform',
                'question_text' => 'Apakah platform belajar FlexLabs mudah digunakan?',
                'help_text' => null,
                'question_type' => 'rating_1_5',
                'rating_scale' => 5,
                'options' => null,
                'is_required' => true,
                'sort_order' => 8,
            ],
            [
                'section' => 'Support',
                'question_text' => 'Apakah komunikasi dan support dari tim FlexLabs sudah baik?',
                'help_text' => null,
                'question_type' => 'rating_1_5',
                'rating_scale' => 5,
                'options' => null,
                'is_required' => true,
                'sort_order' => 9,
            ],
            [
                'section' => 'Outcome',
                'question_text' => 'Setelah mengikuti program ini, apakah skill kamu meningkat?',
                'help_text' => null,
                'question_type' => 'rating_1_5',
                'rating_scale' => 5,
                'options' => null,
                'is_required' => true,
                'sort_order' => 10,
            ],
            [
                'section' => 'NPS',
                'question_text' => 'Seberapa besar kemungkinan kamu merekomendasikan FlexLabs ke teman atau orang lain?',
                'help_text' => 'Gunakan skala 0 sampai 10. 0 berarti sangat tidak mungkin, 10 berarti sangat mungkin.',
                'question_type' => 'rating_0_10',
                'rating_scale' => 10,
                'options' => null,
                'is_required' => true,
                'sort_order' => 11,
            ],
            [
                'section' => 'Insight',
                'question_text' => 'Apa bagian terbaik dari program ini menurut kamu?',
                'help_text' => null,
                'question_type' => 'textarea',
                'rating_scale' => null,
                'options' => null,
                'is_required' => true,
                'sort_order' => 12,
            ],
            [
                'section' => 'Insight',
                'question_text' => 'Apa yang perlu kami perbaiki dari program ini?',
                'help_text' => null,
                'question_type' => 'textarea',
                'rating_scale' => null,
                'options' => null,
                'is_required' => true,
                'sort_order' => 13,
            ],
            [
                'section' => 'Testimonial',
                'question_text' => 'Boleh tuliskan testimonial singkat tentang pengalaman kamu mengikuti program ini?',
                'help_text' => 'Opsional. Testimonial ini bisa membantu calon student lain memahami pengalaman belajar di FlexLabs.',
                'question_type' => 'textarea',
                'rating_scale' => null,
                'options' => null,
                'is_required' => false,
                'sort_order' => 14,
            ],
            [
                'section' => 'Next Step',
                'question_text' => 'Apakah kamu tertarik untuk mengikuti program lanjutan di FlexLabs setelah program ini selesai?',
                'help_text' => 'Jawaban ini tidak mengikat. Kami hanya ingin memahami kebutuhan belajar kamu selanjutnya.',
                'question_type' => 'single_choice',
                'rating_scale' => null,
                'options' => [
                    'Ya, saya tertarik',
                    'Mungkin, saya ingin tahu dulu program yang cocok',
                    'Belum tertarik saat ini',
                ],
                'is_required' => true,
                'sort_order' => 15,
            ],
            [
                'section' => 'Next Step',
                'question_text' => 'Skill atau program apa yang ingin kamu pelajari selanjutnya?',
                'help_text' => 'Opsional. Contoh: Laravel lanjutan, UI/UX, AI tools, project portfolio, career preparation, dan lainnya.',
                'question_type' => 'textarea',
                'rating_scale' => null,
                'options' => null,
                'is_required' => false,
                'sort_order' => 16,
            ],
        ];

        foreach ($questions as $question) {
            FeedbackQuestion::query()->updateOrCreate(
                [
                    'feedback_form_id' => $form->id,
                    'sort_order' => $question['sort_order'],
                ],
                [
                    'section' => $question['section'],
                    'question_text' => $question['question_text'],
                    'help_text' => $question['help_text'],
                    'question_type' => $question['question_type'],
                    'rating_scale' => $question['rating_scale'],
                    'options' => $question['options'],
                    'is_required' => $question['is_required'],
                    'is_active' => true,
                ]
            );
        }
    }
}