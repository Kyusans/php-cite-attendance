-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 11, 2025 at 01:55 AM
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
-- Database: `dbcocfacultyattendance`
--

-- --------------------------------------------------------

--
-- Table structure for table `tblattendance`
--

CREATE TABLE `tblattendance` (
  `atttend_id` int(11) NOT NULL,
  `attend_userId` int(11) NOT NULL,
  `attend_dateTime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblfacultyschedule`
--

CREATE TABLE `tblfacultyschedule` (
  `sched_id` int(11) NOT NULL,
  `sched_day` varchar(11) NOT NULL,
  `sched_startTime` time NOT NULL,
  `sched_endTime` time NOT NULL,
  `sched_userId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblfacultyschedule`
--

INSERT INTO `tblfacultyschedule` (`sched_id`, `sched_day`, `sched_startTime`, `sched_endTime`, `sched_userId`) VALUES
(3, 'Monday', '08:00:00', '10:00:00', 2),
(4, 'Wednesday', '13:00:00', '15:00:00', 2),
(5, 'Friday', '11:00:00', '14:00:00', 2),
(6, 'Tuesday', '09:00:00', '11:00:00', 3),
(7, 'Thursday', '14:00:00', '16:00:00', 3),
(8, 'Friday', '19:00:00', '21:00:00', 3),
(9, 'Monday', '10:00:00', '12:00:00', 4),
(10, 'Wednesday', '15:00:00', '17:00:00', 4),
(11, 'Friday', '08:00:00', '10:00:00', 4),
(14, 'Thursday', '09:00:00', '11:00:00', 5),
(15, 'Monday', '13:00:00', '15:00:00', 6),
(16, 'Wednesday', '09:00:00', '11:00:00', 6),
(17, 'Friday', '14:00:00', '16:00:00', 6),
(18, 'Tuesday', '10:00:00', '12:00:00', 7),
(19, 'Thursday', '08:00:00', '10:00:00', 7),
(20, 'Friday', '13:00:00', '15:00:00', 7),
(21, 'Monday', '14:00:00', '16:00:00', 8),
(22, 'Wednesday', '08:00:00', '10:00:00', 8),
(23, 'Thursday', '15:00:00', '17:00:00', 8),
(24, 'Tuesday', '13:00:00', '15:00:00', 9),
(25, 'Thursday', '10:00:00', '12:00:00', 9),
(26, 'Friday', '09:00:00', '11:00:00', 9),
(27, 'Monday', '08:00:00', '10:00:00', 10),
(28, 'Wednesday', '14:00:00', '16:00:00', 10),
(29, 'Friday', '16:00:00', '18:00:00', 10),
(30, 'Tuesday', '09:00:00', '11:00:00', 11),
(31, 'Thursday', '13:00:00', '15:00:00', 11),
(32, 'Friday', '11:00:00', '13:00:00', 11),
(33, 'Monday', '15:00:00', '17:00:00', 12),
(34, 'Wednesday', '10:00:00', '12:00:00', 12),
(35, 'Thursday', '08:00:00', '10:00:00', 12),
(36, 'Monday', '09:00:00', '11:00:00', 3);

-- --------------------------------------------------------

--
-- Table structure for table `tblfacultystatus`
--

CREATE TABLE `tblfacultystatus` (
  `facStatus_id` int(11) NOT NULL,
  `facStatus_userId` int(11) NOT NULL,
  `facStatus_statusMId` int(11) NOT NULL,
  `facStatus_note` text DEFAULT NULL,
  `facStatus_dateTime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblfacultystatus`
--

INSERT INTO `tblfacultystatus` (`facStatus_id`, `facStatus_userId`, `facStatus_statusMId`, `facStatus_note`, `facStatus_dateTime`) VALUES
(528, 4, 1, 'In Office', '2025-09-10 23:44:45'),
(529, 5, 1, 'In Office', '2025-09-10 23:40:27'),
(530, 6, 1, 'In Office', '2025-09-10 23:44:45'),
(531, 8, 1, 'In Office', '2025-09-10 23:44:45');

-- --------------------------------------------------------

--
-- Table structure for table `tblfacultystatusmaster`
--

CREATE TABLE `tblfacultystatusmaster` (
  `facStatMaster_id` int(11) NOT NULL,
  `facStatMaster_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblfacultystatusmaster`
--

INSERT INTO `tblfacultystatusmaster` (`facStatMaster_id`, `facStatMaster_name`) VALUES
(1, 'In office'),
(2, 'Out'),
(3, 'In class');

-- --------------------------------------------------------

--
-- Table structure for table `tbluser`
--

CREATE TABLE `tbluser` (
  `user_id` int(11) NOT NULL,
  `user_firstName` varchar(100) NOT NULL,
  `user_middleName` varchar(100) DEFAULT NULL,
  `user_lastName` varchar(100) NOT NULL,
  `user_schoolId` varchar(100) NOT NULL,
  `user_password` varchar(100) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `user_image` text NOT NULL,
  `user_level` int(11) NOT NULL,
  `user_isActive` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbluser`
--

INSERT INTO `tbluser` (`user_id`, `user_firstName`, `user_middleName`, `user_lastName`, `user_schoolId`, `user_password`, `user_email`, `user_image`, `user_level`, `user_isActive`) VALUES
(1, 'admin', 'admin', 'admin', 'admin', 'admin', 'admin@gmail.com', 'emptyImage.jpg', 1, 1),
(3, 'Bea', 'Macalua', 'Lachica', '12345678', 'bea', 'bea@gmail.com', 'emptyImage.jpg', 2, 1),
(4, 'kyuchan', 'Sabido', 'joee', '123456781', 'admin', 'kyuchans@gmail.com', 'emptyImage.jpg', 2, 1),
(5, 'Joe', 'Joe', 'Mama', '1234567811', 'admin', 'admin12@gmail.com', 'emptyImage.jpg', 2, 1),
(6, 'Trump', '12', 'Kun', '121312', 'admin12', 'admin1212@gmail.com', 'emptyImage.jpg', 2, 1),
(7, 'Obama', '12', 'Sama', '125432', 'admin12', 'ad1231min@gmail.com', 'emptyImage.jpg', 2, 1),
(8, 'Duterte', 'sabido', 'Kyun', '1234567812', 'admin', 'mel@gmail.com', 'emptyImage.jpg', 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbluserlevel`
--

CREATE TABLE `tbluserlevel` (
  `userLevel_id` int(11) NOT NULL,
  `userLevel_name` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbluserlevel`
--

INSERT INTO `tbluserlevel` (`userLevel_id`, `userLevel_name`) VALUES
(1, 'Admin'),
(2, 'Faculty');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tblattendance`
--
ALTER TABLE `tblattendance`
  ADD PRIMARY KEY (`atttend_id`),
  ADD KEY `attend_userId` (`attend_userId`);

--
-- Indexes for table `tblfacultyschedule`
--
ALTER TABLE `tblfacultyschedule`
  ADD PRIMARY KEY (`sched_id`),
  ADD KEY `sched_userId` (`sched_userId`);

--
-- Indexes for table `tblfacultystatus`
--
ALTER TABLE `tblfacultystatus`
  ADD PRIMARY KEY (`facStatus_id`),
  ADD KEY `tblfacultystatus_ibfk_1` (`facStatus_statusMId`),
  ADD KEY `facStatus_userId` (`facStatus_userId`);

--
-- Indexes for table `tblfacultystatusmaster`
--
ALTER TABLE `tblfacultystatusmaster`
  ADD PRIMARY KEY (`facStatMaster_id`);

--
-- Indexes for table `tbluser`
--
ALTER TABLE `tbluser`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `user_level` (`user_level`);

--
-- Indexes for table `tbluserlevel`
--
ALTER TABLE `tbluserlevel`
  ADD PRIMARY KEY (`userLevel_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tblattendance`
--
ALTER TABLE `tblattendance`
  MODIFY `atttend_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblfacultyschedule`
--
ALTER TABLE `tblfacultyschedule`
  MODIFY `sched_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `tblfacultystatus`
--
ALTER TABLE `tblfacultystatus`
  MODIFY `facStatus_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=532;

--
-- AUTO_INCREMENT for table `tblfacultystatusmaster`
--
ALTER TABLE `tblfacultystatusmaster`
  MODIFY `facStatMaster_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbluser`
--
ALTER TABLE `tbluser`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `tbluserlevel`
--
ALTER TABLE `tbluserlevel`
  MODIFY `userLevel_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tblattendance`
--
ALTER TABLE `tblattendance`
  ADD CONSTRAINT `tblattendance_ibfk_1` FOREIGN KEY (`attend_userId`) REFERENCES `tbluser` (`user_id`);

--
-- Constraints for table `tblfacultyschedule`
--
ALTER TABLE `tblfacultyschedule`
  ADD CONSTRAINT `tblfacultyschedule_ibfk_1` FOREIGN KEY (`sched_userId`) REFERENCES `tbluser` (`user_id`);

--
-- Constraints for table `tblfacultystatus`
--
ALTER TABLE `tblfacultystatus`
  ADD CONSTRAINT `tblfacultystatus_ibfk_1` FOREIGN KEY (`facStatus_statusMId`) REFERENCES `tblfacultystatusmaster` (`facStatMaster_id`),
  ADD CONSTRAINT `tblfacultystatus_ibfk_2` FOREIGN KEY (`facStatus_userId`) REFERENCES `tbluser` (`user_id`);

--
-- Constraints for table `tbluser`
--
ALTER TABLE `tbluser`
  ADD CONSTRAINT `tbluser_ibfk_1` FOREIGN KEY (`user_level`) REFERENCES `tbluserlevel` (`userLevel_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
