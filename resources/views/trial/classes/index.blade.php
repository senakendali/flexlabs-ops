@extends('layouts.app-dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <div>
                <h4 class="mb-1">Trial Classes</h4>
                <p class="text-muted mb-0">Manage trial class master data.</p>
            </div>

            <button class="btn btn-primary" id="btnAddClass">
                <i class="bi bi-plus-lg me-1"></i> Add Class
            </button>
        </div>

        <div class="card-body">
            <div id="alertContainer"></div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="trialClassTable">
                    <thead class="table-light">
                        <tr>
                            <th width="60">#</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Description</th>
                            <th width="160" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classes as $index => $class)
                            <tr id="row-{{ $class->id }}">
                                <td>{{ $index + 1 }}</td>
                                <td class="td-name">{{ $class->name }}</td>
                                <td class="td-slug">{{ $class->slug }}</td>
                                <td class="td-status">
                                    @if($class->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="td-description">{{ $class->description ?: '-' }}</td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-warning btn-edit"
                                        data-id="{{ $class->id }}"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger btn-delete"
                                        data-id="{{ $class->id }}"
                                        data-name="{{ $class->name }}"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyRow">
                                <td colspan="6" class="text-center text-muted py-4">
                                    Belum ada data trial class.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="classModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="classForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="classModalTitle">Add Trial Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="classId">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Class Name</label>
                            <input type="text" class="form-control" id="name" name="name">
                            <div class="invalid-feedback" id="error-name"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Slug</label>
                            <input type="text" class="form-control" id="slug" name="slug">
                            <small class="text-muted">Optional. Kalau kosong, otomatis generate.</small>
                            <div class="invalid-feedback" id="error-slug"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div class="invalid-feedback" id="error-status"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                            <div class="invalid-feedback" id="error-description"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveClass">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Delete Confirmation -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Delete Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Yakin mau hapus trial class
                    <strong id="deleteClassName"></strong>?
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.trialClassConfig = {
        indexUrl: @json(route('trial-classes.index')),
        storeUrl: @json(route('trial-classes.store')),
        showBaseUrl: @json(url('/trial/classes')),
        csrfToken: @json(csrf_token()),
    };
</script>
<script src="{{ asset('js/trial/classes.js') }}"></script>
@endpush