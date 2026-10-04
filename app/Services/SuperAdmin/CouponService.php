<?php

namespace App\Services\SuperAdmin;

use App\Models\Coupon;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Coupon changes made from the Super Admin panel.
 * Business rules live here, not in the UI: a coupon that has been used keeps its
 * code and discount frozen, because invoices already point at it.
 */
class CouponService
{
    /** Fields that may no longer change once a coupon has been redeemed or put on an invoice. */
    private const FROZEN_ONCE_USED = ['code', 'discount_type', 'discount_value'];

    /** A coupon is "used" when it has redemptions or any invoice (even a deleted one) references it. */
    public static function isUsed(Coupon $coupon): bool
    {
        return $coupon->times_redeemed > 0
            || $coupon->invoices()->withTrashed()->exists();
    }

    /**
     * @param  array{code:string, discount_type:string, discount_value:int, valid_from:?\DateTimeInterface, valid_until:?\DateTimeInterface, max_redemptions:?int, is_active:bool}  $data
     *
     * @throws Throwable
     */
    public function create(SuperAdmin $actor, array $data): Coupon
    {
        return DB::transaction(function () use ($actor, $data) {
            $coupon = Coupon::create($data + ['times_redeemed' => 0]);

            activity('coupon')
                ->causedBy($actor)
                ->performedOn($coupon)
                ->withProperties([
                    'code' => $coupon->code,
                    'discount_type' => $data['discount_type'],
                    'discount_value' => $data['discount_value'],
                    'max_redemptions' => $data['max_redemptions'],
                ])
                ->log('Coupon created');

            return $coupon;
        });
    }

    /**
     * @param  array<string, mixed>  $data  same shape as create()
     *
     * @throws RuntimeException when a frozen field or an invalid redemption cap is submitted
     * @throws Throwable
     */
    public function update(SuperAdmin $actor, Coupon $coupon, array $data): Coupon
    {
        return DB::transaction(function () use ($actor, $coupon, $data) {
            // Re-read under a lock so "is it used?" and the write can't race a redemption.
            $coupon = Coupon::query()->lockForUpdate()->findOrFail($coupon->id);

            if (self::isUsed($coupon)) {
                foreach (self::FROZEN_ONCE_USED as $field) {
                    $current = $coupon->{$field};
                    $current = $current instanceof \BackedEnum ? $current->value : $current;

                    if ((string) $current !== (string) $data[$field]) {
                        throw new RuntimeException('This coupon has been used, so its code and discount can no longer be changed.');
                    }
                }
            }

            if ($data['max_redemptions'] !== null && $data['max_redemptions'] < $coupon->times_redeemed) {
                throw new RuntimeException("The limit can't be lower than the {$coupon->times_redeemed} redemptions already recorded.");
            }

            $coupon->fill($data);
            $changes = $coupon->getDirty();

            if ($changes === []) {
                return $coupon;
            }

            $coupon->save();

            activity('coupon')
                ->causedBy($actor)
                ->performedOn($coupon)
                ->withProperties(['code' => $coupon->code, 'changes' => $changes])
                ->log('Coupon updated');

            return $coupon;
        });
    }

    /** @throws Throwable */
    public function setActive(SuperAdmin $actor, Coupon $coupon, bool $active): void
    {
        DB::transaction(function () use ($actor, $coupon, $active) {
            $coupon = Coupon::query()->lockForUpdate()->findOrFail($coupon->id);

            if ($coupon->is_active === $active) {
                return;
            }

            $coupon->update(['is_active' => $active]);

            activity('coupon')
                ->causedBy($actor)
                ->performedOn($coupon)
                ->withProperties(['code' => $coupon->code, 'is_active' => $active])
                ->log($active ? 'Coupon activated' : 'Coupon deactivated');
        });
    }

    /** Soft delete. The code stays reserved and old invoices keep showing it. */
    public function delete(SuperAdmin $actor, Coupon $coupon): void
    {
        DB::transaction(function () use ($actor, $coupon) {
            $coupon->delete();

            activity('coupon')
                ->causedBy($actor)
                ->performedOn($coupon)
                ->withProperties([
                    'code' => $coupon->code,
                    'times_redeemed' => $coupon->times_redeemed,
                ])
                ->log('Coupon deleted');
        });
    }
}
