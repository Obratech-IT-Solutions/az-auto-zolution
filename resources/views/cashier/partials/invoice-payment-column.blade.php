{{-- Payment method labels only (no amounts). Parent uses .inv-pay-wrap for centering. --}}
@php
  $pt = $h->payment_type ?? '';
  $ptLabel = $pt !== '' ? ucfirst(str_replace('_', ' ', $pt)) : '—';
  $ptBadgeBg = $pt === 'cash' ? 'success' : ($pt === 'credit' ? 'primary' : 'secondary');
  $pc = (float) ($h->payment_cash_amount ?? 0);
  $pn = (float) ($h->payment_non_cash_amount ?? 0);
@endphp
@if($pc >= 0.01 && $pn >= 0.01)
  <span class="badge bg-success">Cash</span>
  <span class="badge bg-{{ $ptBadgeBg }}">{{ $ptLabel }}</span>
@elseif($pc >= 0.01 && $pn < 0.01)
  <span class="badge bg-success">Cash</span>
@elseif($pn >= 0.01 && $pc < 0.01)
  <span class="badge bg-{{ $ptBadgeBg }}">{{ $ptLabel }}</span>
@else
  <span class="badge bg-{{ $ptBadgeBg }}">{{ $ptLabel }}</span>
@endif
