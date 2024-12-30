@extends('layouts.app')

@section('content')

<head>
    <link href="{{ asset('css/salary-sheets.css') }}" rel="stylesheet">
</head>
<div class="container-fluid py-4">
    <div class="mb-8">
        <h1 class="text-2xl font-bold mb-4">Salary Sheets Upload</h1>

        <!-- Drag and Drop Zone -->
        <div
            id="dropzone"
            class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition-colors duration-200">
            <div class="space-y-4">
                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400"></i>
                <p class="text-gray-600">Drag and drop salary sheet files here</p>
                <p class="text-sm text-gray-500">or</p>
                <button
                    type="button"
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors duration-200"
                    onclick="document.getElementById('fileInput').click()">
                    Select Files
                </button>
                <input
                    type="file"
                    id="fileInput"
                    multiple
                    class="hidden"
                    accept=".pdf,.xlsx,.xls,.csv">
            </div>
        </div>

        <!-- Upload Progress -->
        <div id="uploadProgress" class="mt-4 hidden">
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
            </div>
            <p class="text-sm text-gray-600 mt-2">Uploading files... <span id="progressText">0%</span></p>
        </div>
    </div>

    <!-- Salary Sheets Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Upload Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($salarySheets as $sheet)

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $sheet->employee_id }}</td>

                    <td class="px-6 py-4 whitespace-nowrap">{{ $sheet->month }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $sheet->original_filename }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $sheet->created_at->format('Y-m-d H:i') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="{{ Storage::url($sheet->file_path) }}"
                            class="text-blue-600 hover:text-blue-900"
                            target="_blank">
                            View
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('fileInput');
        const uploadProgress = document.getElementById('uploadProgress');
        const progressBar = uploadProgress.querySelector('.bg-blue-600');
        const progressText = document.getElementById('progressText');

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        // Highlight drop zone when dragging over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, unhighlight, false);
        });

        // Handle dropped files
        dropzone.addEventListener('drop', handleDrop, false);
        fileInput.addEventListener('change', handleFiles, false);

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function highlight(e) {
            dropzone.classList.add('border-blue-500');
        }

        function unhighlight(e) {
            dropzone.classList.remove('border-blue-500');
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles({
                target: {
                    files: files
                }
            });
        }

        function handleFiles(e) {
            const files = [...e.target.files];
            uploadFiles(files);
        }

        function uploadFiles(files) {
            const formData = new FormData();
            files.forEach(file => {
                formData.append('files[]', file);
            });

            uploadProgress.classList.remove('hidden');

            axios.post('/salary-sheets/upload', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    },
                    onUploadProgress: (progressEvent) => {
                        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        progressBar.style.width = percentCompleted + '%';
                        progressText.textContent = percentCompleted + '%';
                    }
                })
                .then(response => {
                    if (response.data.success) {
                        showAlert('Files uploaded successfully', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                })
                .catch(error => {
                    showAlert(error.response.data.message || 'Upload failed', 'error');
                })
                .finally(() => {
                    setTimeout(() => {
                        uploadProgress.classList.add('hidden');
                        progressBar.style.width = '0%';
                        progressText.textContent = '0%';
                    }, 1500);
                });
        }

        function showAlert(message, type) {
            // Implement your alert/notification system here
            alert(message);
        }
    });
</script>
@endpush
@endsection
