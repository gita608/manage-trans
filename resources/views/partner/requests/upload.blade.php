@extends('layouts.partner')

@section('title', 'Upload Schedule - Partner Portal')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Upload Schedule</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('partner.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('partner.requests.new') }}">New Request</a></li>
                    <li class="breadcrumb-item active">Upload Schedule</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('partner.requests.image.store') }}" method="POST" enctype="multipart/form-data" id="imageUploadForm">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label d-block">Schedule Image</label>
                        <div id="dropZone" class="border border-2 border-dashed rounded p-4 text-center bg-light-subtle">
                            <i class="ri-upload-cloud-2-line display-4 text-muted"></i>
                            <p class="mb-2 mt-3 fw-medium">Drag &amp; drop your schedule image here</p>
                            <p class="text-muted small mb-3">JPEG, JPG, or PNG up to 10 MB</p>
                            <button type="button" class="btn btn-outline-primary" id="chooseImageBtn">Choose Image</button>
                            <input type="file" name="image" id="imageInput" class="d-none" accept="image/jpeg,image/jpg,image/png,.jpg,.jpeg,.png">
                        </div>
                        @error('image')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="previewContainer" class="d-none mb-3">
                        <p class="text-muted mb-1">Selected file</p>
                        <p class="fw-medium mb-2" id="fileName"></p>
                        <img src="" alt="Selected schedule preview" id="imagePreview" class="img-fluid rounded border" style="max-height: 320px;">
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <a href="{{ route('partner.requests.new') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                            <i class="ri-send-plane-fill align-middle me-1"></i> Submit Request
                        </button>
                    </div>
                </form>
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
    const form = document.getElementById('imageUploadForm');

    chooseImageBtn.addEventListener('click', function () {
        imageInput.click();
    });

    dropZone.addEventListener('dragover', function (event) {
        event.preventDefault();
        dropZone.classList.add('border-primary');
    });

    dropZone.addEventListener('dragleave', function () {
        dropZone.classList.remove('border-primary');
    });

    dropZone.addEventListener('drop', function (event) {
        event.preventDefault();
        dropZone.classList.remove('border-primary');

        if (event.dataTransfer.files.length > 0) {
            imageInput.files = event.dataTransfer.files;
            showPreview(event.dataTransfer.files[0]);
        }
    });

    imageInput.addEventListener('change', function () {
        if (imageInput.files.length > 0) {
            showPreview(imageInput.files[0]);
        }
    });

    form.addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Submitting...';
    });

    function showPreview(file) {
        fileName.textContent = file.name;
        previewContainer.classList.remove('d-none');
        submitBtn.disabled = false;

        const reader = new FileReader();
        reader.onload = function (event) {
            imagePreview.src = event.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endpush
