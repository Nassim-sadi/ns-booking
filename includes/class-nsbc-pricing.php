<?php
if (!defined('ABSPATH')) exit;

class NSBC_Pricing {
    public static function currency_symbol($currency) {
        $map = ['EUR'=>'€','USD'=>'$','GBP'=>'£','MAD'=>'MAD','TRY'=>'₺','AED'=>'AED','SAR'=>'﷼'];
        return $map[strtoupper($currency)] ?? $currency;
    }

    /**
     * Recalculate total in cents. Server is source of truth (frontend total is display-only).
     */
    public static function calculate(int $package_id, string $session_type, array $extra_ids): int {
        $session_type = $session_type === 'couple' ? 'couple' : 'solo';
        $key = $session_type === 'couple' ? '_package_price_couple' : '_package_price_solo';
        $base = (int) get_post_meta($package_id, $key, true);
        if ($base < 0) $base = 0;

        $allowed = (array) get_post_meta($package_id, '_package_extra_ids', true);
        $allowed = array_map('intval', $allowed);
        $total = $base;
        foreach ($extra_ids as $eid) {
            $eid = (int) $eid;
            if (!in_array($eid, $allowed, true)) continue; // not assigned → ignore spoof
            if (get_post_type($eid) !== NSBC_CPT_EXTRA) continue;
            $active = get_post_meta($eid, '_extra_active', true);
            if ($active !== '' && !$active && $active !== '1') continue;
            $price = (int) get_post_meta($eid, '_extra_price_cents', true);
            if ($price > 0) $total += $price;
        }
        return $total;
    }

    public static function format(int $cents, string $currency = 'EUR'): string {
        $symbol = self::currency_symbol($currency);
        $amount = number_format($cents / 100, 2, '.', ',');
        // Symbol before for most, MAD after? keep symbol + amount
        if (in_array(strtoupper($currency), ['MAD','AED','SAR'])) return $amount . ' ' . $symbol;
        return $symbol . $amount;
    }
}
