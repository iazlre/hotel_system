-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2026 at 10:17 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hotel_restaurant_dbs`
--

-- --------------------------------------------------------

--
-- Table structure for table `booking_list`
--

CREATE TABLE `booking_list` (
  `booking_id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `guest_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `handled_by_staff_id` int(11) DEFAULT NULL,
  `check_in` datetime NOT NULL,
  `check_out` datetime DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('Unpaid','Paid','Partially Paid') DEFAULT 'Unpaid',
  `booking_status` enum('Checked-In','Checked-Out','Cancelled') DEFAULT 'Checked-In'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cafe_reservations`
--

CREATE TABLE `cafe_reservations` (
  `reservation_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `assigned_staff_id` int(11) DEFAULT NULL,
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `number_of_guests` int(11) NOT NULL,
  `status` enum('Confirmed','Seated','Completed','Cancelled') DEFAULT 'Confirmed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cafe_reservations`
--

INSERT INTO `cafe_reservations` (`reservation_id`, `guest_id`, `table_id`, `assigned_staff_id`, `reservation_date`, `reservation_time`, `number_of_guests`, `status`) VALUES
(1, 2, 6, 4, '2026-11-10', '18:00:00', 4, 'Confirmed');

-- --------------------------------------------------------

--
-- Table structure for table `cafe_tables`
--

CREATE TABLE `cafe_tables` (
  `table_id` int(11) NOT NULL,
  `table_number` varchar(10) NOT NULL,
  `capacity` int(11) NOT NULL CHECK (`capacity` in (2,4,6)),
  `status` enum('Available','Reserved','Occupied') DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cafe_tables`
--

INSERT INTO `cafe_tables` (`table_id`, `table_number`, `capacity`, `status`) VALUES
(1, 'T-01', 2, 'Available'),
(2, 'T-02', 2, 'Available'),
(3, 'T-03', 2, 'Available'),
(4, 'T-04', 2, 'Available'),
(5, 'T-05', 2, 'Available'),
(6, 'T-06', 4, 'Available'),
(7, 'T-07', 4, 'Available'),
(8, 'T-08', 4, 'Available'),
(9, 'T-09', 4, 'Available'),
(10, 'T-10', 4, 'Available'),
(11, 'T-11', 6, 'Available'),
(12, 'T-12', 6, 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `guests`
--

CREATE TABLE `guests` (
  `guest_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `guest_type` enum('Student','Faculty') NOT NULL DEFAULT 'Student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guests`
--

INSERT INTO `guests` (`guest_id`, `first_name`, `last_name`, `email`, `phone`, `guest_type`) VALUES
(1, 'Juan', 'Dela Cruz', 'juan.delacruz@email.com', '09171234567', 'Student'),
(2, 'Maria', 'Clara', 'maria.clara@email.com', '09182345678', 'Faculty'),
(3, 'Elias', 'Salazar', 'elias.s@email.com', '09204567890', 'Student'),
(4, 'Sisa', 'Mercado', 'sisa.m@email.com', '09215678901', 'Faculty'),
(5, 'Crisostomo', 'Ibarra', 'c.ibarra@email.com', '09226789012', 'Faculty'),
(6, 'Basilio', 'Santos', 'basilio.s@email.com', '09237890123', 'Student');

-- --------------------------------------------------------

--
-- Table structure for table `housekeeping_logs`
--

CREATE TABLE `housekeeping_logs` (
  `log_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `assigned_housekeeper_id` int(11) DEFAULT NULL,
  `task_description` varchar(255) NOT NULL DEFAULT 'Routine Cleaning & Bed Reset',
  `cleaning_status` enum('Pending','In Progress','Completed') DEFAULT 'Pending',
  `scheduled_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `room_type` enum('Standard Room','Deluxe Suite') NOT NULL,
  `capacity` int(11) NOT NULL CHECK (`capacity` in (2,4)),
  `price_per_night` decimal(8,2) NOT NULL,
  `status` enum('Vacant','Reserved','Occupied','Under Maintenance') DEFAULT 'Vacant'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_number`, `room_type`, `capacity`, `price_per_night`, `status`) VALUES
(1, '101', 'Standard Room', 2, 1500.00, 'Vacant'),
(2, '102', 'Standard Room', 2, 1500.00, 'Vacant'),
(3, '201', 'Deluxe Suite', 4, 3500.00, 'Vacant'),
(4, '202', 'Deluxe Suite', 4, 3500.00, 'Vacant');

-- --------------------------------------------------------

--
-- Table structure for table `room_reservations`
--

CREATE TABLE `room_reservations` (
  `reservation_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `handled_by_staff_id` int(11) DEFAULT NULL,
  `reservation_date` datetime NOT NULL DEFAULT current_timestamp(),
  `expected_check_in` datetime NOT NULL,
  `expected_check_out` datetime NOT NULL,
  `reservation_status` enum('Pending','Confirmed','Cancelled','Converted to Booking') DEFAULT 'Confirmed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_reservations`
--

INSERT INTO `room_reservations` (`reservation_id`, `guest_id`, `room_id`, `handled_by_staff_id`, `reservation_date`, `expected_check_in`, `expected_check_out`, `reservation_status`) VALUES
(1, 2, 3, 2, '2026-11-05 17:12:38', '2026-11-10 14:00:00', '2026-11-12 12:00:00', 'Confirmed');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('Receptionist','Housekeeping','Cafe Staff','Manager') NOT NULL,
  `shift_schedule` varchar(50) DEFAULT NULL,
  `status` enum('Active','On Leave','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `first_name`, `last_name`, `email`, `role`, `shift_schedule`, `status`) VALUES
(1, 'James Edward', 'Alexander', 'manager@hotel.com', 'Manager', 'Day Shift (8AM-5PM)', 'Active'),
(2, 'Jose', 'Rizal', 'reception@hotel.com', 'Receptionist', 'Morning Shift (6AM-2PM)', 'Active'),
(3, 'Andres', 'Bonifacio', 'housekeeping@hotel.com', 'Housekeeping', 'Morning Shift (6AM-2PM)', 'Active'),
(4, 'Apolinario', 'Mabini', 'cafe@hotel.com', 'Cafe Staff', 'Afternoon Shift (1PM-9PM)', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Staff','Student') NOT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking_list`
--
ALTER TABLE `booking_list`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `fk_b_rsv` (`reservation_id`),
  ADD KEY `fk_b_guest` (`guest_id`),
  ADD KEY `fk_b_room` (`room_id`),
  ADD KEY `fk_b_staff` (`handled_by_staff_id`);

--
-- Indexes for table `cafe_reservations`
--
ALTER TABLE `cafe_reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `fk_cr_guest` (`guest_id`),
  ADD KEY `fk_cr_table` (`table_id`),
  ADD KEY `fk_cr_staff` (`assigned_staff_id`);

--
-- Indexes for table `cafe_tables`
--
ALTER TABLE `cafe_tables`
  ADD PRIMARY KEY (`table_id`),
  ADD UNIQUE KEY `table_number` (`table_number`);

--
-- Indexes for table `guests`
--
ALTER TABLE `guests`
  ADD PRIMARY KEY (`guest_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `housekeeping_logs`
--
ALTER TABLE `housekeeping_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_hk_room` (`room_id`),
  ADD KEY `fk_hk_staff` (`assigned_housekeeper_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- Indexes for table `room_reservations`
--
ALTER TABLE `room_reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `fk_rr_guest` (`guest_id`),
  ADD KEY `fk_rr_room` (`room_id`),
  ADD KEY `fk_rr_staff` (`handled_by_staff_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_guest` (`guest_id`),
  ADD KEY `fk_users_staff` (`staff_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booking_list`
--
ALTER TABLE `booking_list`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cafe_reservations`
--
ALTER TABLE `cafe_reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cafe_tables`
--
ALTER TABLE `cafe_tables`
  MODIFY `table_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `guests`
--
ALTER TABLE `guests`
  MODIFY `guest_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `housekeeping_logs`
--
ALTER TABLE `housekeeping_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `room_reservations`
--
ALTER TABLE `room_reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking_list`
--
ALTER TABLE `booking_list`
  ADD CONSTRAINT `fk_b_guest` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_b_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_b_rsv` FOREIGN KEY (`reservation_id`) REFERENCES `room_reservations` (`reservation_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_b_staff` FOREIGN KEY (`handled_by_staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL;

--
-- Constraints for table `cafe_reservations`
--
ALTER TABLE `cafe_reservations`
  ADD CONSTRAINT `fk_cr_guest` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cr_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cr_table` FOREIGN KEY (`table_id`) REFERENCES `cafe_tables` (`table_id`) ON DELETE CASCADE;

--
-- Constraints for table `housekeeping_logs`
--
ALTER TABLE `housekeeping_logs`
  ADD CONSTRAINT `fk_hk_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_hk_staff` FOREIGN KEY (`assigned_housekeeper_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL;

--
-- Constraints for table `room_reservations`
--
ALTER TABLE `room_reservations`
  ADD CONSTRAINT `fk_rr_guest` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rr_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rr_staff` FOREIGN KEY (`handled_by_staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_guest` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_users_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
