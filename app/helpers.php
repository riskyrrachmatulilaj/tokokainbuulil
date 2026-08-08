<?php

if (! function_exists('rupiah')) {
    /**
     * Format angka menjadi format Rupiah Indonesia.
     */
    function rupiah(float|int|string|null $amount, bool $withDecimals = false): string
    {
        $amount = (float) ($amount ?? 0);

        return $withDecimals
            ? 'Rp '.number_format($amount, 2, ',', '.')
            : 'Rp '.number_format($amount, 0, ',', '.');
    }
}
