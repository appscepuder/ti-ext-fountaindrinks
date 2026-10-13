{{-- Admin order page: the codes issued for this order, or why they are missing --}}
@php
    $order = $formModel;
    $codes = \Cepuder\FountainDrinks\Classes\Codes::forOrder((int)$order->order_id);
    $drinks = \Cepuder\FountainDrinks\Classes\Codes::drinkLines($order);
    $missing = $order->isPaymentProcessed() ? \Cepuder\FountainDrinks\Classes\Codes::missingReason($order, $codes->count()) : null;
    $waiting = $codes->isEmpty() && $drinks && \Cepuder\FountainDrinks\Classes\Codes::paysLater($order);
@endphp
@if($waiting)
    <div class="alert alert-info">@lang('cepuder.fountaindrinks::default.admin_waiting')</div>
@elseif($missing)
    <div class="alert alert-danger fw-bold">{{ $missing }}</div>
@endif
@if($codes->isEmpty() && !$drinks)
    <p class="text-muted">@lang('cepuder.fountaindrinks::default.no_drinks_on_order')</p>
@endif
@if($codes->isNotEmpty())
    <div class="d-flex flex-wrap gap-4">
        @foreach($codes as $i => $code)
            <div class="text-center" style="width: 200px">
                <div class="fw-bold">{{ \Cepuder\FountainDrinks\Classes\Codes::drinkLabel($code) }}</div>
                <div style="background:#fff;padding:4px">{!! \Cepuder\FountainDrinks\Classes\Codes::qrSvg($code->payload) !!}</div>
                <code class="small">{{ $code->token_id }}</code>
            </div>
        @endforeach
    </div>
@endif
