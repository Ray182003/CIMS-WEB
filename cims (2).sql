-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 25, 2025 at 02:00 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cims`
--

-- --------------------------------------------------------

--
-- Table structure for table `baptismal_records`
--

CREATE TABLE `baptismal_records` (
  `id` int(11) NOT NULL,
  `child_name` varchar(255) NOT NULL,
  `child_of` varchar(255) NOT NULL,
  `parent_name1` varchar(255) NOT NULL,
  `parent_name2` varchar(255) NOT NULL,
  `birth_date` varchar(250) NOT NULL,
  `birth_place` date NOT NULL,
  `baptism_date` date NOT NULL,
  `bishop` varchar(255) NOT NULL,
  `day` varchar(11) NOT NULL,
  `month` varchar(50) NOT NULL,
  `year` varchar(10) NOT NULL,
  `book_no` varchar(50) NOT NULL,
  `page_no` varchar(50) NOT NULL,
  `entry_no` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `baptismal_records`
--

INSERT INTO `baptismal_records` (`id`, `child_name`, `child_of`, `parent_name1`, `parent_name2`, `birth_date`, `birth_place`, `baptism_date`, `bishop`, `day`, `month`, `year`, `book_no`, `page_no`, `entry_no`, `created_at`) VALUES
(1, 'MARIA MAY S. JIMENEZ', '', 'ROGELIO JIMENEZ', 'LOLITA SALABANIA', 'BRGY. SALONG, KAB. CITY', '1982-11-24', '1982-11-24', 'FR. HENRY G. PINEDA', '24TH', 'OCTOBER', '2025', '01', '24', '124', '2025-10-23 03:36:26'),
(2, 'MARIA MAY S. JIMENEZ', '', 'ROGELIO JIMENEZ', 'LOLITA SALABANIA', 'BRGY. SALONG, KAB. CITY', '1982-11-24', '1982-11-24', 'REV. FR. HENRY G. PINEDA', '24TH', 'OCTOBER', '2025', '01', '24', '124', '2025-10-23 03:36:26'),
(3, 'BEN ERIC A EBARISTO', '', 'BENJIE O EBARISTO', 'ELDEM A EBARISTO', 'BRGY BINICUIL SAMPAGUITA STREET KAB CITY', '2004-05-08', '2025-11-24', 'FT, Alipon', '24', 'NOVEMBER', '2025', '69', '15', '14', '2025-10-24 06:30:18');

-- --------------------------------------------------------

--
-- Table structure for table `confirmation_records`
--

CREATE TABLE `confirmation_records` (
  `id` int(11) NOT NULL,
  `child_name` varchar(255) NOT NULL,
  `parent_name1` varchar(255) NOT NULL,
  `parent_name2` varchar(255) NOT NULL,
  `bishop` varchar(255) NOT NULL,
  `confirmation_date` date NOT NULL,
  `book_no` varchar(50) NOT NULL,
  `page_no` varchar(50) NOT NULL,
  `entry_no` varchar(50) NOT NULL,
  `issue_day` varchar(250) NOT NULL,
  `issue_month` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `confirmation_records`
--

INSERT INTO `confirmation_records` (`id`, `child_name`, `parent_name1`, `parent_name2`, `bishop`, `confirmation_date`, `book_no`, `page_no`, `entry_no`, `issue_day`, `issue_month`, `created_at`) VALUES
(1, 'Jervie C. Estrillo', 'Eric Doe', 'Jane Doe', 'Fr. Alipo-on', '2025-10-24', '1', '12', '12', '12', '12', '2025-10-23 16:43:24');

-- --------------------------------------------------------

--
-- Table structure for table `confirmation_sponsors`
--

CREATE TABLE `confirmation_sponsors` (
  `id` int(11) NOT NULL,
  `confirmation_id` int(11) NOT NULL,
  `sponsor_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `confirmation_sponsors`
--

INSERT INTO `confirmation_sponsors` (`id`, `confirmation_id`, `sponsor_name`) VALUES
(1, 1, 'Ray 404');

-- --------------------------------------------------------

--
-- Table structure for table `death_certificates`
--

CREATE TABLE `death_certificates` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `date_of_death` date NOT NULL,
  `cause_of_death` varchar(255) NOT NULL,
  `issue_place` varchar(255) NOT NULL,
  `issue_day` varchar(50) NOT NULL,
  `issue_year` varchar(250) NOT NULL,
  `book_no` varchar(50) NOT NULL,
  `page_no` varchar(50) NOT NULL,
  `entry_no` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `death_certificates`
--

INSERT INTO `death_certificates` (`id`, `name`, `address`, `date_of_death`, `cause_of_death`, `issue_place`, `issue_day`, `issue_year`, `book_no`, `page_no`, `entry_no`, `created_at`) VALUES
(1, 'ben eric a ebaristo', 'cauyan', '2025-10-24', 'Lalaki', '12', '12', '12', '12', '12', '12', '2025-10-24 00:11:12');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `event_date` date NOT NULL,
  `time` time NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `liberty_records`
