<?php

namespace Soap\ShoppingCart\Contracts;

interface CouponInterface
{
    /**
     * Get the coupon code.
     * This is the code that the user will enter to apply the coupon.
     * This should be unique for each coupon.
     *
     * @throws \Exception if the coupon code is not set.
     */
    public function getCode(): string;

    /**
     * Get the discount type of the coupon.
     * This could be 'percentage', 'subtraction' or ''fixed'.
     * This will determine how the discount is applied to the cart.
     */
    public function getDiscountType(): string;

    /**
     * Get the discount value of the coupon.
     * This will be a percentage or a fixed amount depending on the discount type.
     *
     * @param  float  $total  The total amount of the cart to apply for discount.
     *                        This is the total amount before the discount is applied.
     */
    public function getDiscountValue($total): float;

    /**
     * Get the type of items the coupon applies to.
     * This could be 'subtotal', 'total', 'shipping'
     */
    public function getAppliesTo(): string;

    /**
     * Get the expiration date of the coupon.
     * Returns null if the coupon does not expire.
     */
    public function getExpiresAt(): ?Carbon;

    /**
     * Check if the coupon is expired.
     * Returns true if the coupon is expired, false otherwise.
     */
    public function isExpires(): bool;

    /**
     * Get minumum order value for the coupon to be valid.
     * This is the minimum amount that the cart must be for the coupon to be valid.
     * This is used to prevent the coupon from being used on very small orders.
     * This is optional and can be null.
     */
    public function getMinOrderValue(): ?float;
}
