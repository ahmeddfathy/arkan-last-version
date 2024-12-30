@extends('layouts.app')

@section('content')
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card">
        <div class="card-header">إنشاء إشعار جديد</div>

        <div class="card-body">
          <form method="POST" action="{{ route('admin.notifications.store') }}">
            @csrf

            <div class="form-group mb-3">
              <label for="title">عنوان الإشعار</label>
              <input type="text"
                class="form-control @error('title') is-invalid @enderror"
                id="title"
                name="title"
                value="{{ old('title') }}"
                required>
              @error('title')
              <span class="invalid-feedback">{{ $message }}</span>
              @enderror
            </div>

            <div class="form-group mb-3">
              <label for="message">نص الإشعار</label>
              <textarea class="form-control @error('message') is-invalid @enderror"
                id="message"
                name="message"
                rows="4"
                required>{{ old('message') }}</textarea>
              @error('message')
              <span class="invalid-feedback">{{ $message }}</span>
              @enderror
            </div>

            <div class="form-group">
              <button type="submit" class="btn btn-primary">
                إرسال الإشعار
              </button>
              <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">
                إلغاء
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection