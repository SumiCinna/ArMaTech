<?php
// core/reservation_expiry.php

/**
 * Auto-forfeit reservations that stayed unpaid for more than 3 days.
 *
 * Rules:
 * - Overdue reservations that are not yet completed are candidates:
 *   `pending_payment`, `pending_verification`, `pending`, `approved`.
 * - Mark reservation as `forfeited` and set `forfeited_at` timestamp.
 * - Return the related shop item back to `available`.
 * - Both updates run in one transaction per reservation.
 *
 * @return int Number of reservations successfully forfeited in this run.
 */
function run_reservation_expiry(mysqli $conn): int
{
		$fetch = $conn->prepare(
				"SELECT reservation_id, shop_id
				 FROM shop_reservations
				 WHERE status IN ('pending_payment', 'pending_verification', 'pending', 'approved')
					 AND created_at <= NOW() - INTERVAL 3 DAY"
		);

	if (!$fetch) {
		return 0;
	}

	$fetch->execute();
	$rows = $fetch->get_result()->fetch_all(MYSQLI_ASSOC);
	$fetch->close();

	if (empty($rows)) {
		return 0;
	}

	$forfeited_count = 0;

	$update_reservation = $conn->prepare(
		"UPDATE shop_reservations
		 SET status = 'forfeited',
			 forfeited_at = NOW()
		 WHERE reservation_id = ?
		   AND status IN ('pending_payment', 'pending_verification', 'pending', 'approved')
		   AND created_at <= NOW() - INTERVAL 3 DAY"
	);

	$update_item = $conn->prepare(
		"UPDATE shop_items
		 SET shop_status = 'available'
		 WHERE shop_id = ?"
	);

	if (!$update_reservation || !$update_item) {
		if ($update_reservation) {
			$update_reservation->close();
		}
		if ($update_item) {
			$update_item->close();
		}
		return 0;
	}

	foreach ($rows as $row) {
		$reservation_id = (int)$row['reservation_id'];
		$shop_id = (int)$row['shop_id'];

		$conn->begin_transaction();

		$update_reservation->bind_param('i', $reservation_id);
		$ok_reservation = $update_reservation->execute() && $update_reservation->affected_rows > 0;

		$update_item->bind_param('i', $shop_id);
		$ok_item = $update_item->execute();

		if ($ok_reservation && $ok_item) {
			$conn->commit();
			$forfeited_count++;
		} else {
			$conn->rollback();
		}
	}

	$update_reservation->close();
	$update_item->close();

	return $forfeited_count;
}

