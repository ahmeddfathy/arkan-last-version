<div>
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">استيراد الموظفين</h3>
    </div>

    <div class="card-body">
      <form wire:submit.prevent="import">
        <div class="form-group">
          <label for="file">اختر ملف Excel</label>
          <input type="file" class="form-control" wire:model="file" id="file">
          @error('file') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <button type="submit" class="btn btn-primary mt-3">
          <span wire:loading wire:target="import" class="spinner-border spinner-border-sm" role="status"></span>
          استيراد
        </button>
      </form>

      @if(session('success'))
      <div class="alert alert-success mt-3">
        {{ session('success') }}
      </div>
      @endif

      @if(session('import_summary'))
      <div class="alert alert-warning mt-3">
        <h4>ملخص الاستيراد</h4>
        <p>تم تخطي {{ session('skipped_count') }} سجل</p>
        <pre style="white-space: pre-wrap;">{{ session('import_summary') }}</pre>
      </div>
      @endif
    </div>
  </div>
</div>