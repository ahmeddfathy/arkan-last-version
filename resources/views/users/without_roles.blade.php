<div class="container">
  <h2>الموظفون بدون أدوار</h2>

  <form action="{{ route('users.assign-employee-role') }}" method="POST">
    @csrf
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th><input type="checkbox" id="select-all"></th>
            <th>الاسم</th>
            <th>البريد الإلكتروني</th>
            <th>القسم</th>
          </tr>
        </thead>
        <tbody>
          @foreach($usersWithoutRole as $user)
          <tr>
            <td>
              <input type="checkbox" name="user_ids[]" value="{{ $user->id }}">
            </td>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->department }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <button type="submit" class="btn btn-primary">
      تعيين دور الموظف للمحددين
    </button>
  </form>
</div>

<script>
  document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.getElementsByName('user_ids[]');
    checkboxes.forEach(checkbox => {
      checkbox.checked = this.checked;
    });
  });
</script>