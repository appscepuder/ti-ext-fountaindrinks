{{-- Customer's order page (Orange theme OrderPreview): one QR per drink, or a note while a cash order waits --}}
@if($codes->isNotEmpty())
    <div class="card shadow-sm mb-3" id="cepuder-drink-codes">
        <div class="card-body text-center">
            <h5 class="mb-1">@lang('cepuder.fountaindrinks::default.scan_at_dispenser')</h5>
            <p class="text-muted mb-3">@lang('cepuder.fountaindrinks::default.one_code_per_drink')</p>
            <div class="d-flex flex-wrap justify-content-center gap-4">
                @foreach($codes as $i => $code)
                    <div style="width: 240px">
                        <div class="fw-bold">{{ \Cepuder\FountainDrinks\Classes\Codes::drinkLabel($code) }}@if($codes->count() > 1) · {{ $i + 1 }}/{{ $codes->count() }}@endif</div>
                        <div style="background:#fff;padding:4px">{!! \Cepuder\FountainDrinks\Classes\Codes::qrSvg($code->payload) !!}</div>
                        <div class="small text-muted">@lang('cepuder.fountaindrinks::default.valid_one_drink')</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@elseif($waiting)
    <div class="card shadow-sm mb-3" id="cepuder-drink-codes">
        <div class="card-body text-center">
            <p class="mb-0">@lang('cepuder.fountaindrinks::default.codes_after_payment')</p>
        </div>
    </div>
@endif
