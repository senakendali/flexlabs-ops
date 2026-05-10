<?php

namespace App\Http\Controllers\Api\Lms;

use App\Http\Controllers\Controller;
use App\Models\CommunityChannel;
use App\Models\CommunityComment;
use App\Models\CommunityGroup;
use App\Models\CommunityPost;
use App\Models\CommunityPostRead;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LmsCommunityController extends Controller
{
    /**
     * Resolve authenticated student.
     *
     * Catatan:
     * - Kalau auth user punya relasi student(), ini akan dipakai.
     * - Kalau guard langsung login sebagai Student, fallback tetap aman.
     */
    private function currentStudent(Request $request): ?Student
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        if ($user instanceof Student) {
            return $user;
        }

        if (method_exists($user, 'student')) {
            return $user->student;
        }

        return Student::query()
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Get active community group for current student.
     */
    private function currentGroup(Request $request): ?CommunityGroup
    {
        $student = $this->currentStudent($request);

        if (!$student) {
            return null;
        }

        /**
         * Prioritas:
         * 1. Ambil dari batch_id student kalau ada.
         * 2. Ambil dari enrollment aktif kalau struktur project lu punya relasi.
         * 3. Fallback ke community default pertama.
         */

        if (!empty($student->batch_id)) {
            $group = CommunityGroup::query()
                ->where('batch_id', $student->batch_id)
                ->where('is_active', true)
                ->first();

            if ($group) {
                return $group;
            }
        }

        if (method_exists($student, 'enrollments')) {
            $enrollment = $student->enrollments()
                ->latest()
                ->first();

            if ($enrollment && !empty($enrollment->batch_id)) {
                $group = CommunityGroup::query()
                    ->where('batch_id', $enrollment->batch_id)
                    ->where('is_active', true)
                    ->first();

                if ($group) {
                    return $group;
                }
            }
        }

        return CommunityGroup::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->latest()
            ->first();
    }

    public function home(Request $request): JsonResponse
    {
        $student = $this->currentStudent($request);
        $group = $this->currentGroup($request);

        if (!$student) {
            return response()->json([
                'message' => 'Student profile not found.',
            ], 404);
        }

        if (!$group) {
            return response()->json([
                'message' => 'Community group not found.',
            ], 404);
        }

        $channels = CommunityChannel::query()
            ->where('community_group_id', $group->id)
            ->where('is_active', true)
            ->withCount([
                'posts as posts_count' => function ($query) {
                    $query->where('is_active', true);
                },
            ])
            ->orderByDesc('is_pinned')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $latestAnnouncements = CommunityPost::query()
            ->with(['channel:id,name,type'])
            ->whereHas('channel', function ($query) use ($group) {
                $query->where('community_group_id', $group->id)
                    ->where('type', 'announcement');
            })
            ->where('is_active', true)
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->latest()
            ->limit(5)
            ->get();

        $latestDiscussions = CommunityPost::query()
            ->with(['channel:id,name,type', 'student:id,full_name,email'])
            ->whereHas('channel', function ($query) use ($group) {
                $query->where('community_group_id', $group->id)
                    ->whereIn('type', [
                        'discussion',
                        'coding_help',
                        'project',
                        'career',
                        'general',
                    ]);
            })
            ->where('is_active', true)
            ->withCount([
                'comments as comments_count' => function ($query) {
                    $query->where('is_active', true);
                },
            ])
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'message' => 'Community home loaded successfully.',
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->full_name ?? $student->name ?? 'Pioneer',
                    'email' => $student->email ?? null,
                ],
                'group' => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'slug' => $group->slug,
                    'description' => $group->description,
                ],
                'channels' => $channels,
                'latest_announcements' => $latestAnnouncements,
                'latest_discussions' => $latestDiscussions,
            ],
        ]);
    }

    public function channels(Request $request): JsonResponse
    {
        $group = $this->currentGroup($request);

        if (!$group) {
            return response()->json([
                'message' => 'Community group not found.',
            ], 404);
        }

        $channels = CommunityChannel::query()
            ->where('community_group_id', $group->id)
            ->where('is_active', true)
            ->withCount([
                'posts as posts_count' => function ($query) {
                    $query->where('is_active', true);
                },
            ])
            ->orderByDesc('is_pinned')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'message' => 'Community channels loaded successfully.',
            'data' => [
                'group' => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'slug' => $group->slug,
                ],
                'channels' => $channels,
            ],
        ]);
    }

    public function posts(Request $request, CommunityChannel $channel): JsonResponse
    {
        $group = $this->currentGroup($request);

        if (!$group || $channel->community_group_id !== $group->id) {
            return response()->json([
                'message' => 'Channel not found for this student.',
            ], 404);
        }

        $posts = CommunityPost::query()
            ->with([
                'student:id,full_name,email',
                'channel:id,name,type,is_readonly',
            ])
            ->withCount([
                'comments as comments_count' => function ($query) {
                    $query->where('is_active', true);
                },
            ])
            ->where('community_channel_id', $channel->id)
            ->where('is_active', true)
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'message' => 'Channel posts loaded successfully.',
            'data' => [
                'channel' => $channel,
                'posts' => $posts,
            ],
        ]);
    }

    public function showPost(Request $request, CommunityPost $post): JsonResponse
    {
        $student = $this->currentStudent($request);
        $group = $this->currentGroup($request);

        $post->load([
            'channel:id,community_group_id,name,type,is_readonly',
            'student:id,full_name,email',
            'activeComments.student:id,full_name,email',
        ]);

        if (!$group || $post->channel->community_group_id !== $group->id) {
            return response()->json([
                'message' => 'Post not found for this student.',
            ], 404);
        }

        if ($student) {
            CommunityPostRead::query()->updateOrCreate(
                [
                    'community_post_id' => $post->id,
                    'student_id' => $student->id,
                ],
                [
                    'read_at' => now(),
                ]
            );
        }

        return response()->json([
            'message' => 'Post detail loaded successfully.',
            'data' => [
                'post' => $post,
            ],
        ]);
    }

    public function storePost(Request $request, CommunityChannel $channel): JsonResponse
    {
        $student = $this->currentStudent($request);
        $group = $this->currentGroup($request);

        if (!$student) {
            return response()->json([
                'message' => 'Student profile not found.',
            ], 404);
        }

        if (!$group || $channel->community_group_id !== $group->id) {
            return response()->json([
                'message' => 'Channel not found for this student.',
            ], 404);
        }

        if ($channel->is_readonly) {
            return response()->json([
                'message' => 'This channel is readonly.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string'],
            'post_type' => ['nullable', 'in:question,discussion'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $post = CommunityPost::query()->create([
            'community_channel_id' => $channel->id,
            'author_type' => 'student',
            'author_id' => $student->id,
            'student_id' => $student->id,
            'title' => $request->title,
            'body' => $request->body,
            'post_type' => $request->post_type ?? 'discussion',
            'status' => 'open',
            'is_pinned' => false,
            'is_locked' => false,
            'is_active' => true,
            'published_at' => now(),
        ]);

        $post->load([
            'channel:id,name,type',
            'student:id,full_name,email',
        ]);

        return response()->json([
            'message' => 'Post created successfully.',
            'data' => [
                'post' => $post,
            ],
        ], 201);
    }

    public function storeComment(Request $request, CommunityPost $post): JsonResponse
    {
        $student = $this->currentStudent($request);
        $group = $this->currentGroup($request);

        if (!$student) {
            return response()->json([
                'message' => 'Student profile not found.',
            ], 404);
        }

        $post->load('channel');

        if (!$group || $post->channel->community_group_id !== $group->id) {
            return response()->json([
                'message' => 'Post not found for this student.',
            ], 404);
        }

        if ($post->is_locked || !$post->is_active) {
            return response()->json([
                'message' => 'This post is locked or inactive.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'body' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $comment = CommunityComment::query()->create([
            'community_post_id' => $post->id,
            'author_type' => 'student',
            'author_id' => $student->id,
            'student_id' => $student->id,
            'body' => $request->body,
            'is_solution' => false,
            'is_active' => true,
        ]);

        if ($post->status === 'open') {
            $post->update([
                'status' => 'answered',
            ]);
        }

        $comment->load('student:id,full_name,email');

        return response()->json([
            'message' => 'Comment created successfully.',
            'data' => [
                'comment' => $comment,
            ],
        ], 201);
    }

    public function markAsSolved(Request $request, CommunityPost $post): JsonResponse
    {
        $student = $this->currentStudent($request);
        $group = $this->currentGroup($request);

        if (!$student) {
            return response()->json([
                'message' => 'Student profile not found.',
            ], 404);
        }

        $post->load('channel');

        if (!$group || $post->channel->community_group_id !== $group->id) {
            return response()->json([
                'message' => 'Post not found for this student.',
            ], 404);
        }

        if ((int) $post->student_id !== (int) $student->id) {
            return response()->json([
                'message' => 'Only the post owner can mark this post as solved.',
            ], 403);
        }

        DB::transaction(function () use ($request, $post) {
            if ($request->filled('comment_id')) {
                CommunityComment::query()
                    ->where('community_post_id', $post->id)
                    ->update([
                        'is_solution' => false,
                    ]);

                CommunityComment::query()
                    ->where('community_post_id', $post->id)
                    ->where('id', $request->comment_id)
                    ->update([
                        'is_solution' => true,
                    ]);
            }

            $post->update([
                'status' => 'solved',
                'solved_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Post marked as solved successfully.',
            'data' => [
                'post' => $post->fresh([
                    'channel:id,name,type',
                    'student:id,full_name,email',
                    'activeComments.student:id,full_name,email',
                ]),
            ],
        ]);
    }
}