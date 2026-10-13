<?php

declare(strict_types=1);

namespace Cepuder\FountainDrinks;

use Cepuder\FountainDrinks\Classes\Codes;
use Igniter\Admin\Widgets\Form;
use Igniter\Cart\Models\Menu;
use Igniter\Cart\Models\Order;
use Igniter\Orange\Livewire\OrderPreview;
use Igniter\System\Classes\BaseExtension;
use Illuminate\Support\Facades\Event;
use Override;

/**
 * Fountain Drinks + QR: a Spire code per fountain drink on paid orders.
 *
 * - Card and other online payments: codes when the payment is processed, before the confirmation email.
 * - Pay at the counter (cod): codes when staff move the order to a completed status, so a code never
 *   shows before the money is in. The order page polls, so the codes appear on the customer's phone.
 */
class Extension extends BaseExtension
{
    #[Override]
    public function boot(): void
    {
        Menu::extend(function($model): void {
            $model->mergeFillable(['spire_size', 'spire_sugar', 'spire_flavors']);
            $model->mergeCasts(['spire_sugar' => 'boolean', 'spire_flavors' => 'boolean']);
        });

        Event::listen('admin.order.beforePaymentProcessed', function(Order $order): void {
            if (!Codes::paysLater($order)) {
                Codes::mintForOrder($order);
            }
        });

        Event::listen('igniter.cart.beforeAddOrderStatus', function($statusHistory, $order, $statusId): void {
            if ($order instanceof Order && Codes::paysLater($order)
                && in_array((int)$statusId, array_map('intval', (array)setting('completed_order_status')), true)) {
                Codes::mintForOrder($order);
            }
        });

        // Codes in the order emails, for templates that use {!! $drink_codes !!}
        Order::extend(function($model): void {
            $model->bindEvent('model.mailGetData', function(array &$data) use ($model): void {
                $codes = Codes::forOrder((int)$model->order_id);
                $data['drink_codes_count'] = $codes->count();
                $data['drink_codes'] = $codes->isEmpty() ? '' : view('cepuder.fountaindrinks::_partials.mail-codes', ['codes' => $codes])->render();
            });
        });

        $this->extendForms();
        $this->extendOrderPage();
    }

    /** Drink settings on menu items, and the codes (or why they're missing) on the admin order page. */
    protected function extendForms(): void
    {
        Event::listen('admin.form.extendFields', function(Form $form): void {
            if ($form->model instanceof Menu && !$form->isNested) {
                $tab = 'lang:cepuder.fountaindrinks::default.tab_fountain_drink';
                $form->addTabFields([
                    'spire_size' => [
                        'tab' => $tab,
                        'label' => 'lang:cepuder.fountaindrinks::default.label_size',
                        'type' => 'select',
                        'options' => [
                            '' => 'lang:cepuder.fountaindrinks::default.size_none',
                            'Small' => 'lang:cepuder.fountaindrinks::default.size_small',
                            'Medium' => 'lang:cepuder.fountaindrinks::default.size_medium',
                            'Large' => 'lang:cepuder.fountaindrinks::default.size_large',
                        ],
                        'comment' => 'lang:cepuder.fountaindrinks::default.help_size',
                    ],
                    'spire_sugar' => [
                        'tab' => $tab,
                        'label' => 'lang:cepuder.fountaindrinks::default.label_sugar',
                        'type' => 'switch',
                        'span' => 'left',
                        'comment' => 'lang:cepuder.fountaindrinks::default.help_sugar',
                    ],
                    'spire_flavors' => [
                        'tab' => $tab,
                        'label' => 'lang:cepuder.fountaindrinks::default.label_flavors',
                        'type' => 'switch',
                        'span' => 'right',
                        'comment' => 'lang:cepuder.fountaindrinks::default.help_flavors',
                    ],
                ]);
            }

            if ($form->model instanceof Order && $form->model->exists && !$form->isNested) {
                $form->addTabFields([
                    '_drink_codes' => [
                        'tab' => 'lang:cepuder.fountaindrinks::default.tab_drink_codes',
                        'type' => 'partial',
                        'path' => 'cepuder.fountaindrinks::_partials.admin-order-codes',
                    ],
                ]);
            }
        });
    }

    /** The codes on the customer's order page (checkout success and My orders), in the Orange theme. */
    protected function extendOrderPage(): void
    {
        if (!class_exists(OrderPreview::class) || !function_exists('Livewire\on')) {
            return;
        }
        \Livewire\on('render', function($component, $view) {
            if (!$component instanceof OrderPreview) {
                return null;
            }

            return function(string $html, callable $replaceHtml) use ($view): void {
                $order = $view->getData()['order'] ?? null;
                if (!$order instanceof Order) {
                    return;
                }
                $block = view('cepuder.fountaindrinks::_partials.order-codes', [
                    'codes' => Codes::forOrder((int)$order->order_id),
                    'waiting' => Codes::paysLater($order) && Codes::drinkLines($order) !== [],
                ])->render();
                // Inside the component's single root element
                $pos = strrpos($html, '</div>');
                if ($pos !== false && trim($block) !== '') {
                    $replaceHtml(substr($html, 0, $pos).$block.substr($html, $pos));
                }
            };
        });
    }

    #[Override]
    public function registerPermissions(): array
    {
        return [
            'Cepuder.FountainDrinks' => [
                'label' => 'cepuder.fountaindrinks::default.permissions',
                'group' => 'igniter.cart::default.text_permission_order_group',
            ],
        ];
    }

    #[Override]
    public function registerNavigation(): array
    {
        return [
            'restaurant' => [
                'child' => [
                    'fountaindrinks' => [
                        'priority' => 50,
                        'class' => 'fountaindrinks',
                        'href' => admin_url('cepuder/fountaindrinks/fountain_drinks'),
                        'title' => lang('cepuder.fountaindrinks::default.side_menu'),
                        'permission' => 'Cepuder.FountainDrinks',
                    ],
                ],
            ],
        ];
    }
}
