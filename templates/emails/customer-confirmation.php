<?php /** @var string $package @var string $session @var string $date @var string $total @var string $name @var string $extrasHtml */ ?>
<!doctype html>
<html><body style="font-family:Arial,sans-serif;color:#111827">
<h2 style="margin:0 0 8px">Hi <?php echo $name; ?> — we received your request 🤍</h2>
<p>Your session <strong><?php echo $package; ?> (<?php echo $session; ?>)</strong> for <strong><?php echo $date; ?></strong> is pending confirmation.</p>
<p><strong>Total:</strong> <?php echo $total; ?></p>
<p><strong>Extras:</strong> <?php echo $extrasHtml; ?></p>
<p>We will confirm within 24 hours. Reply to this email if you have questions.</p>
<p style="color:#6b7280;font-size:12px">This is an automated confirmation.</p>
</body></html>
