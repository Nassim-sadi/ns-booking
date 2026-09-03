<?php
/** @var string $package @var string $session @var string $date @var string $total @var string $name @var string $email @var string $phone @var string $msg @var string $extrasHtml @var string $adminUrl */
?>
<!doctype html>
<html><body style="font-family:Arial,sans-serif;color:#111827">
<h2 style="margin:0 0 12px">New Booking #<?php echo (int)$booking_id; ?> — <?php echo $package; ?></h2>
<table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;border-color:#e5e7eb">
<tr><td><strong>Package</strong></td><td><?php echo $package; ?></td></tr>
<tr><td><strong>Session</strong></td><td><?php echo $session; ?></td></tr>
<tr><td><strong>Extras</strong></td><td><?php echo $extrasHtml; ?></td></tr>
<tr><td><strong>Date</strong></td><td><?php echo $date; ?></td></tr>
<tr><td><strong>Total</strong></td><td><?php echo $total; ?></td></tr>
<tr><td><strong>Customer</strong></td><td><?php echo $name; ?> — <?php echo $email; ?> — <?php echo $phone; ?></td></tr>
<tr><td><strong>Message</strong></td><td><?php echo $msg ?: '—'; ?></td></tr>
</table>
<p><a href="<?php echo $adminUrl; ?>" style="display:inline-block;padding:10px 16px;background:#111827;color:#fff;text-decoration:none;border-radius:999px">View Booking</a></p>
</body></html>
