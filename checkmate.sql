-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 23, 2026 at 01:53 PM
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
-- Database: `checkmate`
--

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `Account_id` int(11) NOT NULL,
  `Naam` varchar(100) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Wachtwoord` varchar(255) NOT NULL,
  `Login_poging` int(11) NOT NULL DEFAULT 0,
  `Geblokkeerd` tinyint(1) NOT NULL DEFAULT 0,
  `Reset_token` varchar(64) DEFAULT NULL,
  `Reset_vervalt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`Account_id`, `Naam`, `Email`, `Wachtwoord`, `Login_poging`, `Geblokkeerd`, `Reset_token`, `Reset_vervalt`) VALUES
(1, 'henry', 'henty@mail.com', '$2y$10$bfkZAtR9NH/SkJY37Vnwq.rj9Cls2LG0kF6JzR0qSd.rIKns7bQyC', 0, 0, NULL, NULL),
(2, 'hedon', 'hedot@mail.com', '$2y$10$pLOAXPqUDSd3ADY8HCjMNeYaK2n49xfxnqgmpdgdvede3dYRGrjyS', 0, 0, NULL, NULL),
(3, 'helon', 'helot@mail.com', '$2y$10$vGh3FhPq9ShlEJyLoY5Sr.ZumF8fskFMYROFyscBThErAYSPJzc32', 0, 0, NULL, NULL),
(4, 'Verwijderd lid', 'verwijderd-4@invalid.local', '$2y$10$M6b1tf9EdwJDwNTTCjpOz.YpEdOUuBZZjzlk9M6pyUxJ2zhkzQQye', 0, 1, NULL, NULL),
(5, 'Verwijderd lid', 'verwijderd-5@invalid.local', '$2y$10$CUELkmC105eCbJ6ddv9Pwei4St/sF8pTUNwpi9Ye3ReujCheaqhrW', 0, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admingroep`
--

CREATE TABLE `admingroep` (
  `Event_id` int(11) NOT NULL,
  `Account_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admingroep`
--

INSERT INTO `admingroep` (`Event_id`, `Account_id`) VALUES
(1, 1),
(2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

CREATE TABLE `event` (
  `Event_id` int(11) NOT NULL,
  `Organizer` int(11) NOT NULL,
  `Event_name` varchar(100) NOT NULL,
  `Lid_Code` varchar(20) NOT NULL,
  `Admin_Code` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event`
--

INSERT INTO `event` (`Event_id`, `Organizer`, `Event_name`, `Lid_Code`, `Admin_Code`) VALUES
(1, 1, 'test', '6A3BC4C0', '4EECF72B'),
(2, 1, 'test', 'ADEA3D5B', '102527DA');

-- --------------------------------------------------------

--
-- Table structure for table `groep`
--

CREATE TABLE `groep` (
  `Groep_id` int(11) NOT NULL,
  `Event_id` int(11) NOT NULL,
  `GroepNaam` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `groep`
--

INSERT INTO `groep` (`Groep_id`, `Event_id`, `GroepNaam`) VALUES
(1, 1, 'test1'),
(2, 1, 'test2'),
(3, 2, 'test1'),
(4, 2, 'test2');

-- --------------------------------------------------------

--
-- Table structure for table `lidgroep`
--

CREATE TABLE `lidgroep` (
  `Groep_id` int(11) NOT NULL,
  `Account_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lidgroep`
--

INSERT INTO `lidgroep` (`Groep_id`, `Account_id`) VALUES
(1, 2),
(1, 3),
(1, 5);

-- --------------------------------------------------------

--
-- Table structure for table `partij`
--

CREATE TABLE `partij` (
  `Partij_id` int(11) NOT NULL,
  `Groep_id` int(11) NOT NULL,
  `Lid1_id` int(11) NOT NULL,
  `Lid2_id` int(11) NOT NULL,
  `Arbiter_id` int(11) DEFAULT NULL,
  `Status` enum('Gepland','Bezig','Afgelopen') NOT NULL DEFAULT 'Gepland',
  `Datum` date NOT NULL,
  `Tafel` varchar(10) DEFAULT NULL,
  `Geplande_Tijd` datetime NOT NULL,
  `Werkelijke_Tijd` datetime DEFAULT NULL,
  `Winnaar` enum('lid1','lid2','gelijk') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `partij`
--

INSERT INTO `partij` (`Partij_id`, `Groep_id`, `Lid1_id`, `Lid2_id`, `Arbiter_id`, `Status`, `Datum`, `Tafel`, `Geplande_Tijd`, `Werkelijke_Tijd`, `Winnaar`) VALUES
(1, 1, 2, 3, 1, 'Afgelopen', '2026-10-19', '7', '2026-10-19 10:23:00', '2026-10-22 18:18:06', 'lid2');

-- --------------------------------------------------------

--
-- Table structure for table `stand`
--

CREATE TABLE `stand` (
  `Account_id` int(11) NOT NULL,
  `Groep_id` int(11) NOT NULL,
  `Gespeeld` int(11) NOT NULL DEFAULT 0,
  `Winst` int(11) NOT NULL DEFAULT 0,
  `Gelijkspel` int(11) NOT NULL DEFAULT 0,
  `Verlies` int(11) NOT NULL DEFAULT 0,
  `Punten` decimal(3,1) NOT NULL DEFAULT 0.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stand`
--

INSERT INTO `stand` (`Account_id`, `Groep_id`, `Gespeeld`, `Winst`, `Gelijkspel`, `Verlies`, `Punten`) VALUES
(2, 1, 1, 0, 0, 1, 0.0),
(3, 1, 1, 1, 0, 0, 1.0),
(3, 2, 0, 0, 0, 0, 0.0),
(5, 1, 0, 0, 0, 0, 0.0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`Account_id`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `admingroep`
--
ALTER TABLE `admingroep`
  ADD PRIMARY KEY (`Event_id`,`Account_id`),
  ADD KEY `Account_id` (`Account_id`);

--
-- Indexes for table `event`
--
ALTER TABLE `event`
  ADD PRIMARY KEY (`Event_id`),
  ADD UNIQUE KEY `Lid_Code` (`Lid_Code`),
  ADD UNIQUE KEY `Admin_Code` (`Admin_Code`),
  ADD KEY `Organizer` (`Organizer`);

--
-- Indexes for table `groep`
--
ALTER TABLE `groep`
  ADD PRIMARY KEY (`Groep_id`),
  ADD KEY `Event_id` (`Event_id`);

--
-- Indexes for table `lidgroep`
--
ALTER TABLE `lidgroep`
  ADD PRIMARY KEY (`Groep_id`,`Account_id`),
  ADD KEY `Account_id` (`Account_id`);

--
-- Indexes for table `partij`
--
ALTER TABLE `partij`
  ADD PRIMARY KEY (`Partij_id`),
  ADD KEY `Groep_id` (`Groep_id`),
  ADD KEY `Lid1_id` (`Lid1_id`),
  ADD KEY `Lid2_id` (`Lid2_id`),
  ADD KEY `Arbiter_id` (`Arbiter_id`);

--
-- Indexes for table `stand`
--
ALTER TABLE `stand`
  ADD PRIMARY KEY (`Account_id`,`Groep_id`),
  ADD KEY `Groep_id` (`Groep_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `Account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `event`
--
ALTER TABLE `event`
  MODIFY `Event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `groep`
--
ALTER TABLE `groep`
  MODIFY `Groep_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `partij`
--
ALTER TABLE `partij`
  MODIFY `Partij_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admingroep`
--
ALTER TABLE `admingroep`
  ADD CONSTRAINT `admingroep_ibfk_1` FOREIGN KEY (`Event_id`) REFERENCES `event` (`Event_id`),
  ADD CONSTRAINT `admingroep_ibfk_2` FOREIGN KEY (`Account_id`) REFERENCES `account` (`Account_id`);

--
-- Constraints for table `event`
--
ALTER TABLE `event`
  ADD CONSTRAINT `event_ibfk_1` FOREIGN KEY (`Organizer`) REFERENCES `account` (`Account_id`);

--
-- Constraints for table `groep`
--
ALTER TABLE `groep`
  ADD CONSTRAINT `groep_ibfk_1` FOREIGN KEY (`Event_id`) REFERENCES `event` (`Event_id`);

--
-- Constraints for table `lidgroep`
--
ALTER TABLE `lidgroep`
  ADD CONSTRAINT `lidgroep_ibfk_1` FOREIGN KEY (`Groep_id`) REFERENCES `groep` (`Groep_id`),
  ADD CONSTRAINT `lidgroep_ibfk_2` FOREIGN KEY (`Account_id`) REFERENCES `account` (`Account_id`);

--
-- Constraints for table `partij`
--
ALTER TABLE `partij`
  ADD CONSTRAINT `partij_ibfk_1` FOREIGN KEY (`Groep_id`) REFERENCES `groep` (`Groep_id`),
  ADD CONSTRAINT `partij_ibfk_2` FOREIGN KEY (`Lid1_id`) REFERENCES `account` (`Account_id`),
  ADD CONSTRAINT `partij_ibfk_3` FOREIGN KEY (`Lid2_id`) REFERENCES `account` (`Account_id`),
  ADD CONSTRAINT `partij_ibfk_4` FOREIGN KEY (`Arbiter_id`) REFERENCES `account` (`Account_id`);

--
-- Constraints for table `stand`
--
ALTER TABLE `stand`
  ADD CONSTRAINT `stand_ibfk_1` FOREIGN KEY (`Account_id`) REFERENCES `account` (`Account_id`),
  ADD CONSTRAINT `stand_ibfk_2` FOREIGN KEY (`Groep_id`) REFERENCES `groep` (`Groep_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
