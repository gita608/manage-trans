@extends('layouts.partner')

@section('title', 'Upload Schedule - Partner Portal')

@section('content')
@include('partner.partials.page-header', [
    'title' => 'Upload Schedule',
    'subtitle' => 'Upload a clear photo or scan of your crew transportation schedule.',
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('partner.dashboard')],
        ['label' => 'New Request', 'url' => route('partner.requests.new')],
        ['label' => 'Upload Image']
    ]
])

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card partner-page-card">
            <div class="card-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="partner-card-header-icon"><i class="ri-image-add-line fs-5" aria-hidden="true"></i></span>
                    <div>
                        <h5 class="card-title mb-0">Upload Your Schedule</h5>
                        <p class="text-muted mb-0 mt-1 small">
                            Manage Trans will review and process the transportation details for you.
                        </p>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('partner.requests.image.store') }}" method="POST" enctype="multipart/form-data" id="imageUploadForm" novalidate>
                    @csrf

                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-3">Select Schedule Image</label>
                        <div id="dropZone"
                             class="border border-2 border-dashed rounded p-5 text-center bg-light-subtle"
                             role="button"
                             tabindex="0"
                             aria-label="Upload area: click to browse or drag and drop your schedule image">
                            <i class="ri-upload-cloud-2-line display-3 text-muted mb-3" aria-hidden="true"></i>
                            <h6 class="mb-2">Drag & drop your schedule here</h6>
                            <p class="text-muted mb-3">or click to browse your files</p>
                            <div class="mb-3">
                                <button type="button" class="btn btn-outline-primary btn-touch" id="chooseImageBtn">
                                    <i class="ri-folder-open-line me-2"></i> Choose Image
                                </button>
                            </div>
                            <p class="text-muted small mb-0">
                                <i class="ri-information-line me-1"></i>
                                Accepted: JPEG, JPG, PNG • Maximum size: 10 MB
                            </p>
                            <input type="file"
                                   name="image"
                                   id="imageInput"
                                   class="d-none"
                                   accept="image/jpeg,image/jpg,image/png,.jpg,.jpeg,.png"
                                   aria-label="Choose schedule image file">
                        </div>
                        @error('image')
                            <div class="text-danger small mt-2" role="alert">
                                <i class="ri-error-warning-line me-1"></i>{{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div id="previewContainer" class="d-none mb-4">
                        <div class="alert alert-success d-flex align-items-center" role="status">
                            <i class="ri-checkbox-circle-line fs-4 me-2" aria-hidden="true"></i>
                            <div class="flex-grow-1">
                                <strong>Image selected:</strong>
                                <span id="fileName" class="ms-1"></span>
                            </div>
                            <button type="button" class="btn btn-sm btn-soft-danger ms-2" id="removeImageBtn" aria-label="Remove selected image">
                                <i class="ri-close-line"></i> Remove
                            </button>
                        </div>
                        <div class="border rounded p-3 bg-light-subtle">
                            <p class="text-muted small mb-2">Preview:</p>
                            <img src=""
                                 alt="Preview of selected schedule"
                                 id="imagePreview"
                                 class="img-fluid rounded border bg-white"
                                 style="max-height: 400px; max-width: 100%; object-fit: contain;">
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                        <a href="{{ route('partner.requests.new') }}" class="btn btn-light btn-touch">
                            <i class="ri-close-line align-middle me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary btn-touch" id="submitBtn" disabled aria-disabled="true">
                            <i class="ri-send-plane-fill align-middle me-1"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Helpful tips -->
        <div class="card partner-help-card mt-3">
            <div class="card-body">
                <h6 class="d-flex align-items-center mb-3">
                    <i class="ri-lightbulb-line text-info fs-5 me-2" aria-hidden="true"></i>
                    Tips for Best Results
                </h6>
                <ul class="mb-0 text-muted small">
                    <li class="mb-1">Ensure the schedule is clear and readable</li>
                    <li class="mb-1">Include all crew names, dates, and locations</li>
                    <li class="mb-1">Avoid glare or shadows on the image</li>
                    <li>Take the photo in good lighting conditions</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropZone = document.getElementById('dropZone');
    const imageInput = document.getElementById('imageInput');
    const chooseImageBtn = document.getElementById('chooseImageBtn');
    const previewContainer = document.getElementById('previewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const fileName = document.getElementById('fileName');
    const submitBtn = document.getElementById('submitBtn');
    const removeImageBtn = document.getElementById('removeImageBtn');
    const form = document.getElementById('imageUploadForm');

    chooseImageBtn.addEventListener('click', function (e) {
        e.preventDefault();
        imageInput.click();
    });

    // Keyboard accessibility for drop zone
    dropZone.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            imageInput.click();
        }
    });

    dropZone.addEventListener('dragover', function (event) {
        event.preventDefault();
        dropZone.classList.add('border-primary', 'bg-primary-subtle');
    });

    dropZone.addEventListener('dragleave', function () {
        dropZone.classList.remove('border-primary', 'bg-primary-subtle');
    });

    dropZone.addEventListener('drop', function (event) {
        event.preventDefault();
        dropZone.classList.remove('border-primary', 'bg-primary-subtle');

        if (event.dataTransfer.files.length > 0) {
            const file = event.dataTransfer.files[0];

            // Validate file type
            if (!file.type.match('image/(jpeg|jpg|png)')) {
                alert('Please select a JPEG, JPG, or PNG image file.');
                return;
            }

            // Validate file size (10MB)
            if (file.size > 10 * 1024 * 1024) {
                alert('File size must not exceed 10 MB.');
                return;
            }

            imageInput.files = event.dataTransfer.files;
            showPreview(file);
        }
    });

    imageInput.addEventListener('change', function () {
        if (imageInput.files.length > 0) {
            showPreview(imageInput.files[0]);
        }
    });

    removeImageBtn.addEventListener('click', function() {
        imageInput.value = '';
        previewContainer.classList.add('d-none');
        submitBtn.disabled = true;
        submitBtn.setAttribute('aria-disabled', 'true');
    });

    // Prevent double submission
    form.addEventListener('submit', function (e) {
        if (submitBtn.disabled) {
            e.preventDefault();
            return false;
        }

        submitBtn.disabled = true;
        submitBtn.setAttribute('aria-disabled', 'true');
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Submitting...';
    });

    function showPreview(file) {
        fileName.textContent = file.name;
        previewContainer.classList.remove('d-none');
        submitBtn.disabled = false;
        submitBtn.removeAttribute('aria-disabled');

        const reader = new FileReader();
        reader.onload = function (event) {
            imagePreview.src = event.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endpush