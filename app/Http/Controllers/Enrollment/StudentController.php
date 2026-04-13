<?php

namespace App\Http\Controllers\Enrollment;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $students = Student::latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('enrollment.students.index', compact('students'));
    }

    public function show(Student $student): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'email' => $student->email,
                'phone' => $student->phone,
                'city' => $student->city,
                'current_status' => $student->current_status,
                'goal' => $student->goal,
                'source' => $student->source,
                'status' => $student->status,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:students,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:255'],
            'current_status' => ['nullable', 'string', 'max:255'],
            'goal' => ['nullable', 'string'],
            'source' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['lead', 'trial', 'active', 'inactive'])],
        ]);

        $student = Student::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Student created successfully.',
            'data' => $student,
        ], 201);
    }

    public function update(Request $request, Student $student): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('students', 'email')->ignore($student->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:255'],
            'current_status' => ['nullable', 'string', 'max:255'],
            'goal' => ['nullable', 'string'],
            'source' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['lead', 'trial', 'active', 'inactive'])],
        ]);

        $student->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Student updated successfully.',
            'data' => $student,
        ]);
    }

    public function destroy(Student $student): JsonResponse
    {
        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully.',
        ]);
    }
}