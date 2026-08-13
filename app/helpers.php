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

if (! function_exists('formatQuantity')) {
    /**
     * Format jumlah kuantitas agar tidak menampilkan desimal nol yang tidak perlu.
     * Contoh: 40.000 -> 40, 40.500 -> 40,5
     */
    function formatQuantity(float|int|string|null $quantity): string
    {
        if ($quantity === null) {
            return '0';
        }

        $num = (float) $quantity;
        if (floor($num) == $num) {
            return number_format($num, 0, ',', '.');
        }

        $formatted = number_format($num, 3, ',', '.');
        return rtrim(rtrim($formatted, '0'), ',');
    }
}
