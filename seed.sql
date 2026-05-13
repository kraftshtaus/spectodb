USE thesis_project;

INSERT INTO transactions (order_id, event_type, created_at) VALUES
(1, 'order_created', '2026-03-20 10:00:00'),
(1, 'payment_started', '2026-03-20 10:01:00'),
(1, 'payment_success', '2026-03-20 10:02:00'),
(1, 'order_sent', '2026-03-20 10:10:00'),

(2, 'order_created', '2026-03-20 11:00:00'),
(2, 'payment_started', '2026-03-20 11:01:00'),
(2, 'payment_failed', '2026-03-20 11:02:00'),

(3, 'order_created', '2026-03-20 12:00:00'),
(3, 'payment_started', '2026-03-20 12:01:00'),
(3, 'payment_success', '2026-03-20 12:02:00'),
(3, 'order_sent', '2026-03-20 12:15:00');