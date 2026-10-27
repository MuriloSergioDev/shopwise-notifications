<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Contact data copied from identity's events (identity.customer-registered, orders.order-placed).
 *
 * @property string $customer_id
 * @property string $email
 * @property string|null $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['customer_id', 'email', 'name'])]
#[WithoutIncrementing]
final class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected $primaryKey = 'customer_id';

    protected $keyType = 'string';
}
