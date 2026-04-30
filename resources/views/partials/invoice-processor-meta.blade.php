@if($invoice->createdByUser || $invoice->lastProcessedByUser)
  <div class="processor-meta px-4 pb-2" style="font-size:0.75rem;color:#555;border-bottom:1px solid #eee;">
    @if($invoice->createdByUser)
      <span class="me-3"><strong>Created by:</strong> {{ $invoice->createdByUser->attributionName() }}</span>
    @endif
    @if($invoice->lastProcessedByUser)
      <span><strong>Last processed:</strong> {{ $invoice->lastProcessedByUser->attributionName() }}</span>
    @endif
  </div>
@endif
