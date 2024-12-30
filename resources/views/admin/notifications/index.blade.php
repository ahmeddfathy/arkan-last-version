@extends('layouts.app')

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>إدارة الإشعارات</h2>
    <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">
      إنشاء إشعار جديد
    </a>
  </div>

  @if(session('success'))
  <div class="alert alert-success">
    {{ session('success') }}
  </div>
  @endif

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>العنوان</th>
              <th>الرسالة</th>
              <th>تاريخ الإنشاء</th>
              <th>الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            @foreach($notifications as $notification)
            <tr>
              <td>{{ $notification->data['title'] }}</td>
              <td>{{ $notification->data['message'] }}</td>
              <td>{{ $notification->created_at->format('Y-m-d H:i') }}</td>
              <td>
                <a href="{{ route('admin.notifications.edit', $notification) }}"
                  class="btn btn-sm btn-primary">
                  تعديل
                </a>
                <form action="{{ route('admin.notifications.destroy', $notification) }}"
                  method="POST"
                  class="d-inline">
                  @csrf
                  @method('DELETE')
                  <button type="submit"
                    class="btn btn-sm btn-danger"
                    onclick="return confirm('هل أنت متأكد من الحذف؟')">
                    حذف
                  </button>
                </form>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      {{ $notifications->links() }}
    </div>
  </div>
</div>
@endsection