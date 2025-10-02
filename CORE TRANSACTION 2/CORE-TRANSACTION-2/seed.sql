USE `hotel_core`;

-- Seed guests
INSERT INTO customer_guest_management (guest_id, name, contact_no, email, address) VALUES
  ('G-001','Juan Dela Cruz','09171234567','juan@example.com','Manila, PH'),
  ('G-002','Maria Clara','09179876543','maria@example.com','Quezon City, PH')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Seed rooms
INSERT INTO room_facilities (room_id, room_type, capacity, status, facility_name) VALUES
  ('RM-101','Standard',2,'Available','City View'),
  ('RM-102','Standard',2,'Occupied','City View'),
  ('RM-201','Deluxe',3,'Cleaning','Sea View'),
  ('RM-301','Suite',4,'Available','Panoramic View')
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- Seed staff
INSERT INTO core_human_capital_management (staff_id, name, role, salary, shift) VALUES
  ('S-FO-01','Anna Reyes','Front Desk',25000,'Day'),
  ('S-HK-01','Leo Santos','Housekeeping',18000,'Morning'),
  ('S-LD-01','Mika Cruz','Laundry',17000,'Evening')
ON DUPLICATE KEY UPDATE role=VALUES(role);

-- Seed suppliers
INSERT INTO supplier_management (supplier_id, supplier_name, contact, item_provided, delivery_date) VALUES
  ('SUP-01','Fresh Linens Co.','linens@example.com','Linens','2025-09-01'),
  ('SUP-02','CleanChem Corp.','chem@example.com','Cleaning Chemicals','2025-09-02')
ON DUPLICATE KEY UPDATE item_provided=VALUES(item_provided);

-- Seed facilities management
INSERT INTO facilities_management (facility_id, supplier_id, facility_type, maintenance_schedule, status) VALUES
  ('FAC-AC-01','SUP-02','Aircon','Monthly','Active'),
  ('FAC-PL-01','SUP-02','Plumbing','Quarterly','Active')
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- Seed reservations
INSERT INTO reservation (reservation_id, room_id, check_in_date, check_out_date, status, guest_id) VALUES
  ('RES-1001','RM-101','2025-10-05','2025-10-07','Booked','G-001'),
  ('RES-1002','RM-102','2025-10-06','2025-10-08','Booked','G-002')
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- Seed bookings (one-to-one with reservation here)
INSERT INTO booking (booking_id, reservation_id, booking_date, status) VALUES
  ('BKG-1001','RES-1001','2025-09-30','Confirmed'),
  ('BKG-1002','RES-1002','2025-09-30','Confirmed')
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- Seed front office interactions
INSERT INTO front_office (office_id, reservation_id, staff_id, office_name, shift_schedule, action_log, guest_id) VALUES
  ('FO-LOG-1','RES-1001','S-FO-01','Front Desk','Day','Reservation created','G-001'),
  ('FO-LOG-2','RES-1002','S-FO-01','Front Desk','Day','Reservation created','G-002')
ON DUPLICATE KEY UPDATE action_log=VALUES(action_log);

-- Seed housekeeping
INSERT INTO housekeeping_laundry (hk_id, task_assigned, laundry_status, shift_time, room_id, staff_id) VALUES
  ('HK-2001','Clean Room','Pending','Morning','RM-201','S-HK-01'),
  ('HK-2002','Prepare Linens','In-Progress','Evening','RM-101','S-LD-01')
ON DUPLICATE KEY UPDATE laundry_status=VALUES(laundry_status);

-- Seed billing
INSERT INTO billing (bill_id, booking_id, amount, payment_status, payment_date, guest_id) VALUES
  ('BILL-9001','BKG-1001',8200.00,'Paid','2025-09-30','G-001'),
  ('BILL-9002','BKG-1002',3450.00,'Unpaid',NULL,'G-002')
ON DUPLICATE KEY UPDATE payment_status=VALUES(payment_status), payment_date=VALUES(payment_date);


