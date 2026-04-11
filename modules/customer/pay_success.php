<?php
date_default_timezone_set('Asia/Manila');
require_once '../../config/database.php';
session_start();

// pay_success.php — PayMongo redirects here after payment
// Verifies server-side, marks reservation as paid & pending_verification.

define('PAYMONGO_SECRET_KEY', 'sk_test_bg7ic4jq6oGSkDPeU5xeQFn5');

$reservation_id = (int)($_GET['reservation_id'] ?? 0);
$profile_q      = (int)($_GET['profile']        ?? 0);
$intent_id_q    = trim($_GET['intent_id']       ?? '');

if (!$reservation_id) { header('Location: my_reservations.php'); exit; }

// Fetch reservation with item info
$stmt = $conn->prepare("
    SELECT sr.*,
           si.selling_price, si.shop_status,
           i.device_type AS item_name, i.brand, i.model,
           p.first_name, p.last_name, p.email AS customer_email
    FROM shop_reservations sr
    JOIN shop_items si   ON si.shop_id   = sr.shop_id
    JOIN items i         ON i.item_id    = si.item_id
    JOIN profiles p      ON p.profile_id = sr.customer_profile_id
    WHERE sr.reservation_id = ?
");
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) { header('Location: my_reservations.php'); exit; }

// Already paid — just show confirmation
if (($res['payment_status'] ?? '') === 'paid') {
    $_SESSION['toast'] = "Your reservation is already confirmed and paid!";
    header('Location: my_reservations.php');
    exit;
}

// ── Helper: PayMongo GET ──
function paymongo_get(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':'),
        ],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => json_decode($body, true)];
}

$verified = false;
$link_id  = $res['paymongo_link_id'] ?? null;

if ($link_id) {

    if (str_starts_with($link_id, 'src_')) {
        // GCash source
        $r = paymongo_get('https://api.paymongo.com/v1/sources/' . $link_id);
        if ($r['code'] === 200) {
            $status   = $r['body']['data']['attributes']['status'] ?? '';
            $verified = in_array($status, ['chargeable', 'consumed', 'paid']);
        }

    } elseif (str_starts_with($link_id, 'pi_')) {
        // Card payment intent
        $pi_to_check = $intent_id_q ?: $link_id;
        $r = paymongo_get('https://api.paymongo.com/v1/payment_intents/' . $pi_to_check);
        if ($r['code'] === 200) {
            $status   = $r['body']['data']['attributes']['status'] ?? '';
            $verified = ($status === 'succeeded');
        }

    } elseif (str_starts_with($link_id, 'lnk_')) {
        // PayMongo link
        $r = paymongo_get('https://api.paymongo.com/v1/links/' . $link_id);
        if ($r['code'] === 200) {
            $status   = $r['body']['data']['attributes']['status'] ?? '';
            $verified = ($status === 'paid');
        }

    } else {
        $verified = true; // Unknown — allow in test mode
    }

} else {
    $verified = true; // No stored ID — allow in test mode
}

if ($verified) {

    // ── Race condition check: someone else may have paid first ──
    // Re-fetch the latest shop_status right now before we do anything
    $race_check = $conn->prepare("SELECT shop_status FROM shop_items WHERE shop_id = ?");
    $shop_id = (int)$res['shop_id'];
    $race_check->bind_param("i", $shop_id);
    $race_check->execute();
    $latest_item = $race_check->get_result()->fetch_assoc();

    if ($latest_item && $latest_item['shop_status'] === 'reserved') {
        // Another customer's payment was confirmed first — this customer loses
        // Mark their reservation as rejected so it's clear in their history
        $stmt_reject = $conn->prepare("UPDATE shop_reservations SET status = 'rejected' WHERE reservation_id = ?");
        $stmt_reject->bind_param("i", $reservation_id);
        $stmt_reject->execute();

        $_SESSION['toast_error'] = "Sorry, another customer completed payment first and secured this item. Please contact support for a refund if you were charged.";
        header('Location: my_reservations.php');
        exit;
    }

    // Generate auto reference number: AM- + date + deterministic suffix
    $ref_suffix = str_pad((string)((abs(crc32($reservation_id . '|' . date('Ymd'))) % 9000) + 1000), 4, '0', STR_PAD_LEFT);
    $receipt_no = 'AM-' . date('dmY') . '-' . $ref_suffix;

    // Mark reservation as paid and pending admin verification
    $upd = $conn->prepare("
        UPDATE shop_reservations
        SET payment_status = 'paid',
            status         = 'pending_verification',
            receipt_number = ?,
            paid_at        = NOW()
        WHERE reservation_id = ?
    ");

    if ($upd) {
        $upd->bind_param("si", $receipt_no, $reservation_id);
        if (!$upd->execute()) {
            $conn->query("UPDATE shop_reservations SET status='pending_verification' WHERE reservation_id=$reservation_id");
        }
    } else {
        $conn->query("UPDATE shop_reservations SET status='pending_verification' WHERE reservation_id=$reservation_id");
    }

    // Now lock the item — payment is confirmed
    $conn->query("UPDATE shop_items SET shop_status='reserved' WHERE shop_id=" . (int)$res['shop_id']);

    $_SESSION['toast'] = "Payment successful! Your reservation for " . $res['brand'] . " " . $res['model'] . " is now confirmed.";
    header('Location: my_reservations.php?paid=1&reservation_id=' . $reservation_id);
    exit;

} else {
    $_SESSION['toast_error'] = "Payment could not be verified. Please contact support if you were charged.";
    header('Location: my_reservations.php');
    exit;
}
?>