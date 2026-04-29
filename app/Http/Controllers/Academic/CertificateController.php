<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\ReportCard;
use App\Services\Assessment\CertificateService;
use Illuminate\Http\Request;
use RuntimeException;

class CertificateController extends Controller
{
    public function __construct(
        protected CertificateService $certificateService
    ) {
    }

    public function index(Request $request)
    {
        $query = Certificate::query()
            ->with(['student', 'batch', 'program', 'reportCard', 'issuer'])
            ->when($request->filled('program_id'), function ($q) use ($request) {
                $q->where('program_id', $request->program_id);
            })
            ->when($request->filled('batch_id'), function ($q) use ($request) {
                $q->where('batch_id', $request->batch_id);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('type'), function ($q) use ($request) {
                $q->where('type', $request->type);
            })
            ->latest();

        $certificates = $query->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $certificates,
            ]);
        }

        return view('academic.certificates.index', compact('certificates'));
    }

    public function show(Request $request, Certificate $certificate)
    {
        $certificate->load([
            'student',
            'batch',
            'program',
            'reportCard',
            'issuer',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $certificate,
            ]);
        }

        return view('academic.certificates.show', compact('certificate'));
    }

    public function issue(Request $request)
    {
        $validated = $request->validate([
            'report_card_id' => ['required', 'exists:report_cards,id'],
            'type' => ['nullable', 'in:completion,achievement,excellence,participation'],
            'title' => ['nullable', 'string', 'max:255'],
            'issued_date' => ['nullable', 'date'],
            'completed_date' => ['nullable', 'date'],
        ]);

        $reportCard = ReportCard::query()->findOrFail($validated['report_card_id']);

        try {
            $certificate = $this->certificateService->issue(
                reportCard: $reportCard,
                issuedBy: $request->user()?->id,
                extraData: [
                    'type' => $validated['type'] ?? null,
                    'title' => $validated['title'] ?? null,
                    'issued_date' => $validated['issued_date'] ?? null,
                    'completed_date' => $validated['completed_date'] ?? null,
                ]
            );
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return back()->with('error', $exception->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Certificate issued successfully.',
                'data' => $certificate,
            ]);
        }

        return redirect()
            ->route('academic.certificates.show', $certificate)
            ->with('success', 'Certificate issued successfully.');
    }

    public function reissue(Request $request, Certificate $certificate)
    {
        $validated = $request->validate([
            'type' => ['nullable', 'in:completion,achievement,excellence,participation'],
            'title' => ['nullable', 'string', 'max:255'],
            'issued_date' => ['nullable', 'date'],
            'completed_date' => ['nullable', 'date'],
        ]);

        try {
            $certificate = $this->certificateService->reissue(
                certificate: $certificate,
                issuedBy: $request->user()?->id,
                extraData: [
                    'type' => $validated['type'] ?? null,
                    'title' => $validated['title'] ?? null,
                    'issued_date' => $validated['issued_date'] ?? null,
                    'completed_date' => $validated['completed_date'] ?? null,
                ]
            );
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return back()->with('error', $exception->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Certificate reissued successfully.',
                'data' => $certificate,
            ]);
        }

        return redirect()
            ->route('academic.certificates.show', $certificate)
            ->with('success', 'Certificate reissued successfully.');
    }

    public function revoke(Request $request, Certificate $certificate)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $certificate = $this->certificateService->revoke(
            certificate: $certificate,
            reason: $validated['reason'],
            revokedBy: $request->user()?->id
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Certificate revoked successfully.',
                'data' => $certificate,
            ]);
        }

        return back()->with('success', 'Certificate revoked successfully.');
    }
}