<div class="d-flex p-3">
    <h4 class="page-title mb-0 lh-base">
        <span>{!! Template::getHeading() !!}</span>
    </h4>
</div>
<div class="row-fluid px-3">
    <p class="text-muted">@lang('cepuder.fountaindrinks::default.intro')</p>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h5 class="card-title">@lang('cepuder.fountaindrinks::default.licence')</h5>
            @if($licence)
                <table class="table table-sm w-auto mb-3">
                    <tr><th class="pe-4">@lang('cepuder.fountaindrinks::default.customer')</th><td>{{ $licence['customer'] }}</td></tr>
                    <tr><th class="pe-4">@lang('cepuder.fountaindrinks::default.tier')</th><td>{{ $licence['tier'] }}</td></tr>
                    <tr><th class="pe-4">@lang('cepuder.fountaindrinks::default.allowed_sizes')</th>
                        <td>{{ $allowedSizes ? implode(', ', array_map([\Cepuder\FountainDrinks\Classes\Codes::class, 'sizeLabel'], $allowedSizes)) : lang('cepuder.fountaindrinks::default.no_sizes') }}</td></tr>
                    <tr><th class="pe-4">@lang('cepuder.fountaindrinks::default.secret_id')</th>
                        <td><strong>{{ $licence['secret_id'] }}</strong> <span class="text-muted small">@lang('cepuder.fountaindrinks::default.help_secret_id')</span></td></tr>
                </table>
            @else
                <p class="text-danger">@lang('cepuder.fountaindrinks::default.no_licence')</p>
            @endif
            <form method="POST" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-center">
                @csrf
                <input type="hidden" name="_handler" value="onUploadLicence">
                <label class="form-label mb-0">@lang($licence ? 'cepuder.fountaindrinks::default.replace_licence' : 'cepuder.fountaindrinks::default.upload_licence')</label>
                <input type="file" name="licence_file" accept=".json,application/json" class="form-control w-auto">
                <button type="submit" class="btn btn-primary">@lang('cepuder.fountaindrinks::default.button_upload')</button>
            </form>
            <p class="small text-muted mt-2 mb-0">@lang('cepuder.fountaindrinks::default.help_upload')</p>
            @if($licence)
                <form method="POST" class="mt-3" onsubmit="return confirm('{{ lang('cepuder.fountaindrinks::default.confirm_remove') }}')">
                    @csrf
                    <input type="hidden" name="_handler" value="onRemoveLicence">
                    <button type="submit" class="btn btn-outline-danger btn-sm">@lang('cepuder.fountaindrinks::default.button_remove')</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h5 class="card-title">@lang('cepuder.fountaindrinks::default.drinks')</h5>
            <p class="small text-muted">@lang('cepuder.fountaindrinks::default.help_drinks')</p>
            @if($drinks->isEmpty())
                <p class="mb-0">@lang('cepuder.fountaindrinks::default.no_drinks')</p>
            @else
                <table class="table table-sm mb-0">
                    <thead><tr>
                        <th>@lang('cepuder.fountaindrinks::default.column_item')</th>
                        <th>@lang('cepuder.fountaindrinks::default.label_size')</th>
                        <th>@lang('cepuder.fountaindrinks::default.label_sugar')</th>
                        <th>@lang('cepuder.fountaindrinks::default.label_flavors')</th>
                    </tr></thead>
                    @foreach($drinks as $drink)
                        <tr>
                            <td><a href="{{ admin_url('menus/edit/'.$drink->menu_id) }}">{{ $drink->menu_name }}</a>
                                @unless($drink->menu_status)<span class="text-muted small">(@lang('igniter::admin.text_disabled'))</span>@endunless</td>
                            <td>{{ \Cepuder\FountainDrinks\Classes\Codes::sizeLabel($drink->spire_size) }}
                                @if($licence && !in_array($drink->spire_size, $allowedSizes, true))
                                    <span class="text-danger small">@lang('cepuder.fountaindrinks::default.not_in_licence')</span>
                                @endif</td>
                            <td>{{ $drink->spire_sugar ? '✓' : '' }}</td>
                            <td>{{ $drink->spire_flavors ? '✓' : '' }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h5 class="card-title">@lang('cepuder.fountaindrinks::default.issued_codes')</h5>
            <p class="small text-muted">@lang('cepuder.fountaindrinks::default.help_issued_codes')</p>
            @if($codes->isEmpty())
                <p class="mb-0">@lang('cepuder.fountaindrinks::default.no_codes_yet')</p>
            @else
                <table class="table table-sm mb-0">
                    <thead><tr>
                        <th>@lang('cepuder.fountaindrinks::default.column_issued')</th>
                        <th>@lang('cepuder.fountaindrinks::default.column_order')</th>
                        <th>@lang('cepuder.fountaindrinks::default.column_drink')</th>
                        <th>@lang('cepuder.fountaindrinks::default.column_code_id')</th>
                        <th>@lang('cepuder.fountaindrinks::default.secret_id')</th>
                    </tr></thead>
                    @foreach($codes as $code)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::createFromTimestamp($code->issued_at, config('app.timezone'))->isoFormat('L LT') }}</td>
                            <td><a href="{{ admin_url('orders/edit/'.$code->order_id) }}">#{{ $code->order_id }}</a></td>
                            <td>{{ \Cepuder\FountainDrinks\Classes\Codes::drinkLabel($code) }}</td>
                            <td><code>{{ $code->token_id }}</code></td>
                            <td>{{ $code->secret_id }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>
    </div>
</div>