--

CREATE TABLE `liberty_records` (
  `id` int(11) NOT NULL,
  `child_name` varchar(255) NOT NULL,
  `parent_name1` varchar(255) NOT NULL,
  `parent_name2` varchar(255) NOT NULL,
  `residence` varchar(255) NOT NULL,
  `marriage_with` varchar(255) NOT NULL,
  `signed_sealed_given` varchar(255) NOT NULL,
  `day_of` varchar(50) NOT NULL,
  `year_of_our_lord` varchar(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `liberty_records`
--

INSERT INTO `liberty_records` (`id`, `child_name`, `parent_name1`, `parent_name2`, `residence`, `marriage_with`, `signed_sealed_given`, `day_of`, `year_of_our_lord`, `created_at`) VALUES
(1, 'joshoua libacao perez', 'cd\'\',,cslcs\'n', 'ogefdsho[idshvo[dv', 'sdfsdf', 'crissa jane javellana taloronbg', 'wedenesday', 'october 2025', '2025', '2025-10-24 07:55:16');

-- --------------------------------------------------------

--
-- Table structure for table `marriage_permits`
--

CREATE TABLE `marriage_permits` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `birthdate` date NOT NULL,
  `parents` varchar(255) NOT NULL,
  `parents_second` varchar(255) NOT NULL,
  `residence` varchar(255) NOT NULL,
  `issue_date` varchar(100) NOT NULL,
  `day_of` varchar(50) NOT NULL,
  `year_of_lord` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marriage_permits`
--

INSERT INTO `marriage_permits` (`id`, `name`, `birthdate`, `parents`, `parents_second`, `residence`, `issue_date`, `day_of`, `year_of_lord`, `created_at`) VALUES
(1, 'crissa jane javellana talorong', '2003-12-06', 'father', 'mother', 'brgy hilamonan sitio tapi an kabanakalan city', 'novermber', 'wednesday', 1, '2025-10-24 07:52:35');

-- --------------------------------------------------------

--
-- Table structure for table `sponsors`
--

CREATE TABLE `sponsors` (
  `id` int(11) NOT NULL,
  `baptismal_id` int(11) NOT NULL,
  `sponsor_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sponsors`
--

INSERT INTO `sponsors` (`id`, `baptismal_id`, `sponsor_name`) VALUES
(4, 2, 'SHIRLEY DELA ROSA'),
(5, 1, 'cute'),
(6, 1, 'cute'),
(7, 1, 'SHIRLEY DELA ROSA'),
(12, 3, 'BONGBONG MARCOS'),
(13, 3, 'rodrigo roa duterte');

-- --------------------------------------------------------

--
-- Table structure for table `status_of_liberty`
--

CREATE TABLE `status_of_liberty` (
  `id` int(11) NOT NULL,
  `son_of` varchar(255) NOT NULL,
  `daughter_of` varchar(255) NOT NULL,
  `residence` varchar(255) NOT NULL,
  `partner_name` varchar(255) NOT NULL,
  `issued_date` date NOT NULL,
  `parish_name` varchar(255) NOT NULL,
  `priest_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`) VALUES
(1, 'admin', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `baptismal_records`
--
ALTER TABLE `baptismal_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `confirmation_records`
--
ALTER TABLE `confirmation_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `confirmation_sponsors`
--
ALTER TABLE `confirmation_sponsors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `confirmation_id` (`confirmation_id`);

--
-- Indexes for table `death_certificates`
--
ALTER TABLE `death_certificates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `liberty_records`
--
ALTER TABLE `liberty_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marriage_permits`
--
ALTER TABLE `marriage_permits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sponsors`
--
ALTER TABLE `sponsors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `baptismal_id` (`baptismal_id`);

--
-- Indexes for table `status_of_liberty`
--
ALTER TABLE `status_of_liberty`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `baptismal_records`
--
ALTER TABLE `baptismal_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `confirmation_records`
--
ALTER TABLE `confirmation_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `confirmation_sponsors`
--
ALTER TABLE `confirmation_sponsors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `death_certificates`
--
ALTER TABLE `death_certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `liberty_records`
--
ALTER TABLE `liberty_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `marriage_permits`
--
ALTER TABLE `marriage_permits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sponsors`
--
ALTER TABLE `sponsors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `status_of_liberty`
--
ALTER TABLE `status_of_liberty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `confirmation_sponsors`
--
ALTER TABLE `confirmation_sponsors`
  ADD CONSTRAINT `confirmation_sponsors_ibfk_1` FOREIGN KEY (`confirmation_id`) REFERENCES `confirmation_records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sponsors`
--
ALTER TABLE `sponsors`
  ADD CONSTRAINT `sponsors_ibfk_1` FOREIGN KEY (`baptismal_id`) REFERENCES `baptismal_records` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
