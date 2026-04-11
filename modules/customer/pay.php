<?php
date_default_timezone_set('Asia/Manila');
require_once '../../config/database.php';
session_start();

$profile_id = $_SESSION['profile_id'] ?? 0;
if (!$profile_id) {
    header('Location: ../../login.php');
    exit;
}

define('PAYMONGO_SECRET_KEY', 'sk_test_bg7ic4jq6oGSkDPeU5xeQFn5');
define('PAYMONGO_PUBLIC_KEY', 'pk_test_KRugwuNGnXVHLMg1bz7rjxbB');
define('BASE_URL', 'http://localhost:3000/modules/customer');

$reservation_id = (int)($_GET['reservation_id'] ?? 0);
if (!$reservation_id) { header('Location: my_reservations.php'); exit; }

$stmt = $conn->prepare("
    SELECT sr.*,
           si.selling_price,
           i.device_type AS item_name, i.brand, i.model,
           p.first_name, p.last_name, p.contact_number,
           p.email AS customer_email
    FROM shop_reservations sr
    JOIN shop_items si   ON si.shop_id  = sr.shop_id
    JOIN items i         ON i.item_id   = si.item_id
    JOIN profiles p      ON p.profile_id = sr.customer_profile_id
    WHERE sr.reservation_id = ? AND sr.customer_profile_id = ?
");
if (!$stmt) { die('Query error: ' . htmlspecialchars($conn->error)); }
$stmt->bind_param("ii", $reservation_id, $profile_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
    $_SESSION['toast_error'] = "Reservation not found.";
    header('Location: my_reservations.php'); exit;
}

if (($res['payment_status'] ?? '') === 'paid') {
    header('Location: my_reservations.php?msg=Already+paid');
    exit;
}

if (!in_array($res['status'] ?? '', ['pending_payment', 'pending'])) {
    $_SESSION['toast_error'] = "This reservation cannot be paid at this time.";
    header('Location: my_reservations.php'); exit;
}

function formatPhone(?string $raw): ?string {
    if (empty(trim($raw ?? ''))) return null;
    $digits = preg_replace('/\D/', '', $raw);
    if (strlen($digits) === 11 && $digits[0] === '0')           return '+63' . substr($digits, 1);
    if (strlen($digits) === 10)                                  return '+63' . $digits;
    if (strlen($digits) === 12 && substr($digits,0,2) === '63') return '+' . $digits;
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_method'])) {
    while (ob_get_level()) ob_end_clean();

    $method       = $_POST['pay_method'];
    $email        = trim($_POST['email'] ?? '');
    $name         = trim($_POST['name']  ?? '');
    $phone        = formatPhone($_POST['phone'] ?? '');
    $amount_cents = (int)(floatval($res['reservation_amount']) * 100);
    if ($amount_cents < 10000) $amount_cents = 10000;

    $success_url = BASE_URL . '/pay_success.php?reservation_id=' . $reservation_id . '&profile=' . $profile_id;
    $failed_url  = BASE_URL . '/pay_cancel.php?reservation_id='  . $reservation_id;

    $billing = ['name' => $name, 'email' => $email];
    if ($phone !== null) $billing['phone'] = $phone;

    $item_label = $res['brand'] . ' ' . $res['model'];

    if ($method === 'gcash') {
        $payload = ['data' => ['attributes' => [
            'amount'   => $amount_cents,
            'currency' => 'PHP',
            'type'     => 'gcash',
            'redirect' => ['success' => $success_url, 'failed' => $failed_url],
            'billing'  => $billing,
        ]]];

        $ch = curl_init('https://api.paymongo.com/v1/sources');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':'),
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);
        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $result = json_decode($response, true);

        if ($http_code === 200 && isset($result['data']['attributes']['redirect']['checkout_url'])) {
            $source_id = $result['data']['id'];
            $upd = $conn->prepare("UPDATE shop_reservations SET paymongo_link_id = ? WHERE reservation_id = ?");
            if ($upd) { $upd->bind_param("si", $source_id, $reservation_id); $upd->execute(); }
            header('Location: ' . $result['data']['attributes']['redirect']['checkout_url']); exit;
        } else {
            $error = $result['errors'][0]['detail'] ?? 'GCash payment gateway error.';
            $_SESSION['toast_error'] = 'PayMongo Error: ' . $error;
            header('Location: pay.php?reservation_id=' . $reservation_id); exit;
        }

    } elseif ($method === 'card') {

        $card_number = preg_replace('/\s+/', '', trim($_POST['card_number'] ?? ''));
        $exp_month   = (int)($_POST['exp_month'] ?? 0);
        $exp_year    = (int)($_POST['exp_year']  ?? 0);
        $cvc         = trim($_POST['cvc'] ?? '');

        if (!$card_number || !$exp_month || !$exp_year || !$cvc) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Card details are incomplete.']);
            exit;
        }

        $pm_payload = ['data' => ['attributes' => [
            'type'    => 'card',
            'details' => [
                'card_number' => $card_number,
                'exp_month'   => $exp_month,
                'exp_year'    => $exp_year,
                'cvc'         => $cvc,
            ],
            'billing' => $billing,
        ]]];

        $ch = curl_init('https://api.paymongo.com/v1/payment_methods');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode(PAYMONGO_PUBLIC_KEY . ':'),
            ],
            CURLOPT_POSTFIELDS => json_encode($pm_payload),
        ]);
        $pm_resp = curl_exec($ch);
        $pm_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $pm = json_decode($pm_resp, true);

        if ($pm_code !== 200 || !isset($pm['data']['id'])) {
            $error = $pm['errors'][0]['detail'] ?? 'Could not tokenise card.';
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $error]);
            exit;
        }

        $pm_id = $pm['data']['id'];

        $pi_payload = ['data' => ['attributes' => [
            'amount'                 => $amount_cents,
            'currency'               => 'PHP',
            'payment_method_allowed' => ['card'],
            'description'            => 'Downpayment for ' . $item_label . ' — ArMaTech',
            'statement_descriptor'   => 'ARMATECH',
            'capture_type'           => 'automatic',
        ]]];

        $ch = curl_init('https://api.paymongo.com/v1/payment_intents');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':'),
            ],
            CURLOPT_POSTFIELDS => json_encode($pi_payload),
        ]);
        $pi_resp = curl_exec($ch);
        $pi_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $pi = json_decode($pi_resp, true);

        if ($pi_code !== 200 || !isset($pi['data']['id'])) {
            $error = $pi['errors'][0]['detail'] ?? 'Could not create payment intent.';
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $error]);
            exit;
        }

        $intent_id  = $pi['data']['id'];
        $client_key = $pi['data']['attributes']['client_key'];

        $upd = $conn->prepare("UPDATE shop_reservations SET paymongo_link_id = ? WHERE reservation_id = ?");
        if ($upd) { $upd->bind_param("si", $intent_id, $reservation_id); $upd->execute(); }

        $attach_payload = ['data' => ['attributes' => [
            'payment_method' => $pm_id,
            'client_key'     => $client_key,
            'return_url'     => $success_url . '&intent_id=' . $intent_id,
        ]]];

        $ch = curl_init("https://api.paymongo.com/v1/payment_intents/{$intent_id}/attach");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':'),
            ],
            CURLOPT_POSTFIELDS => json_encode($attach_payload),
        ]);
        $att_resp = curl_exec($ch);
        $att_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $att = json_decode($att_resp, true);

        if ($att_code !== 200) {
            $error = $att['errors'][0]['detail'] ?? 'Could not process payment.';
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $error]);
            exit;
        }

        $intent_status = $att['data']['attributes']['status'];
        $next_action   = $att['data']['attributes']['next_action'] ?? null;

        header('Content-Type: application/json');
        if ($intent_status === 'succeeded') {
            echo json_encode(['status' => 'succeeded', 'redirect' => $success_url . '&intent_id=' . $intent_id]);
        } elseif ($next_action && isset($next_action['redirect']['url'])) {
            echo json_encode(['status' => 'awaiting_next_action', 'redirect' => $next_action['redirect']['url']]);
        } elseif ($intent_status === 'awaiting_payment_method') {
            echo json_encode(['status' => 'error', 'message' => 'Card was declined. Please try the test card shown.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Payment failed: ' . $intent_status]);
        }
        exit;

    } else {
        $_SESSION['toast_error'] = 'Invalid payment method.';
        header('Location: pay.php?reservation_id=' . $reservation_id); exit;
    }
}

