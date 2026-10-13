<?php

declare(strict_types=1);

namespace Cepuder\FountainDrinks\Models;

use Igniter\Cart\Models\Order;
use Igniter\Flame\Database\Model;

/**
 * An issued drink code: one per fountain drink on a paid order.
 *
 * @property int $id
 * @property int $order_id
 * @property string $label
 * @property string $size
 * @property string $token_id
 * @property int $issued_at
 * @property string $payload
 * @property string $secret_id
 */
class Code extends Model
{
    protected $table = 'cepuder_fountaindrinks_codes';

    protected $guarded = [];

    protected $hidden = ['payload'];

    protected $casts = [
        'sugar' => 'boolean',
        'flavors' => 'boolean',
        'issued_at' => 'integer',
    ];

    public $relation = [
        'belongsTo' => [
            'order' => [Order::class, 'foreignKey' => 'order_id'],
        ],
    ];
}
