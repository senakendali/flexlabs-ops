<?php

namespace Database\Seeders;

use App\Models\AssessmentComponent;
use App\Models\AssessmentRubric;
use App\Models\AssessmentRubricCriteria;
use App\Models\AssessmentRubricLevel;
use App\Models\AssessmentTemplate;
use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssessmentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $softwareEngineeringProgram = $this->findProgram([
            'Software Engineering',
            'Introduction to Software Engineering',
            'Intro Software Engineering',
            'AI-Powered Software Engineering',
            'Software Engineer',
        ]);

        $uiUxProgram = $this->findProgram([
            'UI/UX',
            'UI UX',
            'Introduction to UI/UX Design',
            'Intro UI/UX Design',
            'UI/UX Design',
            'UI UX Design',
            'Augmented UI/UX Design Lab',
        ]);

        if ($softwareEngineeringProgram) {
            $this->seedSoftwareEngineeringIntro($softwareEngineeringProgram);
        }

        if ($uiUxProgram) {
            $this->seedUiUxIntro($uiUxProgram);
        }
    }

    private function findProgram(array $names): ?Program
    {
        foreach ($names as $name) {
            $program = Program::query()
                ->where('name', $name)
                ->first();

            if ($program) {
                return $program;
            }
        }

        foreach ($names as $name) {
            $program = Program::query()
                ->where('name', 'like', "%{$name}%")
                ->first();

            if ($program) {
                return $program;
            }
        }

        return null;
    }

    private function seedSoftwareEngineeringIntro(Program $program): void
    {
        $template = AssessmentTemplate::query()->updateOrCreate(
            [
                'program_id' => $program->id,
                'code' => 'se_intro_assessment',
            ],
            [
                'name' => 'Software Engineering Intro Assessment',
                'description' => 'Assessment template for Intro Software Engineering program.',
                'passing_score' => 70,
                'min_attendance_percent' => 75,
                'min_progress_percent' => 80,
                'requires_final_project' => true,
                'is_active' => true,
            ]
        );

        $components = [
            [
                'name' => 'Attendance',
                'code' => 'attendance',
                'type' => 'attendance',
                'weight' => 10,
                'is_auto_calculated' => true,
                'description' => 'Live session and mentoring attendance.',
                'sort_order' => 1,
            ],
            [
                'name' => 'VOD Progress',
                'code' => 'vod_progress',
                'type' => 'progress',
                'weight' => 15,
                'is_auto_calculated' => true,
                'description' => 'Video on demand and lesson completion progress.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Quiz',
                'code' => 'quiz',
                'type' => 'quiz',
                'weight' => 15,
                'is_auto_calculated' => true,
                'description' => 'Knowledge check and quiz score.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Coding Practice',
                'code' => 'coding_practice',
                'type' => 'assignment',
                'weight' => 25,
                'is_auto_calculated' => false,
                'description' => 'Technical practice score based on coding assignments.',
                'sort_order' => 4,
                'rubric' => [
                    'name' => 'Coding Practice Rubric',
                    'description' => 'Rubric for evaluating technical practice and coding assignments.',
                    'criteria' => [
                        [
                            'name' => 'Functionality',
                            'code' => 'functionality',
                            'weight' => 40,
                            'description' => 'Features work according to the given requirement.',
                            'sort_order' => 1,
                        ],
                        [
                            'name' => 'Code Structure',
                            'code' => 'code_structure',
                            'weight' => 25,
                            'description' => 'Code is readable, organized, and maintainable.',
                            'sort_order' => 2,
                        ],
                        [
                            'name' => 'UI Implementation',
                            'code' => 'ui_implementation',
                            'weight' => 20,
                            'description' => 'Interface is implemented clearly and consistently.',
                            'sort_order' => 3,
                        ],
                        [
                            'name' => 'Submission Quality',
                            'code' => 'submission_quality',
                            'weight' => 15,
                            'description' => 'Submission is complete, on time, and follows instructions.',
                            'sort_order' => 4,
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Mini Project',
                'code' => 'mini_project',
                'type' => 'project',
                'weight' => 25,
                'is_auto_calculated' => false,
                'description' => 'Mini project or final project evaluation.',
                'sort_order' => 5,
                'rubric' => [
                    'name' => 'Mini Project Rubric',
                    'description' => 'Rubric for evaluating software engineering mini projects.',
                    'criteria' => [
                        [
                            'name' => 'Feature Completeness',
                            'code' => 'feature_completeness',
                            'weight' => 35,
                            'description' => 'Project features are complete based on the project brief.',
                            'sort_order' => 1,
                        ],
                        [
                            'name' => 'Technical Implementation',
                            'code' => 'technical_implementation',
                            'weight' => 30,
                            'description' => 'Technical implementation is correct and reliable.',
                            'sort_order' => 2,
                        ],
                        [
                            'name' => 'UI / Responsiveness',
                            'code' => 'ui_responsiveness',
                            'weight' => 20,
                            'description' => 'UI is clean, usable, and responsive.',
                            'sort_order' => 3,
                        ],
                        [
                            'name' => 'Presentation / Explanation',
                            'code' => 'presentation_explanation',
                            'weight' => 15,
                            'description' => 'Student can explain project flow, logic, and result clearly.',
                            'sort_order' => 4,
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Professional Attitude',
                'code' => 'professional_attitude',
                'type' => 'attitude',
                'weight' => 10,
                'is_auto_calculated' => false,
                'description' => 'Discipline, communication, consistency, and learning attitude.',
                'sort_order' => 6,
                'rubric' => [
                    'name' => 'Professional Attitude Rubric',
                    'description' => 'Rubric for evaluating professional attitude and learning behavior.',
                    'criteria' => [
                        [
                            'name' => 'Discipline',
                            'code' => 'discipline',
                            'weight' => 35,
                            'description' => 'Student follows schedule, deadlines, and class rules.',
                            'sort_order' => 1,
                        ],
                        [
                            'name' => 'Communication',
                            'code' => 'communication',
                            'weight' => 30,
                            'description' => 'Student communicates questions, blockers, and progress clearly.',
                            'sort_order' => 2,
                        ],
                        [
                            'name' => 'Problem Solving Attitude',
                            'code' => 'problem_solving_attitude',
                            'weight' => 35,
                            'description' => 'Student shows initiative and persistence when facing problems.',
                            'sort_order' => 3,
                        ],
                    ],
                ],
            ],
        ];

        $this->seedComponents($template, $components);
    }

    private function seedUiUxIntro(Program $program): void
    {
        $template = AssessmentTemplate::query()->updateOrCreate(
            [
                'program_id' => $program->id,
                'code' => 'uiux_intro_assessment',
            ],
            [
                'name' => 'UI/UX Intro Assessment',
                'description' => 'Assessment template for Intro UI/UX Design program.',
                'passing_score' => 70,
                'min_attendance_percent' => 75,
                'min_progress_percent' => 80,
                'requires_final_project' => true,
                'is_active' => true,
            ]
        );

        $components = [
            [
                'name' => 'Attendance',
                'code' => 'attendance',
                'type' => 'attendance',
                'weight' => 10,
                'is_auto_calculated' => true,
                'description' => 'Live session and mentoring attendance.',
                'sort_order' => 1,
            ],
            [
                'name' => 'VOD Progress',
                'code' => 'vod_progress',
                'type' => 'progress',
                'weight' => 15,
                'is_auto_calculated' => true,
                'description' => 'Video on demand and lesson completion progress.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Quiz',
                'code' => 'quiz',
                'type' => 'quiz',
                'weight' => 10,
                'is_auto_calculated' => true,
                'description' => 'Knowledge check and design concept quiz score.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Design Practice',
                'code' => 'design_practice',
                'type' => 'assignment',
                'weight' => 25,
                'is_auto_calculated' => false,
                'description' => 'Design practice score based on wireframe, layout, and UI exercise.',
                'sort_order' => 4,
                'rubric' => [
                    'name' => 'Design Practice Rubric',
                    'description' => 'Rubric for evaluating design practice assignments.',
                    'criteria' => [
                        [
                            'name' => 'Visual Layout',
                            'code' => 'visual_layout',
                            'weight' => 25,
                            'description' => 'Layout is clear, balanced, and visually structured.',
                            'sort_order' => 1,
                        ],
                        [
                            'name' => 'Design Consistency',
                            'code' => 'design_consistency',
                            'weight' => 25,
                            'description' => 'Spacing, typography, color, and components are consistent.',
                            'sort_order' => 2,
                        ],
                        [
                            'name' => 'UX Thinking',
                            'code' => 'ux_thinking',
                            'weight' => 25,
                            'description' => 'Design decisions consider user needs and usability.',
                            'sort_order' => 3,
                        ],
                        [
                            'name' => 'Figma Organization',
                            'code' => 'figma_organization',
                            'weight' => 15,
                            'description' => 'Figma file is organized, named properly, and easy to review.',
                            'sort_order' => 4,
                        ],
                        [
                            'name' => 'Submission Quality',
                            'code' => 'submission_quality',
                            'weight' => 10,
                            'description' => 'Submission is complete, on time, and follows instructions.',
                            'sort_order' => 5,
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Case Study / Mini Project',
                'code' => 'case_study_mini_project',
                'type' => 'project',
                'weight' => 30,
                'is_auto_calculated' => false,
                'description' => 'Mini case study project from problem understanding to prototype.',
                'sort_order' => 5,
                'rubric' => [
                    'name' => 'Case Study / Mini Project Rubric',
                    'description' => 'Rubric for evaluating UI/UX case study and mini project.',
                    'criteria' => [
                        [
                            'name' => 'Problem Understanding',
                            'code' => 'problem_understanding',
                            'weight' => 20,
                            'description' => 'Student understands the problem, user needs, and context.',
                            'sort_order' => 1,
                        ],
                        [
                            'name' => 'User Flow',
                            'code' => 'user_flow',
                            'weight' => 20,
                            'description' => 'User flow is logical, clear, and aligned with the case.',
                            'sort_order' => 2,
                        ],
                        [
                            'name' => 'Wireframe Quality',
                            'code' => 'wireframe_quality',
                            'weight' => 20,
                            'description' => 'Wireframe communicates structure and interaction clearly.',
                            'sort_order' => 3,
                        ],
                        [
                            'name' => 'UI Quality',
                            'code' => 'ui_quality',
                            'weight' => 25,
                            'description' => 'Final UI is clean, consistent, and usable.',
                            'sort_order' => 4,
                        ],
                        [
                            'name' => 'Prototype / Presentation',
                            'code' => 'prototype_presentation',
                            'weight' => 15,
                            'description' => 'Prototype and presentation explain the solution clearly.',
                            'sort_order' => 5,
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Professional Attitude',
                'code' => 'professional_attitude',
                'type' => 'attitude',
                'weight' => 10,
                'is_auto_calculated' => false,
                'description' => 'Discipline, communication, feedback response, and learning attitude.',
                'sort_order' => 6,
                'rubric' => [
                    'name' => 'Professional Attitude Rubric',
                    'description' => 'Rubric for evaluating professional attitude and design learning behavior.',
                    'criteria' => [
                        [
                            'name' => 'Discipline',
                            'code' => 'discipline',
                            'weight' => 30,
                            'description' => 'Student follows schedule, deadlines, and class rules.',
                            'sort_order' => 1,
                        ],
                        [
                            'name' => 'Communication',
                            'code' => 'communication',
                            'weight' => 30,
                            'description' => 'Student communicates ideas, questions, and progress clearly.',
                            'sort_order' => 2,
                        ],
                        [
                            'name' => 'Feedback Response',
                            'code' => 'feedback_response',
                            'weight' => 40,
                            'description' => 'Student can receive feedback and improve the design based on it.',
                            'sort_order' => 3,
                        ],
                    ],
                ],
            ],
        ];

        $this->seedComponents($template, $components);
    }

    private function seedComponents(AssessmentTemplate $template, array $components): void
    {
        foreach ($components as $componentData) {
            $rubricData = $componentData['rubric'] ?? null;

            unset($componentData['rubric']);

            $component = AssessmentComponent::query()->updateOrCreate(
                [
                    'assessment_template_id' => $template->id,
                    'code' => $componentData['code'],
                ],
                [
                    'name' => $componentData['name'],
                    'type' => $componentData['type'],
                    'weight' => $componentData['weight'],
                    'max_score' => $componentData['max_score'] ?? 100,
                    'is_required' => $componentData['is_required'] ?? true,
                    'is_auto_calculated' => $componentData['is_auto_calculated'] ?? false,
                    'sort_order' => $componentData['sort_order'] ?? 0,
                    'description' => $componentData['description'] ?? null,
                ]
            );

            if ($rubricData) {
                $this->seedRubric($component, $rubricData);
            }
        }
    }

    private function seedRubric(AssessmentComponent $component, array $rubricData): void
    {
        $rubric = AssessmentRubric::query()->updateOrCreate(
            [
                'assessment_component_id' => $component->id,
                'name' => $rubricData['name'],
            ],
            [
                'description' => $rubricData['description'] ?? null,
                'is_active' => true,
            ]
        );

        foreach ($rubricData['criteria'] ?? [] as $criteriaData) {
            AssessmentRubricCriteria::query()->updateOrCreate(
                [
                    'assessment_rubric_id' => $rubric->id,
                    'code' => $criteriaData['code'] ?? Str::slug($criteriaData['name'], '_'),
                ],
                [
                    'name' => $criteriaData['name'],
                    'description' => $criteriaData['description'] ?? null,
                    'weight' => $criteriaData['weight'],
                    'max_score' => $criteriaData['max_score'] ?? 100,
                    'sort_order' => $criteriaData['sort_order'] ?? 0,
                    'is_required' => $criteriaData['is_required'] ?? true,
                ]
            );
        }

        $this->seedDefaultRubricLevels($rubric);
    }

    private function seedDefaultRubricLevels(AssessmentRubric $rubric): void
    {
        $levels = [
            [
                'name' => 'Excellent',
                'min_score' => 90,
                'max_score' => 100,
                'description' => 'Exceeds expectations with strong quality and consistency.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Good',
                'min_score' => 80,
                'max_score' => 89.99,
                'description' => 'Meets expectations with only minor improvements needed.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Fair',
                'min_score' => 70,
                'max_score' => 79.99,
                'description' => 'Meets minimum expectations but needs noticeable improvement.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Needs Improvement',
                'min_score' => 0,
                'max_score' => 69.99,
                'description' => 'Does not yet meet expectations and needs further support.',
                'sort_order' => 4,
            ],
        ];

        foreach ($levels as $levelData) {
            AssessmentRubricLevel::query()->updateOrCreate(
                [
                    'assessment_rubric_id' => $rubric->id,
                    'name' => $levelData['name'],
                ],
                [
                    'min_score' => $levelData['min_score'],
                    'max_score' => $levelData['max_score'],
                    'description' => $levelData['description'],
                    'sort_order' => $levelData['sort_order'],
                ]
            );
        }
    }
}