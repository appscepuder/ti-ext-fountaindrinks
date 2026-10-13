{{-- Order emails: PNG QRs from signed URLs (many email clients don't show SVG or data URIs) --}}
<div style="text-align:center">
    <p><strong>@lang('cepuder.fountaindrinks::default.scan_at_dispenser')</strong></p>
    @foreach($codes as $i => $code)
        <p style="margin:16px 0">
            {{ \Cepuder\FountainDrinks\Classes\Codes::drinkLabel($code) }}@if($codes->count() > 1) · {{ $i + 1 }}/{{ $codes->count() }}@endif<br>
            <img src="{{ \Cepuder\FountainDrinks\Classes\Codes::pngUrl($code) }}" width="220" height="220" alt="QR"><br>
            <small>@lang('cepuder.fountaindrinks::default.valid_one_drink')</small>
        </p>
    @endforeach
</div>
