<?php

namespace Soap\ShoppingCart\Contracts;

use Carbon\Carbon;

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
     */
    public function getDiscountValue(): float;

    /**
     * Get the type of items the coupon applies to.
     * This could be 'subtotal', 'total', 'shipping'
     */
    public function getAppliesTarget(): ?string;

    /**
     * Get the expiration date of the coupon.
     * Returns null if the coupon does not expire.
     */
    public function getExpiresAt(): ?Carbon;

    /**
     * Check if the coupon is expired.
     * Returns true if the coupon is expired, false otherwise.
     */
    public function isExpired(): bool;

    /**
     * Get minumum order value for the coupon to be valid.
     * This is the minimum amount that the cart must be for the coupon to be valid.
     * This is used to prevent the coupon from being used on very small orders.
     * This is optional and can be null.
     */
    public function getMinOrderValue(): ?float;

    /**
     * Get the maximum discount value for the coupon.
     * This is the maximum amount that the coupon can discount.
     * This is used to prevent the coupon from being used on very large orders.
     * This is optional and can be null.
     */
    public function getDiscountLimit(): ?float;
}
