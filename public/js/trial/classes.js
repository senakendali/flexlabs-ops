document.addEventListener('DOMContentLoaded', function () {
    const config = window.trialClassConfig;

    const classModalEl = document.getElementById('classModal');
    const deleteModalEl = document.getElementById('deleteModal');

    const classModal = new bootstrap.Modal(classModalEl);
    const deleteModal = new bootstrap.Modal(deleteModalEl);

    const form = document.getElementById('classForm');
    const alertContainer = document.getElementById('alertContainer');
    const tbody = document.querySelector('#trialClassTable tbody');

    const btnAddClass = document.getElementById('btnAddClass');
    const btnSaveClass = document.getElementById('btnSaveClass');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    const modalTitle = document.getElementById('classModalTitle');
    const classIdInput = document.getElementById('classId');
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    const statusInput = document.getElementById('status');
    const descriptionInput = document.getElementById('description');

    const deleteClassName = document.getElementById('deleteClassName');

    let deleteId = null;

    function showAlert(message, type = 'success') {
        alertContainer.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }

    function clearValidation() {
        form.querySelectorAll('.form-control, .form-select').forEach((el) => {
            el.classList.remove('is-invalid');
        });

        form.querySelectorAll('.invalid-feedback').forEach((el) => {
            el.textContent = '';
        });
    }

    function setValidationErrors(errors) {
        Object.keys(errors).forEach((field) => {
            const input = document.getElementById(field);
            const errorEl = document.getElementById(`error-${field}`);

            if (input) input.classList.add('is-invalid');
            if (errorEl) errorEl.textContent = errors[field][0];
        });
    }

    function resetForm() {
        form.reset();
        clearValidation();
        classIdInput.value = '';
        statusInput.value = 'active';
    }

    function mapBooleanToStatus(isActive) {
        return isActive ? 'active' : 'inactive';
    }

    function getBadge(isActive) {
        return isActive
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-secondary">Inactive</span>';
    }

    function removeEmptyRow() {
        const emptyRow = document.getElementById('emptyRow');
        if (emptyRow) emptyRow.remove();
    }

    function renderRow(item, index = null) {
        return `
            <tr id="row-${item.id}">
                <td>${index ?? '-'}</td>
                <td class="td-name">${item.name}</td>
                <td class="td-slug">${item.slug ?? '-'}</td>
                <td class="td-status">${getBadge(item.is_active)}</td>
                <td class="td-description">${item.description ?? '-'}</td>
                <td class="text-center">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-warning btn-edit"
                        data-id="${item.id}"
                    >
                        Edit
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger btn-delete"
                        data-id="${item.id}"
                        data-name="${item.name}"
                    >
                        Delete
                    </button>
                </td>
            </tr>
        `;
    }

    function refreshRowNumbers() {
        const rows = tbody.querySelectorAll('tr');
        let number = 1;

        rows.forEach((row) => {
            if (row.id !== 'emptyRow') {
                const firstTd = row.querySelector('td');
                if (firstTd) {
                    firstTd.textContent = number++;
                }
            }
        });

        if (number === 1) {
            tbody.innerHTML = `
                <tr id="emptyRow">
                    <td colspan="6" class="text-center text-muted py-4">
                        Belum ada data trial class.
                    </td>
                </tr>
            `;
        }
    }

    btnAddClass.addEventListener('click', function () {
        resetForm();
        modalTitle.textContent = 'Add Trial Class';
        classModal.show();
    });

    document.addEventListener('click', async function (e) {
        const editBtn = e.target.closest('.btn-edit');
        const deleteBtn = e.target.closest('.btn-delete');

        if (editBtn) {
            const id = editBtn.dataset.id;

            resetForm();
            modalTitle.textContent = 'Edit Trial Class';

            try {
                const response = await fetch(`${config.showBaseUrl}/${id}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Gagal mengambil data.');
                }

                const data = result.data;

                classIdInput.value = data.id;
                nameInput.value = data.name ?? '';
                slugInput.value = data.slug ?? '';
                statusInput.value = mapBooleanToStatus(data.is_active);
                descriptionInput.value = data.description ?? '';

                classModal.show();
            } catch (error) {
                showAlert(error.message, 'danger');
            }
        }

        if (deleteBtn) {
            deleteId = deleteBtn.dataset.id;
            deleteClassName.textContent = deleteBtn.dataset.name;
            deleteModal.show();
        }
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidation();

        const id = classIdInput.value;
        const isEdit = !!id;
        const url = isEdit ? `${config.showBaseUrl}/${id}` : config.storeUrl;

        const payload = {
            name: nameInput.value,
            slug: slugInput.value,
            status: statusInput.value,
            description: descriptionInput.value,
        };

        btnSaveClass.disabled = true;
        btnSaveClass.textContent = 'Saving...';

        try {
            const response = await fetch(url, {
                method: isEdit ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json();

            if (response.status === 422) {
                setValidationErrors(result.errors || {});
                return;
            }

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Proses gagal.');
            }

            const item = result.data;

            if (isEdit) {
                const row = document.getElementById(`row-${item.id}`);
                if (row) {
                    row.querySelector('.td-name').textContent = item.name;
                    row.querySelector('.td-slug').textContent = item.slug ?? '-';
                    row.querySelector('.td-status').innerHTML = getBadge(item.is_active);
                    row.querySelector('.td-description').textContent = item.description ?? '-';

                    const rowDeleteBtn = row.querySelector('.btn-delete');
                    if (rowDeleteBtn) {
                        rowDeleteBtn.dataset.name = item.name;
                    }
                }
            } else {
                removeEmptyRow();
                tbody.insertAdjacentHTML('beforeend', renderRow(item));
                refreshRowNumbers();
            }

            classModal.hide();
            resetForm();
            showAlert(result.message, 'success');
        } catch (error) {
            showAlert(error.message, 'danger');
        } finally {
            btnSaveClass.disabled = false;
            btnSaveClass.textContent = 'Save';
        }
    });

    confirmDeleteBtn.addEventListener('click', async function () {
        if (!deleteId) return;

        confirmDeleteBtn.disabled = true;
        confirmDeleteBtn.textContent = 'Deleting...';

        try {
            const response = await fetch(`${config.showBaseUrl}/${deleteId}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Gagal menghapus data.');
            }

            const row = document.getElementById(`row-${deleteId}`);
            if (row) row.remove();

            refreshRowNumbers();
            deleteModal.hide();
            showAlert(result.message, 'success');
            deleteId = null;
        } catch (error) {
            showAlert(error.message, 'danger');
        } finally {
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.textContent = 'Delete';
        }
    });
});