$pre_name  = htmlspecialchars(trim(($res['first_name'] ?? '') . ' ' . ($res['last_name'] ?? '')));
$pre_email = htmlspecialchars($res['customer_email'] ?? '');
$pre_phone = htmlspecialchars($res['contact_number'] ?? '');
$amount    = floatval($res['reservation_amount']);
$amount_fmt = '₱' . number_format($amount, 2);
$item_label = htmlspecialchars($res['brand'] . ' ' . $res['model']);
$item_type  = htmlspecialchars($res['item_name'] ?? '');

$ref_suffix = str_pad((string)((abs(crc32($reservation_id . '|' . date('Ymd'))) % 9000) + 1000), 4, '0', STR_PAD_LEFT);
$reference_number = 'AM-' . date('dmY') . '-' . $ref_suffix;

// Deadline = 3 days from reservation creation
$deadline_date = date('F d, Y', strtotime($res['created_at'] . ' +3 days'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Pay · ArMaTech</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --primary: #1a56db; --primary2: #1e40af; --green: #16a34a;
      --muted: #6b7280; --border: #e5e7eb; --bg: #f9fafb;
      --white: #ffffff; --text: #111827; --radius: 12px; --red: #ef4444;
    }
    body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }

    .toast-container { position:fixed; top:1rem; left:50%; transform:translateX(-50%); width:90%; max-width:500px; z-index:9999; display:none; }
    .toast { padding:1rem 1.5rem; border-radius:10px; color:#fff; font-weight:600; font-size:0.9rem; box-shadow:0 4px 12px rgba(0,0,0,0.15); display:flex; align-items:center; gap:0.75rem; }
    .toast.error   { background:var(--red); }
    .toast.success { background:var(--green); }

    .checkout-header { background:linear-gradient(135deg,#0f2027 0%,#203a43 50%,#2c5364 100%); padding:1.5rem 1rem 1.2rem; text-align:center; }
    .checkout-header h1 { font-family:'Plus Jakarta Sans',sans-serif; font-size:1.6rem; font-weight:800; color:#fff; letter-spacing:0.04em; }
    .checkout-header .tagline { font-size:0.72rem; color:rgba(255,255,255,0.55); margin-top:0.15rem; letter-spacing:0.08em; text-transform:uppercase; }

    .ref-bar { background:var(--white); border-bottom:1px solid var(--border); padding:0.6rem 1rem; display:flex; align-items:center; gap:0.6rem; font-size:0.78rem; color:var(--muted); flex-wrap:wrap; }
    .ref-num { font-weight:700; color:var(--text); font-family:monospace; }
    .method-tag { background:rgba(26,86,219,0.1); color:var(--primary); border:1px solid rgba(26,86,219,0.2); border-radius:6px; padding:0.15rem 0.5rem; font-size:0.7rem; font-weight:700; }

    .stepper { background:var(--white); border-bottom:1px solid var(--border); padding:0.9rem 1rem; display:flex; align-items:center; justify-content:center; overflow-x:auto; }
    .step-item { display:flex; align-items:center; gap:0.4rem; white-space:nowrap; }
    .step-circle { width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.72rem; font-weight:700; flex-shrink:0; transition:all 0.3s; }
    .step-circle.done    { background:var(--primary); color:#fff; }
    .step-circle.active  { background:var(--primary); color:#fff; box-shadow:0 0 0 3px rgba(26,86,219,0.2); }
    .step-circle.pending { background:transparent; border:1.5px solid #d1d5db; color:#9ca3af; }
    .step-label { font-size:0.75rem; font-weight:600; color:var(--muted); }
    .step-label.active { color:var(--text); font-weight:700; }
    .step-line { flex:1; height:2px; background:#e5e7eb; margin:0 0.4rem; min-width:24px; max-width:60px; }
    .step-line.done { background:var(--primary); }

    .checkout-body { max-width:600px; margin:0 auto; padding:1.2rem 1rem 5rem; }
    .section-heading { font-family:'Plus Jakarta Sans',sans-serif; font-size:1rem; font-weight:800; color:var(--text); margin-bottom:0.8rem; }

    .amount-display { text-align:center; margin-bottom:1.5rem; }
    .amount-desc  { font-size:0.85rem; color:var(--muted); margin-bottom:0.3rem; }
    .amount-big   { font-family:'Plus Jakarta Sans',sans-serif; font-size:2.8rem; font-weight:800; color:var(--text); letter-spacing:-0.03em; line-height:1; }
    .amount-big .currency { font-size:1.5rem; vertical-align:top; margin-top:0.4rem; display:inline-block; }
    .amount-label { font-size:0.72rem; color:var(--muted); margin-top:0.3rem; }
    .amount-sublabel { display:inline-block; margin-top:0.5rem; background:rgba(26,86,219,0.08); color:var(--primary); border:1px solid rgba(26,86,219,0.2); border-radius:20px; padding:0.25rem 0.8rem; font-size:0.72rem; font-weight:700; }
  .inline-icon { width:0.95em; height:0.95em; vertical-align:-0.12em; margin-right:0.25rem; }
  .inline-icon-lg { width:1.1em; height:1.1em; vertical-align:-0.14em; margin-right:0.3rem; }

    .method-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:0.6rem; margin-bottom:1.5rem; max-width:400px; margin-left:auto; margin-right:auto; }
    .method-card { border:2px solid var(--border); border-radius:var(--radius); padding:1rem 0.5rem; display:flex; flex-direction:column; align-items:center; gap:0.4rem; cursor:pointer; transition:all 0.2s; background:var(--white); position:relative; }
    .method-card:hover { border-color:var(--primary); }
    .method-card.selected { border-color:var(--primary); background:rgba(26,86,219,0.05); box-shadow:0 0 0 1px var(--primary); }
    .method-card .check-badge { position:absolute; top:-7px; right:-7px; width:20px; height:20px; background:var(--primary); border-radius:50%; display:none; align-items:center; justify-content:center; }
    .method-card.selected .check-badge { display:flex; }
    .method-icon  { width:48px; height:48px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; }
    .method-name  { font-size:0.78rem; font-weight:600; color:var(--text); text-align:center; }
    .method-sub   { font-size:0.65rem; color:var(--muted); text-align:center; }
    .logo-gcash   { background:#00a3e0; color:#fff; font-weight:800; font-size:1.2rem; }
    .logo-card    { background:linear-gradient(135deg,#1a56db,#7e3af2); color:#fff; }

    .card-fields { display:none; margin-bottom:1rem; }
    .card-fields.visible { display:block; }
    .card-preview {
      background:linear-gradient(135deg,#0f2027 0%,#203a43 50%,#2c5364 100%);
      border-radius:16px; padding:1.4rem 1.5rem; margin-bottom:1rem;
      color:#fff; position:relative; overflow:hidden; min-height:140px;
      box-shadow:0 8px 24px rgba(15,32,39,0.4);
    }
    .card-preview::before { content:''; position:absolute; top:-40px; right:-30px; width:130px; height:130px; border-radius:50%; background:rgba(255,255,255,0.06); }
    .card-preview::after  { content:''; position:absolute; bottom:-50px; right:30px; width:160px; height:160px; border-radius:50%; background:rgba(255,255,255,0.04); }
    .card-chip { width:34px; height:26px; background:rgba(255,255,255,0.25); border-radius:4px; margin-bottom:0.9rem; }
    .card-num-display { font-family:'Plus Jakarta Sans',sans-serif; font-size:1.1rem; font-weight:700; letter-spacing:0.2em; margin-bottom:0.7rem; }
    .card-bottom { display:flex; justify-content:space-between; align-items:flex-end; }
    .card-meta-label { font-size:0.58rem; opacity:0.6; text-transform:uppercase; letter-spacing:0.08em; }
    .card-meta-value { font-size:0.82rem; font-weight:600; }
    .card-brand { font-family:'Plus Jakarta Sans',sans-serif; font-size:1rem; font-weight:800; opacity:0.9; }
    .test-badge { display:inline-flex; align-items:center; gap:0.35rem; background:#fef3c7; border:1px solid #fbbf24; border-radius:6px; padding:0.3rem 0.7rem; font-size:0.72rem; color:#92400e; font-weight:600; margin-bottom:0.8rem; }
    .card-form { display:grid; gap:0.75rem; }
    .card-row2  { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; }
    .cf-label { font-size:0.72rem; font-weight:700; color:var(--text); display:block; margin-bottom:0.3rem; }
    .cf-input { width:100%; padding:0.68rem 0.9rem; border:1.5px solid var(--border); border-radius:8px; font-family:'DM Sans',sans-serif; font-size:0.92rem; color:var(--text); background:var(--white); outline:none; transition:border-color 0.2s,box-shadow 0.2s; }
    .cf-input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(26,86,219,0.12); }
    .cf-input.ok { background:#f0fdf4; border-color:#86efac; color:#166534; font-weight:600; }
    .error-banner { background:#fef2f2; border:1px solid #fca5a5; border-radius:var(--radius); padding:0.65rem 0.9rem; font-size:0.8rem; color:#991b1b; margin-bottom:1rem; display:none; }
    .error-banner.visible { display:block; }

    .form-row   { display:grid; grid-template-columns:1fr 1fr; gap:0.8rem; margin-bottom:1rem; }
    .form-group { margin-bottom:1rem; }
    .form-label { display:block; font-size:0.75rem; font-weight:700; color:var(--text); margin-bottom:0.35rem; }
    .form-label .req { color:#ef4444; }
    .form-input { width:100%; padding:0.7rem 0.9rem; border:1.5px solid var(--border); border-radius:var(--radius); font-family:'DM Sans',sans-serif; font-size:0.88rem; color:var(--text); background:var(--white); outline:none; transition:border-color 0.2s,box-shadow 0.2s; }
    .form-input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(26,86,219,0.1); }

    .summary-block { background:var(--white); border:1.5px solid var(--border); border-radius:var(--radius); overflow:hidden; margin-bottom:1.2rem; }
    .summary-block-title { font-weight:800; font-size:0.88rem; color:var(--text); padding:0.7rem 0.9rem 0.3rem; font-family:'Plus Jakarta Sans',sans-serif; }
    .summary-table { width:100%; border-collapse:collapse; font-size:0.85rem; margin-bottom:1.2rem; }
    .summary-table td { padding:0.7rem 0.9rem; border-bottom:1px solid var(--border); }
    .summary-table td:first-child { color:var(--muted); font-weight:500; width:40%; }
    .summary-table td:last-child  { color:var(--text); font-weight:600; }

    .privacy-note { display:flex; align-items:center; gap:0.5rem; margin-bottom:1.2rem; font-size:0.75rem; color:var(--muted); }
    .privacy-note input[type=checkbox] { width:16px; height:16px; accent-color:var(--primary); flex-shrink:0; }
    .privacy-note a { color:var(--primary); text-decoration:none; font-weight:600; }

    .nav-row { display:flex; align-items:center; gap:0.8rem; padding:1rem; position:fixed; bottom:0; left:0; right:0; background:var(--white); border-top:1px solid var(--border); z-index:10; }
    .btn-back { padding:0.7rem 1.4rem; border:1.5px solid var(--border); border-radius:50px; background:var(--white); font-family:'DM Sans',sans-serif; font-size:0.85rem; font-weight:600; color:var(--muted); cursor:pointer; transition:all 0.2s; }
    .btn-back:hover { border-color:var(--primary); color:var(--primary); }
    .btn-next { flex:1; padding:0.8rem 1.4rem; border-radius:50px; border:none; background:var(--primary); color:#fff; font-family:'DM Sans',sans-serif; font-size:0.9rem; font-weight:700; cursor:pointer; transition:all 0.2s; box-shadow:0 4px 14px rgba(26,86,219,0.3); }
    .btn-next:hover:not(:disabled) { background:var(--primary2); transform:translateY(-1px); }
    .btn-next:disabled { background:#d1d5db; color:#9ca3af; box-shadow:none; cursor:not-allowed; }

    .powered-by { text-align:center; font-size:0.7rem; color:#9ca3af; display:flex; align-items:center; justify-content:center; gap:0.3rem; margin-top:1.5rem; }
    .checkout-step { display:none; }
    .checkout-step.active { display:block; }

    @keyframes spin { to { transform:rotate(360deg); } }

    /* Policy Modal */
    .policy-modal { display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); z-index:9998; }
    .policy-modal.visible { display:flex; align-items:center; justify-content:center; }
    .policy-modal-content { background:var(--white); border-radius:16px; max-width:680px; width:95vw; max-height:88vh; overflow-y:auto; padding:2rem; box-shadow:0 24px 64px rgba(0,0,0,0.3); position:relative; }
    .policy-modal-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.2rem; padding-bottom:1rem; border-bottom:2px solid var(--border); }
    .policy-modal-header h2 { font-family:'Plus Jakarta Sans',sans-serif; font-size:1.3rem; font-weight:800; color:var(--text); margin:0; line-height:1.3; }
    .policy-modal-close { background:none; border:none; font-size:1.8rem; color:var(--muted); cursor:pointer; padding:0; width:32px; height:32px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .policy-modal-close:hover { color:var(--text); }
    .policy-modal-body { font-size:0.88rem; color:var(--text); line-height:1.75; }
    .policy-modal-body h3 { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:0.9rem; margin-top:1.4rem; margin-bottom:0.5rem; color:var(--text); text-transform:uppercase; letter-spacing:0.04em; }
    .policy-modal-body p { margin-bottom:0.75rem; color:#374151; }
    .policy-modal-body ul { margin:0.5rem 0 0.75rem 1.4rem; }
    .policy-modal-body li { margin-bottom:0.4rem; color:#374151; }
    .law-block { background:#f8faff; border-left:4px solid var(--primary); border-radius:0 8px 8px 0; padding:0.9rem 1.1rem; margin:0.8rem 0; }
    .law-block .law-title { font-weight:800; font-size:0.82rem; color:var(--primary); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.3rem; }
    .law-block .law-text { font-size:0.82rem; color:#4b5563; font-style:italic; margin-bottom:0.4rem; }
    .law-block .law-meaning { font-size:0.82rem; color:#111827; font-weight:600; }
    .warning-box { background:#fff7ed; border:1.5px solid #fed7aa; border-radius:10px; padding:1rem 1.2rem; margin:1rem 0; display:flex; gap:0.8rem; align-items:flex-start; }
    .warning-box .wi { font-size:1.3rem; flex-shrink:0; margin-top:0.1rem; }
    .warning-box .wt { font-size:0.83rem; color:#92400e; line-height:1.6; }
    .warning-box .wt strong { color:#78350f; }
    .deadline-box { background:linear-gradient(135deg,#fef2f2,#fff5f5); border:2px solid #fca5a5; border-radius:10px; padding:1rem 1.2rem; margin:1rem 0; text-align:center; }
    .deadline-box .dl-label { font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; color:#991b1b; font-weight:700; margin-bottom:0.3rem; }
    .deadline-box .dl-date { font-family:'Plus Jakarta Sans',sans-serif; font-size:1.2rem; font-weight:800; color:#dc2626; }
    .deadline-box .dl-sub { font-size:0.75rem; color:#b91c1c; margin-top:0.2rem; }
    .policy-modal-footer { display:flex; gap:0.8rem; margin-top:1.5rem; padding-top:1rem; border-top:1.5px solid var(--border); }
    .policy-modal-footer button { flex:1; padding:0.8rem 1.2rem; border:none; border-radius:50px; font-weight:700; cursor:pointer; font-family:'DM Sans',sans-serif; font-size:0.88rem; }
    .btn-policy-close { background:var(--border); color:var(--muted); }
    .btn-policy-agree { background:var(--primary); color:#fff; box-shadow:0 4px 12px rgba(26,86,219,0.3); }

    @media(max-width:600px) {
      .policy-modal-content { padding:1.4rem; }
      .form-row { grid-template-columns:1fr; }
    }
  </style>
</head>
<body>

<div id="toast-container" class="toast-container">
  <div id="toast" class="toast error"><span id="toast-msg"></span></div>
</div>

<div class="checkout-header">
  <h1>ArMaTech</h1>
  <div class="tagline">Secure Online Payment</div>
</div>

<div class="ref-bar">
  <span>Reference:</span>
  <span class="ref-num"><?= htmlspecialchars($reference_number) ?></span>
  <span class="method-tag" id="method-display" style="display:none;"></span>
</div>

<div class="stepper">
  <div class="step-item"><div class="step-circle active" id="sc1">1</div><span class="step-label active" id="sl1">Method</span></div>
  <div class="step-line" id="line1"></div>
  <div class="step-item"><div class="step-circle pending" id="sc2">2</div><span class="step-label" id="sl2">Billing</span></div>
  <div class="step-line" id="line2"></div>
  <div class="step-item"><div class="step-circle pending" id="sc3">3</div><span class="step-label" id="sl3">Summary</span></div>
  <div class="step-line" id="line3"></div>
  <div class="step-item"><div class="step-circle pending" id="sc4">4</div><span class="step-label" id="sl4">Payment</span></div>
</div>

<div class="checkout-body">

  <!-- STEP 1 — Method -->
  <div class="checkout-step active" id="step1">
    <div class="amount-display">
      <div class="amount-desc">Downpayment for <?= $item_label ?> <span style="color:var(--muted);font-size:0.78rem;">(<?= $item_type ?>)</span></div>
      <div class="amount-big"><span class="currency">₱</span><?= number_format($amount, 2) ?></div>
      <div class="amount-label">10% Downpayment · Amount to Pay Now</div>
  <span class="amount-sublabel"><svg class="inline-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8l9-5 9 5-9 5-9-5z"></path><path d="M3 8v8l9 5 9-5V8"></path><path d="M12 13v8"></path></svg>Reserve in-store pickup</span>
    </div>

    <div class="section-heading">SELECT PAYMENT METHOD</div>

    <div class="method-grid">
      <div class="method-card" data-method="gcash" data-label="GCash" onclick="selectMethod(this)">
        <div class="check-badge"><svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
        <div class="method-icon logo-gcash">G</div>
        <div class="method-name">GCash</div>
        <div class="method-sub">e-Wallet</div>
      </div>
      <div class="method-card" data-method="card" data-label="Credit/Debit Card" onclick="selectMethod(this)">
        <div class="check-badge"><svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
  <div class="method-icon logo-card"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="5" width="19" height="14" rx="2.5"></rect><path d="M2.5 10h19"></path><path d="M7 15h3"></path></svg></div>
        <div class="method-name">Credit/Debit Card</div>
        <div class="method-sub">Visa / Mastercard</div>
      </div>
    </div>

    <div class="card-fields" id="card-fields">
  <div class="test-badge"><svg class="inline-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 2v5l-4.5 7.8A4 4 0 0 0 9 21h6a4 4 0 0 0 3.5-6.2L14 7V2"></path><path d="M8 2h8"></path><path d="M8.5 13h7"></path></svg>Test Mode — PH test card pre-filled</div>
      <div class="card-preview">
        <div class="card-chip"></div>
        <div class="card-num-display" id="preview-number">4009 9300 0000 1421</div>
        <div class="card-bottom">
          <div>
            <div class="card-meta-label">Card Holder</div>
            <div class="card-meta-value" id="preview-name"><?= $pre_name ?: 'CARDHOLDER' ?></div>
          </div>
          <div>
            <div class="card-meta-label">Expires</div>
            <div class="card-meta-value" id="preview-expiry">12 / 28</div>
          </div>
          <div class="card-brand" id="preview-brand">VISA</div>
        </div>
      </div>
      <div id="card-error" class="error-banner"></div>
      <div class="card-form">
        <div>
          <label class="cf-label">Card Number</label>
          <input class="cf-input ok" id="cf-number" type="text" inputmode="numeric" value="4009 9300 0000 1421" maxlength="19" oninput="fmtNum(this)" onkeyup="livePreview()"/>
        </div>
        <div class="card-row2">
          <div>
            <label class="cf-label">Expiry (MM / YY)</label>
            <input class="cf-input ok" id="cf-expiry" type="text" inputmode="numeric" value="12 / 28" maxlength="7" oninput="fmtExp(this)" onkeyup="livePreview()"/>
          </div>
          <div>
            <label class="cf-label">CVV / CVC</label>
            <input class="cf-input ok" id="cf-cvc" type="text" inputmode="numeric" value="123" maxlength="4" oninput="this.value=this.value.replace(/\D/g,'')"/>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- STEP 2 — Billing -->
  <div class="checkout-step" id="step2">
    <div class="section-heading">Customer Information</div>
    <div class="form-row">
      <div>
        <label class="form-label"><span class="req">*</span> E-mail</label>
        <input type="email" class="form-input" id="inp-email" value="<?= $pre_email ?>" placeholder="you@email.com"/>
      </div>
      <div>
        <label class="form-label">Phone <span style="color:var(--muted);font-weight:400;">(optional)</span></label>
        <input type="tel" class="form-input" id="inp-phone" value="<?= $pre_phone ?>" placeholder="09XXXXXXXXX"/>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label"><span class="req">*</span> Full Name</label>
      <input type="text" class="form-input" id="inp-name" value="<?= $pre_name ?>" placeholder="Full Name"/>
    </div>
  </div>

  <!-- STEP 3 — Summary -->
  <div class="checkout-step" id="step3">
    <div class="summary-block">
      <div class="summary-block-title">Payment Summary</div>
      <table class="summary-table">
        <tr><td>Item</td><td><?= $item_label ?></td></tr>
        <tr><td>Category</td><td><?= $item_type ?></td></tr>
        <tr><td>Downpayment (10%)</td><td style="font-size:1rem;color:var(--primary);"><?= $amount_fmt ?></td></tr>
        <tr><td>Method</td><td id="sum-method"></td></tr>
        <tr><td>Reference</td><td style="font-family:monospace;font-size:0.8rem;"><?= htmlspecialchars($reference_number) ?></td></tr>
        <tr><td>Claim Deadline</td><td style="color:#dc2626;font-weight:700;"><?= $deadline_date ?></td></tr>
      </table>
    </div>
    <div class="summary-block">
      <div class="summary-block-title">Billing Details</div>
      <table class="summary-table">
        <tr><td>Name</td><td id="sum-name"></td></tr>
        <tr><td>E-mail</td><td id="sum-email"></td></tr>
        <tr><td>Phone</td><td id="sum-phone"></td></tr>
      </table>
    </div>
    <div class="privacy-note">
      <input type="checkbox" id="agree-chk"/>
      <label for="agree-chk">I have read and agreed to ArMaTech's <a href="#" onclick="openPolicyModal(event)">Reservation & Payment Policy</a>, including the 3-day claim deadline and non-refundable downpayment terms.</label>
    </div>
  </div>

  <!-- STEP 4 — Processing -->
  <div class="checkout-step" id="step4">
    <div style="text-align:center;padding:3rem 1rem;">
      <div style="width:60px;height:60px;border:4px solid rgba(26,86,219,0.2);border-top-color:var(--primary);border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 1.2rem;"></div>
      <div style="font-family:'Plus Jakarta Sans',sans-serif;font-size:1.1rem;font-weight:800;margin-bottom:0.5rem;" id="processing-label">Processing…</div>
      <div style="font-size:0.82rem;color:var(--muted);">Please wait and do not close this page.</div>
    </div>
  </div>

  <div class="powered-by">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    Secured by ArMaTech · Powered by PayMongo
  </div>
</div>

<!-- ═══════════════════════════════════════════════════
     RESERVATION & PAYMENT POLICY MODAL
     Based on the Civil Code of the Philippines
════════════════════════════════════════════════════ -->
<div class="policy-modal" id="policy-modal">
  <div class="policy-modal-content">
    <div class="policy-modal-header">
  <h2><svg class="inline-icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="7" y="3" width="10" height="18" rx="2"></rect><path d="M9 7h6"></path><path d="M9 11h6"></path><path d="M9 15h4"></path></svg>Reservation &amp; Payment Policy</h2>
      <button class="policy-modal-close" onclick="closePolicyModal()">&times;</button>
    </div>
    <div class="policy-modal-body">

      <p>By proceeding with this reservation, you acknowledge and agree to the following terms grounded in the <strong>Civil Code of the Philippines</strong>.</p>

      <div class="warning-box">
  <div class="wi"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg></div>
        <div class="wt">
          <strong>Important:</strong> Your 10% downpayment of <strong><?= $amount_fmt ?></strong> is <strong>non-refundable</strong>. You must complete full payment and claim the item within <strong>3 days</strong> from the date your reservation is approved. Failure to do so will result in automatic forfeiture of your downpayment and cancellation of your reservation.
        </div>
      </div>

      <div class="deadline-box">
  <div class="dl-label"><svg class="inline-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="13" r="8"></circle><path d="M12 9v4l2 2"></path><path d="M9 3h6"></path></svg>Your Claim Deadline</div>
        <div class="dl-date"><?= $deadline_date ?></div>
        <div class="dl-sub">3 days from reservation date · Balance must be paid in-store</div>
      </div>

      <h3>Legal Basis</h3>

      <div class="law-block">
        <div class="law-title">Article 1306 — Freedom of Contract</div>
        <div class="law-text">"The contracting parties may establish such stipulations, clauses, terms, and conditions as they may deem convenient, provided they are not contrary to law, morals, good customs, public order, or public policy."</div>
  <div class="law-meaning"><svg class="inline-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"></path></svg>What this means: ArMaTech may legally set a 3-day payment window and a non-refundable downpayment policy, as these terms are clearly disclosed and agreed upon before payment.</div>
      </div>

      <div class="law-block">
        <div class="law-title">Article 1159 — Obligations from Contracts</div>
        <div class="law-text">"Obligations arising from contracts have the force of law between the contracting parties and should be complied with in good faith."</div>
  <div class="law-meaning"><svg class="inline-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"></path></svg>What this means: Once you agree to these terms and pay the downpayment, you are legally bound to complete payment within the agreed period.</div>
      </div>

      <div class="law-block">
        <div class="law-title">Articles 1191 &amp; 1192 — Rescission for Breach</div>
        <div class="law-text">"The power to rescind obligations is implied in reciprocal ones, in case one of the obligors should not comply with what is incumbent upon him."</div>
  <div class="law-meaning"><svg class="inline-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"></path></svg>What this means: If you fail to complete payment and claim the item within 3 days, ArMaTech has the legal right to cancel your reservation and return the item to the shop.</div>
      </div>

      <div class="law-block">
        <div class="law-title">Article 1226 — Penal Clauses</div>
        <div class="law-text">"In obligations with a penal clause, the penalty shall substitute the indemnity for damages and the payment of interests in case of non-compliance."</div>
  <div class="law-meaning"><svg class="inline-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"></path></svg>What this means: Your non-refundable downpayment functions as a penalty clause — it compensates ArMaTech if you back out or fail to pay the balance on time.</div>
      </div>

      <h3>Summary of Your Obligations</h3>
      <ul>
        <li>Pay the <strong>10% downpayment (<?= $amount_fmt ?>)</strong> now via PayMongo to secure your reservation.</li>
        <li>Visit the ArMaTech branch within <strong>3 days</strong> to pay the remaining balance.</li>
        <li>If you fail to claim within 3 days, your reservation is <strong>automatically cancelled</strong> and the downpayment is <strong>forfeited</strong>.</li>
        <li>The item will be <strong>re-listed in the shop</strong> for other customers.</li>
      </ul>

      <h3>Data Privacy</h3>
      <p>ArMaTech collects your name, email, phone number, and payment reference for reservation processing only. Card details are handled exclusively by <strong>PayMongo</strong> (PCI DSS compliant) and are never stored on ArMaTech servers.</p>


    </div>
    <div class="policy-modal-footer">
      <button class="btn-policy-close" onclick="closePolicyModal()">Close</button>
  <button class="btn-policy-agree" onclick="agreePolicyModal()"><svg class="inline-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"></path></svg>I Understand &amp; Agree</button>
    </div>
  </div>
</div>

<!-- Hidden GCash POST form -->
<form method="POST" id="pay-form" action="pay.php?reservation_id=<?= $reservation_id ?>">
  <input type="hidden" name="pay_method" id="f-method"/>
  <input type="hidden" name="email"      id="f-email"/>
  <input type="hidden" name="name"       id="f-name"/>
  <input type="hidden" name="phone"      id="f-phone"/>
</form>

<div class="nav-row">
  <button class="btn-back" id="btn-back" onclick="goBack()" style="display:none;">Back</button>
  <button class="btn-next" id="btn-next" onclick="goNext()" disabled>Next</button>
</div>

<script>
  let step = 1, selMethod = null, selLabel = null;

  function showToast(msg, type='error') {
    const c = document.getElementById('toast-container');
    document.getElementById('toast').className = 'toast ' + type;
    document.getElementById('toast-msg').textContent = msg;
    c.style.display = 'block';
    setTimeout(() => c.style.display='none', 6000);
  }
  <?php if (isset($_SESSION['toast_error'])): ?>
    showToast(<?= json_encode($_SESSION['toast_error']) ?>, 'error');
    <?php unset($_SESSION['toast_error']); ?>
  <?php endif; ?>

  function fmtNum(el) {
    let v = el.value.replace(/\D/g,'').substring(0,16);
    el.value = v.replace(/(.{4})/g,'$1 ').trim();
    livePreview();
  }
  function fmtExp(el) {
    let v = el.value.replace(/\D/g,'').substring(0,4);
    el.value = v.length >= 3 ? v.slice(0,2) + ' / ' + v.slice(2) : v;
    livePreview();
  }
  function livePreview() {
    const raw = document.getElementById('cf-number').value.replace(/\s/g,'');
    document.getElementById('preview-number').textContent = raw ? raw.replace(/(.{4})/g,'$1 ').trim() : '•••• •••• •••• ••••';
    document.getElementById('preview-expiry').textContent = document.getElementById('cf-expiry').value || '•• / ••';
    let brand = 'CARD';
    if (/^4/.test(raw)) brand = 'VISA';
    else if (/^5/.test(raw)) brand = 'MASTERCARD';
    document.getElementById('preview-brand').textContent = brand;
  }

  function selectMethod(el) {
    document.querySelectorAll('.method-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    selMethod = el.dataset.method; selLabel = el.dataset.label;
    document.getElementById('method-display').textContent   = selLabel;
    document.getElementById('method-display').style.display = '';
    document.getElementById('card-fields').classList.toggle('visible', selMethod === 'card');
    document.getElementById('btn-next').disabled = false;
  }

  function updateStepper(n) {
    for (let i=1;i<=4;i++) {
      const sc = document.getElementById('sc'+i), sl = document.getElementById('sl'+i);
      sc.className = 'step-circle '+(i<n?'done':i===n?'active':'pending');
      sl.className = 'step-label'+(i===n?' active':'');
      if(i<n) sc.innerHTML='<svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
      else sc.textContent=i;
      if(i<4) document.getElementById('line'+i).className='step-line '+(i<n?'done':'');
    }
  }

  function showStep(n) {
    document.querySelectorAll('.checkout-step').forEach(s=>s.classList.remove('active'));
    document.getElementById('step'+n).classList.add('active');
    updateStepper(n);
    const bb=document.getElementById('btn-back'), bn=document.getElementById('btn-next');
    bb.style.display = n>1?'':'none';
    if(n===1){ bn.disabled=!selMethod; bn.textContent='Next'; bn.style.display=''; }
    else if(n===2){ bn.disabled=false; bn.textContent='Next'; bn.style.display=''; }
    else if(n===3){
      bn.disabled=true; bn.textContent='Confirm & Pay →'; bn.style.display='';
      const chk=document.getElementById('agree-chk');
      chk.checked=false; chk.onchange=()=>{ bn.disabled=!chk.checked; };
    } else { bn.style.display='none'; bb.style.display='none'; }
  }

  function goBack() { if(step>1){ step--; showStep(step); } }

  function goNext() {
    if(step===1) {
      if(!selMethod) return;
      step=2; showStep(2);
    } else if(step===2) {
      const email=document.getElementById('inp-email').value.trim();
      const name =document.getElementById('inp-name').value.trim();
      if(!email||!name){ alert('Please fill in your email and name.'); return; }
      document.getElementById('sum-method').textContent = selLabel;
      document.getElementById('sum-name').textContent   = name;
      document.getElementById('sum-email').textContent  = email;
      document.getElementById('sum-phone').textContent  = document.getElementById('inp-phone').value.trim()||'—';
      document.getElementById('preview-name').textContent = name||'CARDHOLDER';
      step=3; showStep(3);
    } else if(step===3) {
      submitPayment();
    }
  }

  async function submitPayment() {
    showStep(4);
    const email = document.getElementById('inp-email').value.trim();
    const name  = document.getElementById('inp-name').value.trim();
    const phone = document.getElementById('inp-phone').value.trim();

    if (selMethod === 'card') {
      document.getElementById('processing-label').textContent = 'Tokenising card…';
      const rawNumber = document.getElementById('cf-number').value.replace(/\s/g,'');
      const rawExpiry = document.getElementById('cf-expiry').value;
      const cvc       = document.getElementById('cf-cvc').value.trim();
      const parts    = rawExpiry.replace(/\s/g,'').split('/');
      const expMonth = parseInt(parts[0]||'0',10);
      const expYearS = parseInt(parts[1]||'0',10);
      const expYear  = expYearS < 100 ? 2000 + expYearS : expYearS;

      if (!rawNumber || rawNumber.length < 13 || !expMonth || !expYear || !cvc) {
        showCardError('Please check all card fields are filled correctly.');
        return;
      }

      const fd = new FormData();
      fd.append('pay_method',  'card');
      fd.append('card_number', rawNumber);
      fd.append('exp_month',   expMonth);
      fd.append('exp_year',    expYear);
      fd.append('cvc',         cvc);
      fd.append('email', email);
      fd.append('name',  name);
      fd.append('phone', phone);

      let rawText;
      try {
        const res = await fetch('pay.php?reservation_id=<?= $reservation_id ?>', { method:'POST', body:fd });
        rawText = await res.text();
      } catch(e) {
        showCardError('Network error: ' + e.message);
        return;
      }
      let data;
      try { data = JSON.parse(rawText); }
      catch(_) { showCardError('Server error — check browser console.'); console.error(rawText); return; }

      if (data.status === 'succeeded') {
        window.location.href = data.redirect;
      } else if (data.status === 'awaiting_next_action') {
        window.location.href = data.redirect;
      } else {
        showCardError(data.message || 'Payment failed. Please try again.');
      }

    } else {
      document.getElementById('processing-label').textContent = 'Redirecting to GCash…';
      document.getElementById('f-method').value = selMethod;
      document.getElementById('f-email').value  = email;
      document.getElementById('f-name').value   = name;
      document.getElementById('f-phone').value  = phone;
      setTimeout(() => document.getElementById('pay-form').submit(), 800);
    }
  }

  function showCardError(msg) {
    step = 1; showStep(1);
    const cardCard = document.querySelector('[data-method="card"]');
    if (cardCard && selMethod === 'card') selectMethod(cardCard);
    document.getElementById('card-fields').classList.add('visible');
    const err = document.getElementById('card-error');
    err.textContent = msg; err.classList.add('visible');
    showToast(msg, 'error');
  }

  function openPolicyModal(e) { e.preventDefault(); document.getElementById('policy-modal').classList.add('visible'); }
  function closePolicyModal() { document.getElementById('policy-modal').classList.remove('visible'); }
  function agreePolicyModal() {
    document.getElementById('agree-chk').checked = true;
    document.getElementById('btn-next').disabled = false;
    closePolicyModal();
  }
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePolicyModal(); });

  livePreview();
</script>
</body>
</html>