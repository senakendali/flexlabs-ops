<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Official Roles
    |--------------------------------------------------------------------------
    */

    'roles' => [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'academic' => 'Academic',
        'marketing' => 'Marketing',
        'sales' => 'Sales',
        'finance' => 'Finance',
        'hr' => 'HR',
        'instructor' => 'Instructor',
        'student' => 'Student',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Types
    |--------------------------------------------------------------------------
    */

    'user_types' => [
        'staff' => 'Staff',
        'instructor' => 'Instructor',
        'student' => 'Student',
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Permissions
    |--------------------------------------------------------------------------
    */

    'permissions' => [

        'super_admin' => [
            '*',
        ],

        'admin' => [
            '*',
        ],

        'academic' => [
            'dashboard.view',

            'internal_memos.view',
            'internal_memos.create',
            'internal_memos.update',
            'internal_memos.submit',
            'internal_memos.approve',
            'internal_memos.reject',
            'internal_memos.export',

            'academic.view',
            'academic.dashboard.view',

            'programs.view',
            'programs.create',
            'programs.update',
            'programs.delete',

            'batches.view',
            'batches.create',
            'batches.update',
            'batches.delete',

            'curriculum.view',
            'curriculum.create',
            'curriculum.update',
            'curriculum.delete',

            'enrollments.view',
            'enrollments.create',
            'enrollments.update',
            'enrollments.delete',

            'students.view',
            'students.create',
            'students.update',
            'students.delete',

            'academic.announcements.view',
            'academic.announcements.create',
            'academic.announcements.update',
            'academic.announcements.delete',

            'assignments.view',
            'assignments.create',
            'assignments.update',
            'assignments.delete',

            'batch_assignments.view',
            'batch_assignments.create',
            'batch_assignments.update',
            'batch_assignments.delete',

            'assignment_submissions.view',
            'assignment_submissions.update',

            'learning_quizzes.view',
            'learning_quizzes.create',
            'learning_quizzes.update',
            'learning_quizzes.delete',

            'batch_learning_quizzes.view',
            'batch_learning_quizzes.create',
            'batch_learning_quizzes.update',
            'batch_learning_quizzes.delete',

            'learning_quiz_attempts.view',

            'assessment_templates.view',
            'assessment_templates.create',
            'assessment_templates.update',
            'assessment_templates.delete',

            'assessment_scores.view',
            'assessment_scores.create',
            'assessment_scores.update',

            'report_cards.view',
            'report_cards.publish',

            'certificates.view',
            'certificates.issue',

            'instructors.view',
            'instructors.create',
            'instructors.update',
            'instructors.delete',

            'instructor_availability.view',
            'instructor_availability.create',
            'instructor_availability.update',
            'instructor_availability.delete',

            'mentoring_sessions.view',
            'mentoring_sessions.create',
            'mentoring_sessions.update',

            'instructor_schedules.view',
            'instructor_schedules.create',
            'instructor_schedules.update',
            'instructor_schedules.delete',

            'student_attendances.view',
            'student_attendances.create',
            'student_attendances.update',

            'instructor_tracking.view',
            'instructor_tracking.create',
            'instructor_tracking.update',

            'trial_themes.view',
            'trial_themes.create',
            'trial_themes.update',
            'trial_themes.delete',

            'trial_schedules.view',
            'trial_schedules.create',
            'trial_schedules.update',
            'trial_schedules.delete',

            'trial_participants.view',
            'trial_participants.create',
            'trial_participants.update',
            'trial_participants.delete',

            'workshops.view',
            'workshops.create',
            'workshops.update',
            'workshops.delete',

            'workshop_schedules.view',
            'workshop_schedules.create',
            'workshop_schedules.update',
            'workshop_schedules.delete',

            'workshop_participants.view',
            'workshop_participants.create',
            'workshop_participants.update',
            'workshop_participants.delete',

            'public_learning_materials.view',
            'public_learning_materials.create',
            'public_learning_materials.update',
            'public_learning_materials.delete',

            'learning_videos.view',
            'learning_videos.create',
            'learning_videos.update',
            'learning_videos.delete',

            'meeting_minutes.view',
            'equipment.view',
            'equipment_borrowings.view',
            'equipment_borrowings.create',
            'equipment_borrowings.update',
            'equipment_borrowings.return',
            'atk_items.view',
            'atk_requests.view',
        ],

        'marketing' => [
            'dashboard.view',

            'internal_memos.view',
            'internal_memos.create',
            'internal_memos.update',
            'internal_memos.submit',
            'internal_memos.approve',
            'internal_memos.reject',
            'internal_memos.export',

            'articles.view',
            'articles.create',
            'articles.update',
            'articles.generate',
            'articles.archive',
            'articles.submit_review',

            'academic.view',

            'trial_themes.view',
            'trial_themes.create',
            'trial_themes.update',
            'trial_themes.delete',

            'trial_schedules.view',
            'trial_schedules.create',
            'trial_schedules.update',
            'trial_schedules.delete',

            'trial_participants.view',
            'trial_participants.create',
            'trial_participants.update',
            'trial_participants.delete',

            'workshops.view',
            'workshops.create',
            'workshops.update',
            'workshops.delete',

            'workshop_schedules.view',
            'workshop_schedules.create',
            'workshop_schedules.update',
            'workshop_schedules.delete',

            'workshop_participants.view',
            'workshop_participants.create',
            'workshop_participants.update',
            'workshop_participants.delete',

            'public_learning_materials.view',
            'public_learning_materials.create',
            'public_learning_materials.update',
            'public_learning_materials.delete',

            'marketing.view',
            'marketing.dashboard.view',

            'marketing_reports.view',
            'marketing_reports.create',
            'marketing_reports.update',

            'campaigns.view',
            'campaigns.create',
            'campaigns.update',
            'campaigns.delete',

            'ads.view',
            'ads.create',
            'ads.update',
            'ads.delete',

            'quizzes.view',
            'quizzes.create',
            'quizzes.update',
            'quizzes.delete',

            'meeting_minutes.view',
            'equipment.view',
            'equipment_borrowings.view',
            'equipment_borrowings.create',
            'equipment_borrowings.update',
            'equipment_borrowings.return',
            'atk_items.view',
            'atk_requests.view',
        ],

        'sales' => [
            'dashboard.view',

            'internal_memos.view',
            'internal_memos.create',
            'internal_memos.update',
            'internal_memos.submit',
            'internal_memos.approve',
            'internal_memos.reject',
            'internal_memos.export',

            'academic.view',

            'enrollments.view',
            'enrollments.create',
            'enrollments.update',

            'students.view',
            'students.create',
            'students.update',

            'student_progress.view',

            'trial_themes.view',
            'trial_themes.create',
            'trial_themes.update',
            'trial_themes.delete',

            'trial_schedules.view',
            'trial_schedules.create',
            'trial_schedules.update',
            'trial_schedules.delete',

            'trial_participants.view',
            'trial_participants.create',
            'trial_participants.update',
            'trial_participants.delete',

            'workshops.view',
            'workshops.create',
            'workshops.update',
            'workshops.delete',

            'workshop_schedules.view',
            'workshop_schedules.create',
            'workshop_schedules.update',
            'workshop_schedules.delete',

            'workshop_participants.view',
            'workshop_participants.create',
            'workshop_participants.update',
            'workshop_participants.delete',

            'public_learning_materials.view',
            'public_learning_materials.create',
            'public_learning_materials.update',
            'public_learning_materials.delete',

            'sales.view',

            'sales_daily_reports.view',
            'sales_daily_reports.create',
            'sales_daily_reports.update',
            'sales_daily_reports.delete',

            'sales_performance.view',

            'finance.view',

            'payment_schedules.view',
            'payment_schedules.create',
            'payment_schedules.update',
            'payment_schedules.approve',

            'payments.view',
            'payments.create',
            'payments.update',
            'payments.approve',
            'payments.reject',

            'orders.view',
            'orders.create',
            'orders.update',

            'sales_orders.view',
            'sales_orders.create',
            'sales_orders.update',

            'meeting_minutes.view',
            'equipment.view',
            'equipment_borrowings.view',
            'equipment_borrowings.create',
            'equipment_borrowings.update',
            'equipment_borrowings.return',
            'atk_items.view',
            'atk_requests.view',
            'atk_requests.approve_budget',
        ],

        'finance' => [
            'dashboard.view',

            'internal_memos.view',
            'internal_memos.create',
            'internal_memos.update',
            'internal_memos.submit',
            'internal_memos.approve',
            'internal_memos.reject',
            'internal_memos.export',

            'academic.view',

            'workshops.view',

            'workshop_schedules.view',
            'workshop_schedules.create',
            'workshop_schedules.update',
            'workshop_schedules.delete',

            'workshop_participants.view',
            'workshop_participants.create',
            'workshop_participants.update',
            'workshop_participants.delete',

            'finance.view',

            'sales_orders.view',
            'sales_orders.create',
            'sales_orders.update',

            'payment_schedules.view',
            'payment_schedules.create',
            'payment_schedules.update',
            'payment_schedules.approve',

            'payments.view',
            'payments.create',
            'payments.update',
            'payments.approve',
            'payments.reject',

            'meeting_minutes.view',
            'equipment.view',
            'equipment_borrowings.view',
            'equipment_borrowings.create',
            'equipment_borrowings.update',
            'equipment_borrowings.return',
            'atk_items.view',
            'atk_requests.view',
            'atk_requests.approve_budget',
        ],

        'hr' => [
            'dashboard.view',

            'internal_memos.view',
            'internal_memos.create',
            'internal_memos.update',
            'internal_memos.submit',
            'internal_memos.approve',
            'internal_memos.reject',
            'internal_memos.export',

            'hr.view',

            'instructors.view',
            'instructor_availability.view',
            'instructor_tracking.view',
            'student_attendances.view',

            'equipment.view',
            'equipment.create',
            'equipment.update',

            'equipment_borrowings.view',
            'equipment_borrowings.create',
            'equipment_borrowings.update',
            'equipment_borrowings.return',
            'equipment_borrowings.approve',

            'atk_items.view',
            'atk_items.create',
            'atk_items.update',

            'atk_requests.view',
            'atk_requests.create',
            'atk_requests.approve',

            'meeting_minutes.view',
            'meeting_minutes.create',
            'meeting_minutes.update',
        ],

        'instructor' => [
            'dashboard.view',

            'internal_memos.view',
            'internal_memos.create',
            'internal_memos.update',
            'internal_memos.submit',
            'internal_memos.approve',
            'internal_memos.reject',
            'internal_memos.export',
            'instructor_tracking.view',
            'instructor_tracking.create',
            'mentoring_sessions.view',
            'mentoring_sessions.update',

            'equipment.view',
            'equipment_borrowings.view',
            'equipment_borrowings.create',
            'equipment_borrowings.update',
            'equipment_borrowings.return',
        ],

        'student' => [
            'student_portal.view',

            'internal_memos.view',
            'internal_memos.create',
            'internal_memos.update',
            'internal_memos.submit',
            'internal_memos.approve',
            'internal_memos.reject',
            'internal_memos.export',

            'equipment.view',
            'equipment_borrowings.view',
            'equipment_borrowings.update',
            'equipment_borrowings.return',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menus
    |--------------------------------------------------------------------------
    | Semua menu diambil dari navbar existing.
    */

    'menus' => [

        [
            'type' => 'link',
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'icon' => 'bi bi-grid-1x2-fill',
            'permission' => 'dashboard.view',
            'active' => ['dashboard'],
        ],

        [
            'type' => 'mega',
            'label' => 'Academic',
            'icon' => 'bi bi-mortarboard-fill',
            'permission' => 'academic.view',
            'dropdown_class' => 'dropdown-menu-academic dropdown-menu-mega',
            'active' => [
                'academic.dashboard*',
                'programs.*',
                'batches.*',
                'enrollments.*',
                'students.*',
                'academic.student-progress.*',
                'curriculum.*',
                'public-learning-materials.*',
                'academic.learning-videos.*',
                'assignments.*',
                'batch-assignments.*',
                'assignment-submissions.*',
                'learning-quizzes.*',
                'batch-learning-quizzes.*',
                'learning-quiz-attempts.*',
                'instructors.*',
                'instructor-schedules.*',
                'instructor-tracking.*',
                'academic.instructor-availability.*',
                'trial-themes.*',
                'trial-schedules.*',
                'trial-participants.*',
                'academic.workshops.*',
                'academic.workshop-schedules.*',
                'academic.workshop-participants.*',
                'academic.mentoring-sessions.*',
                'academic.announcements.*',
                'academic.assessment-templates.*',
                'academic.assessment-scores.*',
                'academic.report-cards.*',
                'academic.certificates.*',
                'academic.attendances.*',
            ],

            'hero' => [
                'kicker' => 'Academic Center',
                'title' => 'Learning Operations',
                'description' => 'Centralized access to academic operations, learning management, assessment, and student development workflows.',
                'icon' => 'bi bi-mortarboard-fill',
                'shortcut' => [
                    'label' => 'Academic Dashboard',
                    'route' => 'academic.dashboard',
                    'permission' => 'academic.dashboard.view',
                    'active' => ['academic.dashboard*'],
                    'icon' => 'bi bi-speedometer2',
                    'desc' => 'Monitor batch capacity, instructor activity, learning progress, and academic readiness.',
                ],
            ],

            'sections' => [
                [
                    'title' => 'Core Setup',
                    'subtitle' => 'Master data akademik utama.',
                    'icon' => 'bi bi-layers-fill',
                    'items' => [
                        [
                            'label' => 'Programs',
                            'route' => 'programs.index',
                            'active' => ['programs.*'],
                            'icon' => 'bi bi-journal-bookmark-fill',
                            'permission' => 'programs.view',
                            'desc' => 'Program belajar, pricing, dan basic setup.',
                        ],
                        [
                            'label' => 'Batches',
                            'route' => 'batches.index',
                            'active' => ['batches.*'],
                            'icon' => 'bi bi-collection-play-fill',
                            'permission' => 'batches.view',
                            'desc' => 'Batch, kapasitas, tanggal mulai, dan status.',
                        ],
                        [
                            'label' => 'Curriculum',
                            'route' => 'curriculum.index',
                            'active' => ['curriculum.*'],
                            'icon' => 'bi bi-diagram-3-fill',
                            'permission' => 'curriculum.view',
                            'desc' => 'Stage, module, topic, dan sub topic.',
                        ],
                        [
                            'label' => 'Learning Videos',
                            'route' => 'academic.learning-videos.index',
                            'active' => ['academic.learning-videos.*'],
                            'icon' => 'bi bi-cloud-arrow-up-fill',
                            'permission' => 'learning_videos.view',
                            'desc' => 'Upload video materi ke folder learning-videos dengan drag and drop.',
                        ],
                        [
                            'label' => 'Student Enrollment',
                            'route' => 'enrollments.index',
                            'active' => ['enrollments.*', 'students.*'],
                            'icon' => 'bi bi-people-fill',
                            'permission' => 'enrollments.view',
                            'desc' => 'Data student, enrollment, dan batch student.',
                        ],
                        [
                            'label' => 'Student Progress',
                            'route' => 'academic.student-progress.index',
                            'active' => ['academic.student-progress.*'],
                            'icon' => 'bi bi-graph-up-arrow',
                            'permission' => 'student_progress.view',
                            'desc' => 'Monitoring progress belajar, materi selesai, last activity, dan student yang butuh follow up.',
                        ],
                        [
                            'label' => 'Announcements',
                            'route' => 'academic.announcements.index',
                            'active' => ['academic.announcements.*'],
                            'icon' => 'bi bi-megaphone-fill',
                            'permission' => 'academic.announcements.view',
                            'desc' => 'Pengumuman untuk student dan batch.',
                        ],
                    ],
                ],

                [
                    'title' => 'Learning Activities',
                    'subtitle' => 'Tugas, quiz, dan aktivitas belajar.',
                    'icon' => 'bi bi-journal-check',
                    'items' => [
                        [
                            'label' => 'Assignments',
                            'route' => 'assignments.index',
                            'active' => ['assignments.*'],
                            'icon' => 'bi bi-journal-check',
                            'permission' => 'assignments.view',
                            'desc' => 'Master tugas dan aktivitas mandiri.',
                        ],
                        [
                            'label' => 'Batch Assignments',
                            'route' => 'batch-assignments.index',
                            'active' => ['batch-assignments.*'],
                            'icon' => 'bi bi-clipboard-check-fill',
                            'permission' => 'batch_assignments.view',
                            'desc' => 'Assign tugas ke batch tertentu.',
                        ],
                        [
                            'label' => 'Assignment Submissions',
                            'route' => 'assignment-submissions.index',
                            'active' => ['assignment-submissions.*'],
                            'icon' => 'bi bi-inbox-fill',
                            'permission' => 'assignment_submissions.view',
                            'desc' => 'Review hasil pengumpulan tugas student.',
                        ],
                        [
                            'label' => 'Learning Quizzes',
                            'route' => 'learning-quizzes.index',
                            'active' => ['learning-quizzes.*'],
                            'icon' => 'bi bi-patch-question-fill',
                            'permission' => 'learning_quizzes.view',
                            'desc' => 'Quiz pembelajaran dan bank pertanyaan.',
                        ],
                        [
                            'label' => 'Batch Learning Quizzes',
                            'route' => 'batch-learning-quizzes.index',
                            'active' => ['batch-learning-quizzes.*'],
                            'icon' => 'bi bi-ui-checks-grid',
                            'permission' => 'batch_learning_quizzes.view',
                            'desc' => 'Assign quiz ke batch tertentu.',
                        ],
                        [
                            'label' => 'Quiz Attempts / Results',
                            'route' => 'learning-quiz-attempts.index',
                            'active' => ['learning-quiz-attempts.*'],
                            'icon' => 'bi bi-activity',
                            'permission' => 'learning_quiz_attempts.view',
                            'desc' => 'Hasil pengerjaan quiz dan score student.',
                        ],
                    ],
                ],

                [
                    'title' => 'Evaluation',
                    'subtitle' => 'Assessment, nilai, dan report.',
                    'icon' => 'bi bi-clipboard-data-fill',
                    'items' => [
                        [
                            'label' => 'Assessment Templates',
                            'route' => 'academic.assessment-templates.index',
                            'active' => ['academic.assessment-templates.*'],
                            'icon' => 'bi bi-sliders2-vertical',
                            'permission' => 'assessment_templates.view',
                            'desc' => 'Rubrik dan template penilaian.',
                        ],
                        [
                            'label' => 'Assessment Scores',
                            'route' => 'academic.assessment-scores.index',
                            'active' => ['academic.assessment-scores.*'],
                            'icon' => 'bi bi-pencil-square',
                            'permission' => 'assessment_scores.view',
                            'desc' => 'Input dan review score assessment.',
                        ],
                        [
                            'label' => 'Report Cards',
                            'route' => 'academic.report-cards.index',
                            'active' => ['academic.report-cards.*'],
                            'icon' => 'bi bi-file-earmark-text-fill',
                            'permission' => 'report_cards.view',
                            'desc' => 'Report card, grade, dan final evaluation.',
                        ],
                        [
                            'label' => 'Certificates',
                            'route' => 'academic.certificates.index',
                            'active' => ['academic.certificates.*'],
                            'icon' => 'bi bi-award-fill',
                            'permission' => 'certificates.view',
                            'desc' => 'Kelola sertifikat peserta, PDF resmi, QR code, dan verifikasi publik.',
                        ],
                    ],
                ],

                [
                    'title' => 'Instructor Ops',
                    'subtitle' => 'Pengajar, jadwal, attendance, dan tracking kelas.',
                    'icon' => 'bi bi-person-video3',
                    'items' => [
                        [
                            'label' => 'Instructors',
                            'route' => 'instructors.index',
                            'active' => ['instructors.*'],
                            'icon' => 'bi bi-person-video3',
                            'permission' => 'instructors.view',
                            'desc' => 'Master data instructor.',
                        ],
                        [
                            'label' => 'Instructor Availability',
                            'route' => 'academic.instructor-availability.index',
                            'active' => ['academic.instructor-availability.*'],
                            'icon' => 'bi bi-calendar2-week-fill',
                            'permission' => 'instructor_availability.view',
                            'desc' => 'Ketersediaan jadwal instructor.',
                        ],
                        [
                            'label' => 'Mentoring Sessions',
                            'route' => 'academic.mentoring-sessions.index',
                            'active' => ['academic.mentoring-sessions.*'],
                            'icon' => 'bi bi-chat-square-text-fill',
                            'permission' => 'mentoring_sessions.view',
                            'desc' => 'Jadwal mentoring student.',
                        ],
                        [
                            'label' => 'Instructor Schedules',
                            'route' => 'instructor-schedules.index',
                            'active' => ['instructor-schedules.*'],
                            'icon' => 'bi bi-calendar-check-fill',
                            'permission' => 'instructor_schedules.view',
                            'desc' => 'Jadwal mengajar dan replacement.',
                        ],
                        [
                            'label' => 'Student Attendance',
                            'route' => 'academic.attendances.index',
                            'active' => ['academic.attendances.*'],
                            'icon' => 'bi bi-check2-square',
                            'permission' => 'student_attendances.view',
                            'desc' => 'Catat kehadiran student online/offline per live session.',
                        ],
                        [
                            'label' => 'Instructor Tracking',
                            'route' => 'instructor-tracking.index',
                            'active' => ['instructor-tracking.*'],
                            'icon' => 'bi bi-clipboard-data-fill',
                            'permission' => 'instructor_tracking.view',
                            'desc' => 'Tracking coverage materi per sesi.',
                        ],
                    ],
                ],

                [
                    'title' => 'Webinar & Workshops',
                    'subtitle' => 'Webinar, workshop, peserta, dan materi public.',
                    'icon' => 'bi bi-easel2-fill',
                    'items' => [
                        [
                            'label' => 'Webinar Themes',
                            'route' => 'trial-themes.index',
                            'active' => ['trial-themes.*'],
                            'icon' => 'bi bi-lightbulb-fill',
                            'permission' => 'trial_themes.view',
                            'desc' => 'Tema webinar public.',
                        ],
                        [
                            'label' => 'Webinar Schedules',
                            'route' => 'trial-schedules.index',
                            'active' => ['trial-schedules.*'],
                            'icon' => 'bi bi-calendar2-week-fill',
                            'permission' => 'trial_schedules.view',
                            'desc' => 'Jadwal webinar public.',
                        ],
                        [
                            'label' => 'Webinar Participants',
                            'route' => 'trial-participants.index',
                            'active' => ['trial-participants.*'],
                            'icon' => 'bi bi-people-fill',
                            'permission' => 'trial_participants.view',
                            'desc' => 'Peserta webinar dan follow up.',
                        ],
                        [
                            'label' => 'Workshops',
                            'route' => 'academic.workshops.index',
                            'active' => ['academic.workshops.*'],
                            'icon' => 'bi bi-easel2-fill',
                            'permission' => 'workshops.view',
                            'desc' => 'Workshop public dan internal.',
                        ],
                        [
                            'label' => 'Workshop Schedules',
                            'route' => 'academic.workshop-schedules.index',
                            'active' => ['academic.workshop-schedules.*'],
                            'icon' => 'bi bi-calendar2-week-fill',
                            'permission' => 'workshop_schedules.view',
                            'desc' => 'Jadwal pelaksanaan workshop, kuota, harga per jadwal, dan status pendaftaran.',
                        ],
                        [
                            'label' => 'Workshop Participants',
                            'route' => 'academic.workshop-participants.index',
                            'active' => ['academic.workshop-participants.*'],
                            'icon' => 'bi bi-person-lines-fill',
                            'permission' => 'workshop_participants.view',
                            'desc' => 'Input peserta workshop, generate order, dan payment schedule.',
                        ],
                        [
                            'label' => 'Webinar & Workshop Materials',
                            'route' => 'public-learning-materials.index',
                            'active' => ['public-learning-materials.*'],
                            'icon' => 'bi bi-file-earmark-code-fill',
                            'permission' => 'public_learning_materials.view',
                            'desc' => 'Materi public berisi text, code snippet, image, dan link expired.',
                        ],
                    ],
                ],
            ],
        ],

        [
            'type' => 'dropdown',
            'label' => 'Sales',
            'icon' => 'bi bi-graph-up-arrow',
            'permission' => 'sales.view',
            'active' => [
                'sales.*',
                'sales-daily-reports.*',
                'sales-performance.*',
                'sales-orders.*',
                'orders.*',
            ],
            'sections' => [
                [
                    'title' => null,
                    'items' => [
                        [
                            'label' => 'Daily Report',
                            'route' => 'sales-daily-reports.index',
                            'active' => ['sales-daily-reports.*'],
                            'icon' => 'bi bi-journal-text',
                            'permission' => 'sales_daily_reports.view',
                        ],
                        [
                            'label' => 'Performance',
                            'route' => 'sales-performance.index',
                            'active' => ['sales-performance.*'],
                            'icon' => 'bi bi-bar-chart-line',
                            'permission' => 'sales_performance.view',
                        ],
                        [
                            'label' => 'Sales Orders',
                            'route' => 'orders.index',
                            'fallback_route' => 'sales-orders.index',
                            'active' => ['orders.*', 'sales-orders.*'],
                            'icon' => 'bi bi-receipt-cutoff',
                            'permission' => 'orders.view',
                        ],
                    ],
                ],
            ],
        ],

        [
            'type' => 'dropdown',
            'label' => 'Marketing',
            'icon' => 'bi bi-megaphone-fill',
            'permission' => 'marketing.view',
            'dropdown_class' => 'dropdown-menu-marketing',
            'active' => [
                'marketing.*',
                'marketing.setup.*',
                'quiz.*',
                'articles.*',
            ],
            'sections' => [
                [
                    'title' => 'Reporting',
                    'items' => [
                        [
                            'label' => 'Dashboard',
                            'route' => 'marketing.dashboard',
                            'active' => ['marketing.dashboard'],
                            'icon' => 'bi bi-speedometer2',
                            'permission' => 'marketing.dashboard.view',
                        ],
                        [
                            'label' => 'Reports',
                            'route' => 'marketing.reports.index',
                            'active' => ['marketing.reports.*'],
                            'icon' => 'bi bi-bar-chart-line',
                            'permission' => 'marketing_reports.view',
                        ],
                    ],
                ],
                [
                    'title' => 'Setup',
                    'items' => [
                        [
                            'label' => 'Campaign Setup',
                            'route' => 'marketing.setup.campaigns.index',
                            'active' => ['marketing.setup.campaigns.*'],
                            'icon' => 'bi bi-bullseye',
                            'permission' => 'campaigns.view',
                        ],
                        [
                            'label' => 'Ads Setup',
                            'route' => 'marketing.setup.ads.index',
                            'active' => ['marketing.setup.ads.*'],
                            'icon' => 'bi bi-badge-ad',
                            'permission' => 'ads.view',
                        ],
                    ],
                ],
                [
                    'title' => 'Tools',
                    'items' => [
                        [
                            'label' => 'Article Generator',
                            'route' => 'articles.index',
                            'active' => ['articles.*'],
                            'icon' => 'bi bi-file-earmark-richtext-fill',
                            'permission' => 'articles.view',
                            'desc' => 'Generate draft artikel, SEO meta, creative brief, dan caption untuk website FlexLabs.',
                            'missing_label' => 'Article Generator belum tersedia',
                        ],
                        [
                            'label' => 'Quiz Management',
                            'route' => 'quiz.index',
                            'active' => ['quiz.*'],
                            'icon' => 'bi bi-ui-checks-grid',
                            'permission' => 'quizzes.view',
                        ],
                    ],
                ],
            ],
        ],

        [
            'type' => 'dropdown',
            'label' => 'Finance',
            'icon' => 'bi bi-wallet2',
            'permission' => 'finance.view',
            'active' => [
                'finance.*',
                'sales-orders.*',
                'payments.*',
                'payment-schedules.*',
            ],
            'sections' => [
                [
                    'title' => null,
                    'items' => [
                        [
                            'label' => 'Sales Orders',
                            'route' => 'sales-orders.index',
                            'active' => ['sales-orders.*'],
                            'icon' => 'bi bi-receipt-cutoff',
                            'permission' => 'sales_orders.view',
                        ],
                        [
                            'label' => 'Payment Schedule',
                            'route' => 'payment-schedules.index',
                            'active' => ['payment-schedules.*'],
                            'icon' => 'bi bi-calendar-check',
                            'permission' => 'payment_schedules.view',
                        ],
                        [
                            'label' => 'Payments',
                            'route' => 'payments.index',
                            'active' => ['payments.*'],
                            'icon' => 'bi bi-credit-card',
                            'permission' => 'payments.view',
                        ],
                    ],
                ],
            ],
        ],

        [
            'type' => 'dropdown',
            'label' => 'Operations',
            'icon' => 'bi bi-building-gear',
            'permission' => null,
            'dropdown_class' => 'dropdown-menu-operations',
            'active' => [
                'internal-memos.*',
                'equipment.*',
                'borrowings.*',
                'atk-items.*',
                'atk-requests.*',
                'inventory.atk-items.*',
                'inventory.atk-requests.*',
                'operation.meeting-minutes.*',
                'operation.meeting-minute-action-items.*',
            ],
            'sections' => [
                [
                    'title' => 'Meeting & Documents',
                    'items' => [
                        [
                            'label' => 'Internal Memo',
                            'route' => 'internal-memos.index',
                            'active' => ['internal-memos.*'],
                            'icon' => 'bi bi-file-earmark-text-fill',
                            'permission' => 'internal_memos.view',
                            'missing_label' => 'Internal Memo belum tersedia',
                        ],
                        [
                            'label' => 'Meeting Minutes / MOM',
                            'route' => 'operation.meeting-minutes.index',
                            'active' => ['operation.meeting-minutes.*', 'operation.meeting-minute-action-items.*'],
                            'icon' => 'bi bi-journal-text',
                            'permission' => 'meeting_minutes.view',
                            'missing_label' => 'Meeting Minutes belum tersedia',
                        ],
                    ],
                ],
                [
                    'title' => 'Inventory',
                    'items' => [
                        [
                            'label' => 'Equipment',
                            'route' => 'equipment.index',
                            'active' => ['equipment.*'],
                            'icon' => 'bi bi-pc-display-horizontal',
                            'permission' => 'equipment.view',
                            'missing_label' => 'Equipment belum tersedia',
                        ],
                        [
                            'label' => 'Borrow Equipment',
                            'route' => 'borrowings.index',
                            'active' => ['borrowings.*'],
                            'icon' => 'bi bi-box-arrow-up-right',
                            'permission' => 'equipment_borrowings.view',
                        ],
                        [
                            'label' => 'Master ATK',
                            'route' => 'inventory.atk-items.index',
                            'fallback_route' => 'atk-items.index',
                            'active' => ['inventory.atk-items.*', 'atk-items.*'],
                            'icon' => 'bi bi-box-seam',
                            'permission' => 'atk_items.view',
                            'missing_label' => 'Master ATK belum tersedia',
                        ],
                    ],
                ],
                [
                    'title' => 'Requests',
                    'items' => [
                        [
                            'label' => 'ATK Request',
                            'route' => 'inventory.atk-requests.index',
                            'fallback_route' => 'atk-requests.index',
                            'active' => ['inventory.atk-requests.*', 'atk-requests.*'],
                            'icon' => 'bi bi-pencil-square',
                            'permission' => 'atk_requests.view',
                            'missing_label' => 'ATK Request belum tersedia',
                        ],
                    ],
                ],
            ],
        ],

        [
            'type' => 'dropdown',
            'label' => 'Settings',
            'icon' => 'bi bi-gear-fill',
            'permission' => 'users.view',
            'dropdown_class' => 'dropdown-menu-settings',
            'active' => [
                'settings.*',
                'settings.users.*',
            ],
            'sections' => [
                [
                    'title' => 'System Access',
                    'items' => [
                        [
                            'label' => 'User Management',
                            'route' => 'settings.users.index',
                            'active' => ['settings.users.*'],
                            'icon' => 'bi bi-people-fill',
                            'permission' => 'users.view',
                            'missing_label' => 'User Management belum tersedia',
                        ],
                    ],
                ],
            ],
        ],

    ],

];