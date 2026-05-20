@extends($layout)
@section('title', 'SMS')

@section('content')
@include('cashier.partials.cashier-flash-toast')
@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if(session('warning'))
  <div class="alert alert-warning">{{ session('warning') }}</div>
@endif
<div class="container py-2">
  <h2 class="fw-bold mb-4"><i class="fas fa-sms me-2"></i>SMS &amp; Oil Change Reminders</h2>

  <div class="card mb-4 shadow-sm">
    <div class="card-header fw-semibold">Reminder settings</div>
    <div class="card-body">
      @php
        $settingsRoute = request()->routeIs('admin.*') ? 'admin.sms.settings' : 'cashier.sms.settings';
      @endphp
      <form method="POST" action="{{ route($settingsRoute) }}">
        @csrf
        @method('PUT')
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Days before due date</label>
            <input type="number" name="reminder_days_before" class="form-control" min="0" max="365"
              value="{{ old('reminder_days_before', $settings->reminder_days_before) }}" required>
            <div class="form-text">e.g. 5 = send 5 days before next oil change is due</div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Interval (months)</label>
            <input type="number" name="interval_months" class="form-control" min="1" max="36"
              value="{{ old('interval_months', $settings->interval_months) }}" required>
            <div class="form-text">Months after paid oil-change invoice</div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Job match text</label>
            <input type="text" name="oil_change_match" class="form-control" maxlength="100"
              value="{{ old('oil_change_match', $settings->oil_change_match) }}" required>
            <div class="form-text">Job description must contain this (case-insensitive)</div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Sender ID override</label>
            <input type="text" name="sender_id" class="form-control" maxlength="11"
              value="{{ old('sender_id', $settings->sender_id) }}" placeholder="{{ config('philsms.sender_id') }}">
            <div class="form-text">Leave blank to use .env PHILSMS_SENDER_ID</div>
          </div>
          <div class="col-12">
            <label class="form-label">Message template</label>
            <textarea name="message_template" class="form-control" rows="3" maxlength="500" required>{{ old('message_template', $settings->message_template) }}</textarea>
            <div class="form-text">Placeholders: {customer_name}, {plate}, {due_date}, {oil_change_date}, {remind_on}</div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input type="hidden" name="enabled" value="0">
              <input type="checkbox" name="enabled" value="1" class="form-check-input" id="sms_enabled"
                {{ old('enabled', $settings->enabled) ? 'checked' : '' }}>
              <label class="form-check-label" for="sms_enabled">Enable automatic scheduled reminders</label>
            </div>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary">Save settings</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="card mb-4 shadow-sm">
    <div class="card-header fw-semibold">Manual SMS</div>
    <div class="card-body">
      @php
        $manualRoute = request()->routeIs('admin.*') ? 'admin.sms.send' : 'cashier.sms.send';
      @endphp
      <form method="POST" action="{{ route($manualRoute) }}" class="row g-3" id="manual-sms-form"
        data-all-clients-count="{{ (int) $uniqueManualSmsRecipientCount }}">
        @csrf
        <input type="hidden" name="send_to_all_clients" value="0">
        <div class="col-12">
          <div class="form-check">
            <input type="checkbox" name="send_to_all_clients" value="1" class="form-check-input" id="manual_sms_all_clients"
              {{ old('send_to_all_clients') ? 'checked' : '' }}
              @if(($uniqueManualSmsRecipientCount ?? 0) === 0) disabled @endif>
            <label class="form-check-label" for="manual_sms_all_clients">
              Send to all client numbers ({{ $uniqueManualSmsRecipientCount ?? 0 }} unique valid numbers on file)
            </label>
            <div class="form-text">Uses every distinct normalized phone from the clients list—handy for shop-wide promos. You still enter the message below.</div>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" id="manual_sms_phone" class="form-control" placeholder="09XXXXXXXXX" maxlength="32"
            value="{{ old('phone') }}"
            @if(old('send_to_all_clients')) disabled @else required @endif>
        </div>
        <div class="col-md-8">
          <label class="form-label">Message</label>
          <textarea name="message" class="form-control" rows="2" maxlength="500" required>{{ old('message') }}</textarea>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-success">Send now</button>
        </div>
      </form>
      <script>
        (function () {
          var form = document.getElementById('manual-sms-form');
          if (!form) return;
          var cb = document.getElementById('manual_sms_all_clients');
          var phone = document.getElementById('manual_sms_phone');
          if (!cb || !phone) return;
          function syncPhoneField() {
            if (cb.checked) {
              phone.removeAttribute('required');
              phone.setAttribute('disabled', 'disabled');
            } else {
              phone.removeAttribute('disabled');
              phone.setAttribute('required', 'required');
            }
          }
          cb.addEventListener('change', syncPhoneField);
          syncPhoneField();
          form.addEventListener('submit', function (e) {
            if (!cb.checked) return;
            var n = parseInt(form.getAttribute('data-all-clients-count') || '0', 10);
            var msg = 'Send this message to all ' + n + ' client number' + (n === 1 ? '' : 's') + '?';
            if (!window.confirm(msg)) {
              e.preventDefault();
            }
          });
        })();
      </script>
    </div>
  </div>

  <div class="card mb-4 shadow-sm">
    <div class="card-header fw-semibold">Oil change reminders</div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Customer</th>
              <th>Plate</th>
              <th>Phone</th>
              <th>Oil change</th>
              <th>Due</th>
              <th>Remind on</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($reminders as $r)
            <tr>
              <td>{{ $r->customer_name ?? '—' }}</td>
              <td>{{ $r->plate ?? '—' }}</td>
              <td>{{ $r->phone }}</td>
              <td>{{ $r->oil_change_date?->format('M j, Y') }}</td>
              <td>{{ $r->due_date?->format('M j, Y') }}</td>
              <td>{{ $r->remind_on?->format('M j, Y') }}</td>
              <td>
                @if($r->status === 'pending')
                  <span class="badge bg-warning text-dark">Pending</span>
                @elseif($r->status === 'sent')
                  <span class="badge bg-success">Sent</span>
                @elseif($r->status === 'failed')
                  <span class="badge bg-danger">Failed</span>
                @else
                  <span class="badge bg-secondary">{{ $r->status }}</span>
                @endif
              </td>
              <td class="text-end">
                @if(in_array($r->status, ['pending', 'failed'], true))
                  @php
                    $sendRoute = request()->routeIs('admin.*') ? 'admin.sms.reminder.send' : 'cashier.sms.reminder.send';
                  @endphp
                  <form method="POST" action="{{ route($sendRoute, $r->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary">Send now</button>
                  </form>
                @endif
              </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted py-4">No reminders yet. Schedule by saving a paid invoice with a matching oil-change job.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($reminders->hasPages())
        <div class="card-footer py-3 d-flex justify-content-center">
          {{ $reminders->onEachSide(1)->links() }}
        </div>
      @endif
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header fw-semibold">Recent SMS log</div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
          <thead class="table-light">
            <tr>
              <th>When</th>
              <th>Phone</th>
              <th>Message</th>
              <th>Status</th>
              <th>By</th>
            </tr>
          </thead>
          <tbody>
            @forelse($logs as $log)
            <tr>
              <td class="text-nowrap">{{ $log->created_at?->format('M j, Y g:i A') }}</td>
              <td>{{ $log->phone }}</td>
              <td style="max-width:280px;word-break:break-word;">{{ Str::limit($log->message, 80) }}</td>
              <td>
                @if($log->status === 'sent')
                  <span class="badge bg-success">Sent</span>
                @else
                  <span class="badge bg-danger">Failed</span>
                @endif
              </td>
              <td>{{ $log->sentBy?->name ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted py-3">No messages sent yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
