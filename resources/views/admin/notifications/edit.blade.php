@extends('layouts.app')

@section('content')
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card">
        <div class="card-header">تعديل الإشعار</div>

        <div class="card-body">
          <form method="POST" action="{{ route('admin.notifications.update', $notification) }}">
            @csrf
            @method('PUT')

            <div class="form-group mb-3">
              <label for="title">عنوان الإشعار</label>
              <input type="text"
                class="form-control @error('title') is-invalid @enderror"
                id="title"
                name="title"
                value="{{ old('title', $notification->data['title']) }}"
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
                required>{{ old('message', $notification->data['message']) }}</textarea>
              @error('message')
              <span class="invalid-feedback">{{ $message }}</span>
              @enderror
            </div>

            <div class="form-group">
              <button type="submit" class="btn btn-primary">
                تحديث الإشعار
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