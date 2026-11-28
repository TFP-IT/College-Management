@if ($invoice['due'] > 0)
<button type="button" class="btn soft small" onclick="document.getElementById('collect-{{ $invoice['id'] }}').showModal()">Collect</button>
<dialog id="collect-{{ $invoice['id'] }}" aria-labelledby="collect-title-{{ $invoice['id'] }}">
    <form method="post" action="{{ route('office.fees.collect', $invoice['id']) }}" onsubmit="this.querySelector('[type=submit]').disabled = true">
        @csrf
        <h2 id="collect-title-{{ $invoice['id'] }}">Collect payment</h2>
        <p class="small muted">{{ $invoice['title'] }}, {{ $invoice['number'] }}. ৳{{ number_format($invoice['due']) }} is due.</p>
        <div class="field"><label for="amount-{{ $invoice['id'] }}">Amount received (৳)</label><input id="amount-{{ $invoice['id'] }}" type="number" name="amount" min="1" max="{{ $invoice['due'] }}" value="{{ $invoice['due'] }}" required></div>
        <div class="field"><label for="method-{{ $invoice['id'] }}">Method</label><select id="method-{{ $invoice['id'] }}" name="method">@foreach (['cash' => 'Cash', 'bank' => 'Bank', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket', 'card' => 'Card'] as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></div>
        <div class="field"><label for="ref-{{ $invoice['id'] }}">Reference</label><input id="ref-{{ $invoice['id'] }}" name="reference" maxlength="120" placeholder="Transaction or deposit slip number (optional)"></div>
        <div class="dialog-actions">
            <button type="button" class="btn quiet" onclick="this.closest('dialog').close()">Cancel</button>
            <button type="submit" class="btn">Record and show receipt</button>
        </div>
    </form>
</dialog>
@endif
