-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 16, 2026 at 05:19 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `army_personnel_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` enum('add','edit','delete','approve','reject','login','logout') NOT NULL,
  `target_personnel_id` int(11) DEFAULT NULL,
  `details` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action_type`, `target_personnel_id`, `details`, `ip_address`, `created_at`) VALUES
(1, 6, 'edit', 2, '{\"name\":\"\"}', '::1', '2026-08-15 04:44:47'),
(2, 5, 'approve', 2, '{\"approval_id\":7,\"type\":\"edit\"}', '::1', '2026-08-15 04:45:29'),
(3, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-15 04:56:20'),
(4, 6, 'login', NULL, '{\"username\":\"raj\"}', '::1', '2026-08-15 04:56:25'),
(5, 6, 'logout', NULL, '{\"username\":\"raj\"}', '::1', '2026-08-15 05:04:16'),
(6, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-15 05:04:20'),
(7, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-15 05:10:00'),
(8, 6, 'login', NULL, '{\"username\":\"raj\"}', '::1', '2026-08-15 05:10:06'),
(9, 6, 'edit', 2, '{\"details\":\"Speed March: \'2\' -> \'3\'\"}', '::1', '2026-08-15 05:10:22'),
(10, 6, 'logout', NULL, '{\"username\":\"raj\"}', '::1', '2026-08-15 05:10:31'),
(11, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-15 05:10:36'),
(12, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-15 05:10:53'),
(13, 7, 'login', NULL, '{\"username\":\"op\"}', '::1', '2026-08-15 05:10:56'),
(14, 7, 'logout', NULL, '{\"username\":\"op\"}', '::1', '2026-08-15 05:11:44'),
(15, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-15 05:11:48'),
(16, 5, 'reject', 6, '{\"approval_id\":8,\"type\":\"edit\",\"requested_by\":\"op\",\"details\":\"Social Links updated\"}', '::1', '2026-08-15 05:15:02'),
(17, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-15 05:15:37'),
(18, 7, 'login', NULL, '{\"username\":\"op\"}', '::1', '2026-08-15 05:15:41'),
(19, 7, 'edit', 6, '{\"details\":\"Requested approval for edit: Social Links updated\"}', '::1', '2026-08-15 05:15:54'),
(20, 7, 'logout', NULL, '{\"username\":\"op\"}', '::1', '2026-08-15 05:15:57'),
(21, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-15 05:16:00'),
(22, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-15 05:19:10'),
(23, 7, 'login', NULL, '{\"username\":\"op\"}', '::1', '2026-08-15 05:19:16'),
(24, 7, 'edit', 5, '{\"details\":\"Requested approval for edit: Cadre updated\"}', '::1', '2026-08-15 05:19:33'),
(25, 7, 'logout', NULL, '{\"username\":\"op\"}', '::1', '2026-08-15 05:19:34'),
(26, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-15 05:19:37'),
(27, 5, 'reject', 6, '{\"approval_id\":9,\"type\":\"edit\",\"requested_by\":\"op\",\"details\":\"No changes detected\"}', '::1', '2026-08-15 05:26:37'),
(28, 5, 'approve', 5, '{\"approval_id\":10,\"type\":\"edit\",\"requested_by\":\"op\",\"details\":\"Cadre updated\"}', '::1', '2026-08-15 05:26:42'),
(29, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-15 05:27:21'),
(30, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-15 05:27:25'),
(31, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-15 05:28:56'),
(32, 6, 'login', NULL, '{\"username\":\"raj\"}', '::1', '2026-08-15 05:28:59'),
(33, 6, 'edit', 2, '{\"details\":\"Speed March: \'3\' -> \'1\' | Living Status: \'In Living\' -> \'Out Living\'\"}', '::1', '2026-08-15 05:29:16'),
(34, 6, 'logout', NULL, '{\"username\":\"raj\"}', '::1', '2026-08-15 05:29:19'),
(35, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-15 05:29:22'),
(36, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-15 05:32:42'),
(37, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-15 05:32:46'),
(38, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-15 05:34:20'),
(39, 1, 'login', NULL, '{\"username\":\"superadmin\"}', '::1', '2026-08-15 05:34:28'),
(40, 1, 'logout', NULL, '{\"username\":\"superadmin\"}', '::1', '2026-08-15 05:40:50'),
(41, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-15 05:40:59'),
(42, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-15 05:54:10'),
(43, 7, 'login', NULL, '{\"username\":\"op\"}', '::1', '2026-08-15 05:54:13'),
(44, 7, 'logout', NULL, '{\"username\":\"op\"}', '::1', '2026-08-15 05:54:26'),
(45, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 03:30:30'),
(46, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 03:31:23'),
(47, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 03:32:12'),
(48, 5, 'edit', 2, '{\"details\":\"Leaves updated\"}', '::1', '2026-08-16 03:33:01'),
(49, 5, 'edit', 5, '{\"details\":\"Leaves updated\"}', '::1', '2026-08-16 03:34:58'),
(50, 5, 'edit', 2, '{\"details\":\"Any Disease: \'Diabetic\' -> \'Diabetic, L4 & L5 Vertebra lower back pain\'\"}', '::1', '2026-08-16 03:38:27'),
(51, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 03:38:50'),
(52, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 03:38:57'),
(53, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 03:39:29'),
(54, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 03:39:59'),
(55, 5, 'edit', 6, '{\"details\":\"Leaves updated\"}', '::1', '2026-08-16 03:40:20'),
(56, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 03:40:51'),
(57, 7, 'login', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 03:40:54'),
(58, 7, 'logout', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 03:41:09'),
(59, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 03:41:14'),
(60, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 03:41:57'),
(61, 7, 'login', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 03:42:01'),
(62, 7, 'edit', 6, '{\"details\":\"Appointment: \'\' -> \'H\\/CLK\' | Cadre updated\"}', '::1', '2026-08-16 03:42:20'),
(63, 7, 'logout', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 03:42:27'),
(64, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 03:42:32'),
(65, 4, 'login', NULL, '{\"username\":\"palash\"}', '192.168.0.107', '2026-08-16 03:43:25'),
(66, 7, 'login', NULL, '{\"username\":\"op\"}', '192.168.0.175', '2026-08-16 03:47:02'),
(67, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 03:47:37'),
(68, 7, 'login', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 03:47:42'),
(69, 7, 'login', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 03:50:40'),
(70, 7, 'edit', 5, '{\"details\":\"Batch: \'\' -> \'51TH\' | Marriage Date: \'\' -> \'2004-03-25\' | Height Cm: \'\' -> \'175\' | Weight Kg: \'\' -> \'79\'\"}', '::1', '2026-08-16 03:52:21'),
(71, 7, 'logout', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 03:52:25'),
(72, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 03:53:57'),
(73, 4, 'edit', 5, '{\"details\":\"Marriage Date: \'2004-03-25\' -> \'2004-10-25\'\"}', '::1', '2026-08-16 03:54:39'),
(74, 7, 'login', NULL, '{\"username\":\"op\"}', '192.168.0.175', '2026-08-16 03:55:24'),
(75, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 03:55:45'),
(76, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 03:56:23'),
(77, 6, 'login', NULL, '{\"username\":\"raj\"}', '192.168.0.107', '2026-08-16 04:12:10'),
(78, 6, 'logout', NULL, '{\"username\":\"raj\"}', '192.168.0.107', '2026-08-16 04:12:31'),
(79, 6, 'login', NULL, '{\"username\":\"raj\"}', '192.168.0.107', '2026-08-16 04:12:40'),
(80, 1, 'login', NULL, '{\"username\":\"superadmin\"}', '192.168.0.107', '2026-08-16 04:18:32'),
(81, 5, 'login', NULL, '{\"username\":\"oc\"}', '192.168.0.107', '2026-08-16 04:27:43'),
(82, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 04:51:23'),
(83, 5, 'logout', NULL, '{\"username\":\"oc\"}', '192.168.0.107', '2026-08-16 04:51:36'),
(84, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 04:51:40'),
(85, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 04:51:56'),
(86, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 04:55:00'),
(87, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 04:59:01'),
(88, 1, 'login', NULL, '{\"username\":\"superadmin\"}', '::1', '2026-08-16 04:59:11'),
(89, 1, 'logout', NULL, '{\"username\":\"superadmin\"}', '::1', '2026-08-16 04:59:39'),
(90, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 04:59:43'),
(91, 5, 'edit', 6, '{\"details\":\"Rank: \'Sergeant (CLK)\' -> \'Sergeant\'\"}', '::1', '2026-08-16 05:07:56'),
(92, 5, 'edit', 6, '{\"details\":\"Leaves updated\"}', '::1', '2026-08-16 05:08:25'),
(93, 5, 'edit', 2, '{\"details\":\"Leaves updated\"}', '::1', '2026-08-16 05:09:13'),
(94, 5, 'edit', 2, '{\"details\":\"Leaves updated\"}', '::1', '2026-08-16 05:09:36'),
(95, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 05:10:21'),
(96, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 05:10:26'),
(97, 5, 'add', 7, '{\"details\":\"Added personnel: Dujahan Mia\"}', '::1', '2026-08-16 05:13:25'),
(98, 4, 'login', NULL, '{\"username\":\"palash\"}', '192.168.0.107', '2026-08-16 05:16:32'),
(99, 5, 'edit', 7, '{\"details\":\"Leaves updated | Family Member: \'\' -> \'No\'\"}', '::1', '2026-08-16 05:17:04'),
(100, 5, 'edit', 7, '{\"details\":\"Family Member: \'\' -> \'No\' | Leaves updated\"}', '::1', '2026-08-16 05:17:20'),
(101, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 06:55:51'),
(102, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 07:00:34'),
(103, 3, 'login', NULL, '{\"username\":\"user\"}', '::1', '2026-08-16 07:01:00'),
(104, 3, 'logout', NULL, '{\"username\":\"User\"}', '::1', '2026-08-16 07:01:24'),
(105, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 07:01:29'),
(106, 5, 'login', NULL, '{\"username\":\"oc\"}', '192.168.0.107', '2026-08-16 07:02:18'),
(107, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 07:11:41'),
(108, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 07:11:50'),
(109, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 07:11:58'),
(110, 5, 'login', NULL, '{\"username\":\"oc\"}', '192.168.0.115', '2026-08-16 07:13:49'),
(111, 4, 'login', NULL, '{\"username\":\"palash\"}', '192.168.0.115', '2026-08-16 07:14:36'),
(112, 5, 'login', NULL, '{\"username\":\"oc\"}', '192.168.0.107', '2026-08-16 07:16:44'),
(113, 5, 'logout', NULL, '{\"username\":\"oc\"}', '192.168.0.107', '2026-08-16 07:16:46'),
(114, 5, 'login', NULL, '{\"username\":\"oc\"}', '192.168.0.107', '2026-08-16 07:17:00'),
(115, 5, 'logout', NULL, '{\"username\":\"oc\"}', '192.168.0.107', '2026-08-16 07:17:09'),
(116, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 07:17:21'),
(117, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 07:17:28'),
(118, 5, 'login', NULL, '{\"username\":\"oc\"}', '192.168.0.107', '2026-08-16 07:18:18'),
(119, 5, 'logout', NULL, '{\"username\":\"oc\"}', '192.168.0.107', '2026-08-16 07:18:31'),
(120, 5, 'login', NULL, '{\"username\":\"oc\"}', '192.168.0.107', '2026-08-16 07:18:38'),
(121, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 07:19:12'),
(122, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 07:21:39'),
(123, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 07:21:46'),
(124, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 07:25:00'),
(125, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 07:25:04'),
(126, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 07:25:31'),
(127, 5, 'login', NULL, '{\"username\":\"oc\"}', '192.168.0.107', '2026-08-16 07:27:28'),
(128, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 07:29:40'),
(129, 5, 'login', NULL, '{\"username\":\"oc\"}', '192.168.0.107', '2026-08-16 07:30:42'),
(130, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 07:31:34'),
(131, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 07:34:24'),
(132, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 07:34:36'),
(133, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 07:35:01'),
(134, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 07:39:21'),
(135, 5, 'logout', NULL, '{\"username\":\"oc\"}', '192.168.0.107', '2026-08-16 07:49:11'),
(136, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 07:53:34'),
(137, 7, 'login', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 07:53:37'),
(138, 7, 'logout', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 07:53:45'),
(139, 3, 'login', NULL, '{\"username\":\"user\"}', '::1', '2026-08-16 07:53:51'),
(140, 3, 'logout', NULL, '{\"username\":\"User\"}', '::1', '2026-08-16 07:53:54'),
(141, 6, 'login', NULL, '{\"username\":\"raj\"}', '::1', '2026-08-16 07:53:58'),
(142, 6, 'logout', NULL, '{\"username\":\"raj\"}', '::1', '2026-08-16 07:54:03'),
(143, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 07:54:07'),
(144, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 08:02:00'),
(145, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 08:04:17'),
(146, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 08:07:35'),
(147, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 08:07:56'),
(148, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 08:08:02'),
(149, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 08:36:56'),
(150, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 08:42:45'),
(151, 5, 'login', NULL, '{\"username\":\"oc\"}', '192.168.68.104', '2026-08-16 08:43:11'),
(152, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 08:43:15'),
(153, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 08:43:31'),
(154, 7, 'login', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 08:43:35'),
(155, 5, 'login', NULL, '{\"username\":\"oc\"}', '192.168.68.104', '2026-08-16 08:43:36'),
(156, 7, 'edit', 7, '{\"details\":\"Cycle 1: \'\' -> \'Administration\'\"}', '::1', '2026-08-16 08:43:50'),
(157, 7, 'logout', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 08:44:04'),
(158, 6, 'login', NULL, '{\"username\":\"raj\"}', '::1', '2026-08-16 08:44:08'),
(159, 6, 'edit', 7, '{\"details\":\"Requested approval for edit: Speed March: \'\' -> \'1\' | Family Member: \'\' -> \'No\'\"}', '::1', '2026-08-16 08:44:17'),
(160, 5, 'logout', NULL, '{\"username\":\"oc\"}', '192.168.68.104', '2026-08-16 08:49:19'),
(161, 1, 'login', NULL, '{\"username\":\"superadmin\"}', '192.168.68.104', '2026-08-16 08:49:35'),
(162, 6, 'logout', NULL, '{\"username\":\"raj\"}', '::1', '2026-08-16 08:51:18'),
(163, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 13:46:11'),
(164, 4, 'reject', 7, '{\"approval_id\":11,\"type\":\"edit\",\"requested_by\":\"Raj Rahman\",\"details\":\"Speed March: \'\' -> \'1\' | Family Member: \'\' -> \'No\'\"}', '::1', '2026-08-16 13:46:58'),
(165, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 13:47:03'),
(166, 6, 'login', NULL, '{\"username\":\"raj\"}', '::1', '2026-08-16 13:47:07'),
(167, 6, 'edit', 7, '{\"details\":\"Requested approval for edit: Speed March: \'\' -> \'1\' | Family Member: \'\' -> \'No\'\"}', '::1', '2026-08-16 13:47:21'),
(168, 6, 'logout', NULL, '{\"username\":\"raj\"}', '::1', '2026-08-16 13:47:29'),
(169, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 13:47:34'),
(170, 5, 'reject', 7, '{\"approval_id\":12,\"type\":\"edit\",\"requested_by\":\"Raj Rahman\",\"details\":\"Speed March: \'\' -> \'1\'\"}', '::1', '2026-08-16 13:50:50'),
(171, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 13:50:52'),
(172, 6, 'login', NULL, '{\"username\":\"raj\"}', '::1', '2026-08-16 13:50:57'),
(173, 6, 'logout', NULL, '{\"username\":\"raj\"}', '::1', '2026-08-16 13:53:44'),
(174, 5, 'login', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 13:53:47'),
(175, 5, 'edit', 7, '{\"details\":\"Marital Status: \'\' -> \'married\'\"}', '::1', '2026-08-16 13:54:07'),
(176, 5, 'logout', NULL, '{\"username\":\"oc\"}', '::1', '2026-08-16 13:54:14'),
(177, 6, 'login', NULL, '{\"username\":\"raj\"}', '::1', '2026-08-16 13:54:17'),
(178, 6, 'logout', NULL, '{\"username\":\"raj\"}', '::1', '2026-08-16 13:54:25'),
(179, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 13:54:42'),
(180, 4, 'edit', 2, '{\"details\":\"Speed March changed from 1 to 3\"}', '::1', '2026-08-16 13:58:19'),
(181, 4, 'edit', 2, '{\"details\":\"Cadre updated, Speed March changed from 3 to 1\"}', '::1', '2026-08-16 13:58:59'),
(182, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 14:02:19'),
(183, 7, 'login', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 14:02:22'),
(184, 7, 'logout', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 14:02:36'),
(185, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 14:02:41'),
(186, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 14:05:07'),
(187, 8, 'login', NULL, '{\"username\":\"saiful\"}', '::1', '2026-08-16 14:05:14'),
(188, 8, 'logout', NULL, '{\"username\":\"saiful\"}', '::1', '2026-08-16 14:05:36'),
(189, 9, 'login', NULL, '{\"username\":\"rifat\"}', '::1', '2026-08-16 14:05:47'),
(190, 9, 'logout', NULL, '{\"username\":\"rifat\"}', '::1', '2026-08-16 14:10:18'),
(191, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 14:10:24'),
(192, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 14:16:42'),
(193, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 14:16:47'),
(194, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 14:27:33'),
(195, 7, 'login', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 14:34:39'),
(196, 7, 'logout', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 14:34:45'),
(197, 7, 'login', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 14:34:49'),
(198, 7, 'logout', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 14:36:27'),
(199, 4, 'login', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 14:36:31'),
(200, 4, 'logout', NULL, '{\"username\":\"palash\"}', '::1', '2026-08-16 14:43:17'),
(201, 7, 'login', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 14:43:58'),
(202, 7, 'logout', NULL, '{\"username\":\"op\"}', '::1', '2026-08-16 14:53:23');

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `name`) VALUES
(2, 'Chattogram'),
(1, 'Dhaka');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `name`) VALUES
(13, 'BK NCO'),
(11, 'CQ'),
(10, 'CSM'),
(15, 'H/CLK'),
(3, 'INT NCO'),
(12, 'MT NCO'),
(2, 'OC'),
(1, 'Pl Cmdr'),
(14, 'SJCO');

-- --------------------------------------------------------

--
-- Table structure for table `app_settings`
--

CREATE TABLE `app_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `app_settings`
--

INSERT INTO `app_settings` (`setting_key`, `setting_value`) VALUES
('app_logo', 'ti-shield-star'),
('app_logo_path', 'app_logo_1786854447.png'),
('app_name', 'Profile 99'),
('app_title', 'Unit Management System');

-- --------------------------------------------------------

--
-- Table structure for table `blood_groups`
--

CREATE TABLE `blood_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blood_groups`
--

INSERT INTO `blood_groups` (`id`, `name`) VALUES
(1, 'A+'),
(2, 'A-'),
(7, 'AB+'),
(8, 'AB-'),
(3, 'B+'),
(4, 'B-'),
(5, 'O+'),
(6, 'O-');

-- --------------------------------------------------------

--
-- Table structure for table `cadres`
--

CREATE TABLE `cadres` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cadres`
--

INSERT INTO `cadres` (`id`, `name`) VALUES
(5, 'AGL Cadre'),
(2, 'First Aid Cadre'),
(3, 'INT Cadre'),
(4, 'Metis Cadre'),
(1, 'MG Cadre');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `name`) VALUES
(1, 'ATGW 3'),
(7, 'AWGSS 11/23'),
(3, 'ICT NAC 5'),
(2, 'ICT NTC 19'),
(4, 'NCO\'s Advanced Course'),
(5, 'PT Course'),
(6, 'Sniper Course');

-- --------------------------------------------------------

--
-- Table structure for table `manpower_state`
--

CREATE TABLE `manpower_state` (
  `category` varchar(20) NOT NULL,
  `auth` int(11) DEFAULT 0,
  `posted` int(11) DEFAULT 0,
  `att` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `manpower_state`
--

INSERT INTO `manpower_state` (`category`, `auth`, `posted`, `att`) VALUES
('CK', 5, 5, 0),
('CLK', 2, 2, 1),
('CPL', 18, 19, 0),
('LCPL', 3, 3, 0),
('NC(E)', 3, 3, 0),
('NC(U)', 1, 1, 0),
('OFFR', 5, 1, 0),
('SGT', 11, 11, 0),
('SNK(GD)', 71, 71, 0),
('SWO', 1, 1, 0),
('WO', 3, 3, 0);

-- --------------------------------------------------------

--
-- Table structure for table `medical_categories`
--

CREATE TABLE `medical_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_categories`
--

INSERT INTO `medical_categories` (`id`, `name`) VALUES
(1, 'A');

-- --------------------------------------------------------

--
-- Table structure for table `moqs`
--

CREATE TABLE `moqs` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `moqs`
--

INSERT INTO `moqs` (`id`, `name`) VALUES
(2, 'Arms Commando'),
(5, 'ATT'),
(1, 'BTT'),
(8, 'CLM Cadre'),
(9, 'JCOC'),
(6, 'NCOC'),
(4, 'PC'),
(10, 'PE'),
(3, 'Services Commando'),
(7, 'Utility Course');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `description`) VALUES
(1, 'view_dashboard', 'Can view the main dashboard'),
(2, 'manage_users', 'Can create/edit/delete user accounts'),
(3, 'manage_roles', 'Can manage roles and permissions'),
(4, 'view_personnel', 'Can search and view personnel profiles'),
(5, 'add_personnel', 'Can add new personnel records'),
(7, 'delete_personnel', 'Can delete personnel records'),
(8, 'manage_options', 'Can add/edit lookup options (ranks, units, etc)'),
(9, 'app_settings', 'Can modify global app settings'),
(19, 'edit_personnel_basic', 'Can edit basic personal info (Name, Rank, Unit, Contact)'),
(20, 'edit_personnel_course', 'Can edit courses completed'),
(21, 'edit_personnel_education', 'Can edit civil education'),
(22, 'edit_personnel_service', 'Can edit service details (Admission, Retirement, UN Mission, Punishment)'),
(23, 'edit_personnel_family', 'Can edit family information'),
(24, 'edit_personnel_health', 'Can edit health and medical details'),
(25, 'edit_personnel_social', 'Can edit social links'),
(26, 'edit_personnel_notes', 'Can edit special notes'),
(27, 'edit_personnel_leaves', 'Can edit leave records'),
(28, 'edit_personnel_moqs', 'Can edit MOQs'),
(29, 'edit_personnel_cadres', 'Can edit Cadres'),
(30, 'edit_personnel_ipft', 'Can edit IPFT results'),
(31, 'edit_personnel_yearly_plan', 'Can edit Yearly Plan'),
(32, 'edit_personnel_family_member_status', 'Can edit Family Member status'),
(38, 'edit_personnel', 'Can edit existing personnel records'),
(42, 'edit_personnel_status', 'Can edit personnel status'),
(43, 'edit_manpower_state', 'Can edit manpower state'),
(44, 'approval', NULL),
(45, 'auto_approval', 'Auto Approval'),
(46, 'view_logs', 'View Activity Logs'),
(47, 'reset_logs', 'Reset Activity Logs'),
(48, 'force_logout_user', 'Can force logout specific users'),
(49, 'bulk_import', 'Can use bulk Excel import feature');

-- --------------------------------------------------------

--
-- Table structure for table `personnel`
--

CREATE TABLE `personnel` (
  `id` int(11) NOT NULL,
  `personal_number` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `rank_id` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `cadre_id` int(11) DEFAULT NULL,
  `platoon_id` int(11) DEFAULT NULL,
  `blood_group_id` int(11) DEFAULT NULL,
  `batch` varchar(100) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `appointment_id` int(11) DEFAULT NULL,
  `detailed_address` text DEFAULT NULL,
  `vill` varchar(255) DEFAULT NULL,
  `po` varchar(255) DEFAULT NULL,
  `ps` varchar(255) DEFAULT NULL,
  `nid` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personnel`
--

INSERT INTO `personnel` (`id`, `personal_number`, `name`, `photo_path`, `rank_id`, `unit_id`, `cadre_id`, `platoon_id`, `blood_group_id`, `batch`, `mobile_number`, `address`, `status`, `created_at`, `appointment_id`, `detailed_address`, `vill`, `po`, `ps`, `nid`) VALUES
(2, '4047810', 'Noor Mohammad Palash', '4047810_1785047584.jpg', 2, 4, NULL, 4, 3, '68TH', '01914331734', 'Gazipur', 'active', '2026-07-25 04:44:16', 3, NULL, 'Noyonpur', 'Rajendrapur Cantt', 'Gazipur', '5997683262'),
(5, 'BJO62143', 'Mohammad Kamruzzaman', 'BJO62143_1785818735.jpg', 7, 5, NULL, 5, 3, '51TH', '01916600701', 'Mymensingh', 'active', '2026-08-04 04:39:51', 14, NULL, 'Tarati', 'Gosai Candura', 'Ishwrganj', '3709501880'),
(6, '4043576', 'Md Mahabubul Haqu', '4043576_1785820568.jpg', 1, NULL, NULL, 5, 3, '64TH', '01623252944', 'Kishoreganj', 'active', '2026-08-04 05:12:34', 15, NULL, 'Kaykurdia', 'Jangal Bari', 'Karim Ganj', NULL),
(7, '4063639', 'Dujahan Mia', NULL, 4, NULL, NULL, 1, 3, '18/3', '01913159987', 'Sherpur', 'active', '2026-08-16 05:13:25', NULL, NULL, 'Bakshabaid', 'Doherper', 'Sreebordi', NULL),
(8, '4056907', 'Md Al Mamun Asad Sarkar', NULL, 4, 4, NULL, 2, 7, '16', '1309171806', 'Sirajganj', 'active', '2026-08-16 08:04:43', NULL, NULL, 'Teyashia', 'Kollanpur', 'Belkuci', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `personnel_approvals`
--

CREATE TABLE `personnel_approvals` (
  `id` int(11) NOT NULL,
  `action_type` enum('add','edit','delete') NOT NULL,
  `personnel_id` int(11) DEFAULT NULL,
  `proposed_data` longtext NOT NULL,
  `previous_data` longtext DEFAULT NULL,
  `requested_by` int(11) NOT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personnel_approvals`
--

INSERT INTO `personnel_approvals` (`id`, `action_type`, `personnel_id`, `proposed_data`, `previous_data`, `requested_by`, `requested_at`, `status`, `reviewed_by`, `reviewed_at`) VALUES
(1, 'edit', 2, '{\"csrf_token\":\"a6f703eaebd5bcd57a0a960357892529a0b594081138df900513e0b16a254574\",\"status\":\"active\",\"ipft_1st\":\"PASS\",\"ipft_2nd\":\"\",\"ret\":\"PASS\",\"speed_march\":\"2\",\"leaves\":{\"from_date\":[\"2026-08-14\",\"2026-08-06\",\"2026-08-02\",\"2026-07-31\",\"2026-06-09\",\"2026-05-06\"],\"to_date\":[\"2026-08-15\",\"2026-08-08\",\"2026-08-04\",\"2026-08-01\",\"2026-06-10\",\"2026-06-19\"],\"total_days\":[\"2\",\"3\",\"3\",\"2\",\"2\",\"45\"],\"leave_type\":[\"Weekend Leave\",\"Weekend Leave\",\"Weekend Leave\",\"Weekend Leave\",\"Weekend Leave\",\"Pre Leave\"]},\"family_member\":\"Yes\",\"fm_date_from\":\"2026-08-06\",\"fm_date_to\":\"2027-08-05\",\"fm_current_address\":\"12\\/3 Sankibhanga, Sohid Road, Beside Public School\",\"living_status\":\"In Living\"}', NULL, 6, '2026-08-14 18:09:32', 'rejected', 5, '2026-08-14 18:23:09'),
(2, 'edit', 5, '{\"csrf_token\":\"aa69463f98f9bccaffe7fc2e030a3d8e9fd4ab84872f0deb6df11cb616de6dbb\",\"name\":\"Mohammad Kamruzzaman\",\"personal_number\":\"BJO62143\",\"nid\":\"3709501880\",\"rank_id\":\"7\",\"unit_id\":\"5\",\"platoon_id\":\"5\",\"blood_group_id\":\"3\",\"status\":\"active\",\"appointment_id\":\"14\",\"mobile_number\":\"01916600701\",\"address\":\"Mymensingh\",\"batch\":\"58TH\",\"vill\":\"Tarati\",\"po\":\"Gosai Candura\",\"ps\":\"Ishwrganj\",\"cadre_ids\":[\"1\"],\"course_id\":[\"4\"],\"course_result\":{\"4\":\"B+\"},\"admission_date\":\"1998-11-01\",\"retirement_date\":\"2017-11-10\",\"un_mission\":\"MONUSCO\",\"punishment_note\":\"\",\"cycle_1\":\"\",\"cycle_2\":\"\",\"cycle_3\":\"\",\"cycle_4\":\"\",\"birthdate\":\"1980-10-20\",\"marriage_date\":\"\",\"marital_status\":\"married\",\"children_count\":\"2\",\"father_name\":\"Md Hafiz Uddin\",\"father_mobile\":\"01916600701\",\"mother_name\":\"Mst Hosne Ara Begum\",\"mother_mobile\":\"01916600701\",\"spouse_name\":\"Mst. Jhora Akhter\",\"spouse_mobile\":\"01916600701\",\"medical_category_id\":\"1\",\"height_cm\":\"\",\"weight_kg\":\"\",\"any_disease\":\"No\",\"social_links\":{\"facebook\":\"\",\"linkedin\":\"\",\"whatsapp\":\"https:\\/\\/wa.me\\/01916600701\",\"twitter\":\"\"},\"special_note\":\"He is performing the duties of an SM alongside the Senior JCO.\"}', NULL, 7, '2026-08-14 18:16:59', 'rejected', 5, '2026-08-14 18:29:11'),
(3, 'edit', 6, '{\"csrf_token\":\"96293886a7af7b04caac2849ef751f07aaebd6f520ed5cbc28abe474d031d3a0\",\"name\":\"Md Mahabubul Haqu\",\"personal_number\":\"4043576\",\"nid\":\"\",\"rank_id\":\"8\",\"unit_id\":\"5\",\"platoon_id\":\"5\",\"blood_group_id\":\"3\",\"status\":\"active\",\"appointment_id\":\"\",\"mobile_number\":\"01623252944\",\"address\":\"Kishoreganj\",\"batch\":\"64TH\",\"vill\":\"Kaykurdia\",\"po\":\"Jangal Bari\",\"ps\":\"Karim Ganj\",\"cadre_ids\":[\"2\"],\"admission_date\":\"2005-11-12\",\"retirement_date\":\"2032-11-11\",\"un_mission\":\"BANBAT 1\\/20\",\"punishment_note\":\"NO\",\"cycle_1\":\"Administration\",\"cycle_2\":\"Training\",\"cycle_3\":\"Administration\",\"cycle_4\":\"Pre Leave\",\"birthdate\":\"1987-01-27\",\"marriage_date\":\"2016-04-01\",\"marital_status\":\"married\",\"children_count\":\"2\",\"father_name\":\"Abul Kashem Badal\",\"father_mobile\":\"\",\"mother_name\":\"Monjura Begum\",\"mother_mobile\":\"\",\"spouse_name\":\"Nira Rahman\",\"spouse_mobile\":\"\",\"medical_category_id\":\"1\",\"height_cm\":\"175.00\",\"weight_kg\":\"81.00\",\"any_disease\":\"No\",\"social_links\":{\"facebook\":\"\",\"linkedin\":\"\",\"whatsapp\":\"\",\"twitter\":\"\"},\"special_note\":\"H\\/Clk\"}', NULL, 7, '2026-08-14 18:29:51', 'rejected', 5, '2026-08-14 18:32:45'),
(4, 'edit', 5, '{\"csrf_token\":\"8472e8c4086e4a2e66afbe1f852b1ddf888779cb51dce3d883f1fc2b1db55cee\",\"name\":\"Mohammad Kamruzzaman\",\"personal_number\":\"BJO62143\",\"nid\":\"3709501880\",\"rank_id\":\"7\",\"unit_id\":\"5\",\"platoon_id\":\"5\",\"blood_group_id\":\"3\",\"status\":\"active\",\"appointment_id\":\"14\",\"mobile_number\":\"01916600701\",\"address\":\"Mymensingh\",\"batch\":\"\",\"vill\":\"Tarati\",\"po\":\"Gosai Candura\",\"ps\":\"Ishwrganj\",\"cadre_ids\":[\"1\"],\"course_id\":[\"4\"],\"course_result\":{\"4\":\"B+\"},\"admission_date\":\"1998-11-01\",\"retirement_date\":\"2017-11-10\",\"un_mission\":\"MONUSCO\",\"punishment_note\":\"\",\"cycle_1\":\"\",\"cycle_2\":\"\",\"cycle_3\":\"\",\"cycle_4\":\"\",\"birthdate\":\"1980-10-20\",\"marriage_date\":\"\",\"marital_status\":\"married\",\"children_count\":\"2\",\"father_name\":\"Md Hafiz Uddin\",\"father_mobile\":\"01916600701\",\"mother_name\":\"Mst Hosne Ara Begum\",\"mother_mobile\":\"01916600701\",\"spouse_name\":\"Mst. Jhora Akhter\",\"spouse_mobile\":\"01916600701\",\"medical_category_id\":\"1\",\"height_cm\":\"\",\"weight_kg\":\"\",\"any_disease\":\"No\",\"social_links\":{\"facebook\":\"\",\"linkedin\":\"\",\"whatsapp\":\"https:\\/\\/wa.me\\/01916600701\",\"twitter\":\"\"},\"special_note\":\"He is performing the duties of an SM alongside the Senior JCO.\"}', NULL, 7, '2026-08-15 04:21:59', 'approved', 5, '2026-08-15 04:25:10'),
(5, 'edit', 6, '{\"csrf_token\":\"8786dd08bce1ca04295199d38c9f882df78a9a90f393b6969502da02dfdf89e7\",\"name\":\"Md Mahabubul Haqu\",\"personal_number\":\"4043576\",\"nid\":\"\",\"rank_id\":\"1\",\"unit_id\":\"\",\"platoon_id\":\"5\",\"blood_group_id\":\"3\",\"status\":\"active\",\"appointment_id\":\"\",\"mobile_number\":\"01623252944\",\"address\":\"Kishoreganj\",\"batch\":\"64TH\",\"vill\":\"Kaykurdia\",\"po\":\"Jangal Bari\",\"ps\":\"Karim Ganj\",\"admission_date\":\"2005-11-12\",\"retirement_date\":\"2032-11-11\",\"un_mission\":\"BANBAT 1\\/20\",\"punishment_note\":\"NO\",\"cycle_1\":\"\",\"cycle_2\":\"\",\"cycle_3\":\"\",\"cycle_4\":\"\",\"birthdate\":\"1987-01-27\",\"marriage_date\":\"2016-04-01\",\"marital_status\":\"married\",\"children_count\":\"2\",\"father_name\":\"Abul Kashem Badal\",\"father_mobile\":\"\",\"mother_name\":\"Monjura Begum\",\"mother_mobile\":\"\",\"spouse_name\":\"Nira Rahman\",\"spouse_mobile\":\"\",\"medical_category_id\":\"1\",\"height_cm\":\"175.00\",\"weight_kg\":\"81.00\",\"any_disease\":\"No\",\"social_links\":{\"facebook\":\"\",\"linkedin\":\"\",\"whatsapp\":\"\",\"twitter\":\"\"},\"special_note\":\"H\\/Clk\"}', NULL, 7, '2026-08-15 04:25:43', 'approved', 5, '2026-08-15 04:26:02'),
(6, 'edit', 2, '{\"csrf_token\":\"b4e33c01cd5da920cd607bc0bc906134a81ed8de1abd04b14c72ceecfd2859fb\",\"status\":\"active\",\"ipft_1st\":\"PASS\",\"ipft_2nd\":\"\",\"ret\":\"PASS\",\"speed_march\":\"1\",\"leaves\":{\"from_date\":[\"2026-08-14\",\"2026-08-06\",\"2026-08-02\",\"2026-07-31\",\"2026-06-09\",\"2026-05-06\"],\"to_date\":[\"2026-08-15\",\"2026-08-08\",\"2026-08-04\",\"2026-08-01\",\"2026-06-10\",\"2026-06-19\"],\"total_days\":[\"2\",\"3\",\"3\",\"2\",\"2\",\"45\"],\"leave_type\":[\"Weekend Leave\",\"Weekend Leave\",\"Weekend Leave\",\"Weekend Leave\",\"Weekend Leave\",\"Pre Leave\"]},\"family_member\":\"Yes\",\"fm_date_from\":\"2026-08-06\",\"fm_date_to\":\"2027-08-05\",\"fm_current_address\":\"12\\/3 Sankibhanga, Sohid Road, Beside Public School\",\"living_status\":\"In Living\"}', NULL, 6, '2026-08-15 04:33:42', 'rejected', 4, '2026-08-15 04:37:28'),
(7, 'edit', 2, '{\"csrf_token\":\"d9d43e217ac1b02ec7e69fb099c762fc17fe8c71994aa05720f672c04b24b7c9\",\"name\":\"Noor Mohammad Palash\",\"personal_number\":\"4047810\",\"nid\":\"5997683262\",\"rank_id\":\"2\",\"unit_id\":\"4\",\"platoon_id\":\"4\",\"blood_group_id\":\"3\",\"status\":\"active\",\"appointment_id\":\"3\",\"mobile_number\":\"01914331734\",\"address\":\"Gazipur\",\"batch\":\"68TH\",\"vill\":\"Noyonpur\",\"po\":\"Rajendrapur Cantt\",\"ps\":\"Gazipur\",\"cadre_ids\":[\"5\",\"2\",\"3\",\"4\"],\"course_id\":[\"1\",\"7\",\"3\",\"2\"],\"course_result\":{\"1\":\"B+ Y+\",\"7\":\"B+\",\"3\":\"B+\",\"2\":\"B+\"},\"moq_id\":[\"5\",\"10\"],\"moq_result\":{\"5\":\"Pass\",\"10\":\"PASS\"},\"admission_date\":\"2008-01-14\",\"retirement_date\":\"2031-01-14\",\"un_mission\":\"BANBAT 1\\/20\",\"punishment_note\":\"No\",\"cycle_1\":\"Administration\",\"cycle_2\":\"Pre Leave\",\"cycle_3\":\"Group Training\",\"cycle_4\":\"Administration\",\"birthdate\":\"1989-10-20\",\"marriage_date\":\"2024-10-21\",\"marital_status\":\"married\",\"children_count\":\"2\",\"father_name\":\"Md. Yousuf\",\"father_mobile\":\"01914331734\",\"mother_name\":\"Noor Nahar\",\"mother_mobile\":\"01914331734\",\"spouse_name\":\"Sharmin Zahan\",\"spouse_mobile\":\"01914331734\",\"medical_category_id\":\"1\",\"height_cm\":\"175.00\",\"weight_kg\":\"74.00\",\"any_disease\":\"Diabetic\",\"social_links\":{\"facebook\":\"https:\\/\\/web.whatsapp.com\",\"linkedin\":\"https:\\/\\/web.whatsapp.com\",\"whatsapp\":\"https:\\/\\/web.whatsapp.com\",\"twitter\":\"https:\\/\\/web.whatsapp.com\"},\"special_note\":\"Special Note\"}', NULL, 7, '2026-08-15 04:45:13', 'approved', 5, '2026-08-15 04:45:29'),
(8, 'edit', 6, '{\"csrf_token\":\"7da4fea4e6460b671e4815fa55aba9280c0a2acd5a1f8c27b5f5c6ae44e80be9\",\"name\":\"Md Mahabubul Haqu\",\"personal_number\":\"4043576\",\"nid\":\"\",\"rank_id\":\"8\",\"unit_id\":\"\",\"platoon_id\":\"5\",\"blood_group_id\":\"3\",\"status\":\"active\",\"appointment_id\":\"\",\"mobile_number\":\"01623252944\",\"address\":\"Kishoreganj\",\"batch\":\"64TH\",\"vill\":\"Kaykurdia\",\"po\":\"Jangal Bari\",\"ps\":\"Karim Ganj\",\"admission_date\":\"\",\"retirement_date\":\"\",\"un_mission\":\"\",\"punishment_note\":\"\",\"cycle_1\":\"Administration\",\"cycle_2\":\"Training\",\"cycle_3\":\"Administration\",\"cycle_4\":\"Pre Leave\",\"birthdate\":\"1987-01-27\",\"marriage_date\":\"2016-04-01\",\"marital_status\":\"married\",\"children_count\":\"2\",\"father_name\":\"Abul Kashem Badal\",\"father_mobile\":\"\",\"mother_name\":\"Monjura Begum\",\"mother_mobile\":\"\",\"spouse_name\":\"Nira Rahman\",\"spouse_mobile\":\"\",\"medical_category_id\":\"\",\"height_cm\":\"\",\"weight_kg\":\"\",\"any_disease\":\"\",\"social_links\":{\"facebook\":\"\",\"linkedin\":\"\",\"whatsapp\":\"\",\"twitter\":\"\"},\"special_note\":\"\"}', NULL, 7, '2026-08-15 05:11:42', 'rejected', 5, '2026-08-15 05:15:02'),
(9, 'edit', 6, '{\"csrf_token\":\"ed24f68a208b836a0cc5f4218002177d48ab5d976cc702a85e4889f807c5106b\",\"name\":\"Md Mahabubul Haqu\",\"personal_number\":\"4043576\",\"nid\":\"\",\"rank_id\":\"8\",\"unit_id\":\"\",\"platoon_id\":\"5\",\"blood_group_id\":\"3\",\"status\":\"active\",\"appointment_id\":\"\",\"mobile_number\":\"01623252944\",\"address\":\"Kishoreganj\",\"batch\":\"64TH\",\"vill\":\"Kaykurdia\",\"po\":\"Jangal Bari\",\"ps\":\"Karim Ganj\",\"admission_date\":\"\",\"retirement_date\":\"\",\"un_mission\":\"\",\"punishment_note\":\"\",\"cycle_1\":\"Administration\",\"cycle_2\":\"Training\",\"cycle_3\":\"Administration\",\"cycle_4\":\"Pre Leave\",\"birthdate\":\"1987-01-27\",\"marriage_date\":\"2016-04-01\",\"marital_status\":\"married\",\"children_count\":\"2\",\"father_name\":\"Abul Kashem Badal\",\"father_mobile\":\"\",\"mother_name\":\"Monjura Begum\",\"mother_mobile\":\"\",\"spouse_name\":\"Nira Rahman\",\"spouse_mobile\":\"\",\"medical_category_id\":\"\",\"height_cm\":\"\",\"weight_kg\":\"\",\"any_disease\":\"\",\"social_links\":{\"facebook\":\"\",\"linkedin\":\"\",\"whatsapp\":\"\",\"twitter\":\"\"},\"special_note\":\"\"}', NULL, 7, '2026-08-15 05:15:54', 'rejected', 5, '2026-08-15 05:26:37'),
(10, 'edit', 5, '{\"csrf_token\":\"ff22d23740ccdb0009c28ae8abcf68712d9ec012b457749064c8fda2c99da0f7\",\"name\":\"Mohammad Kamruzzaman\",\"personal_number\":\"BJO62143\",\"nid\":\"3709501880\",\"rank_id\":\"7\",\"unit_id\":\"5\",\"platoon_id\":\"5\",\"blood_group_id\":\"3\",\"status\":\"active\",\"appointment_id\":\"14\",\"mobile_number\":\"01916600701\",\"address\":\"Mymensingh\",\"batch\":\"\",\"vill\":\"Tarati\",\"po\":\"Gosai Candura\",\"ps\":\"Ishwrganj\",\"cadre_ids\":[\"2\",\"1\"],\"course_id\":[\"4\"],\"course_result\":{\"4\":\"B+\"},\"admission_date\":\"1998-11-01\",\"retirement_date\":\"2017-11-10\",\"un_mission\":\"MONUSCO\",\"punishment_note\":\"\",\"cycle_1\":\"\",\"cycle_2\":\"\",\"cycle_3\":\"\",\"cycle_4\":\"\",\"birthdate\":\"1980-10-20\",\"marriage_date\":\"\",\"marital_status\":\"married\",\"children_count\":\"2\",\"father_name\":\"Md Hafiz Uddin\",\"father_mobile\":\"01916600701\",\"mother_name\":\"Mst Hosne Ara Begum\",\"mother_mobile\":\"01916600701\",\"spouse_name\":\"Mst. Jhora Akhter\",\"spouse_mobile\":\"01916600701\",\"medical_category_id\":\"1\",\"height_cm\":\"\",\"weight_kg\":\"\",\"any_disease\":\"No\",\"social_links\":{\"facebook\":\"\",\"linkedin\":\"\",\"whatsapp\":\"https:\\/\\/wa.me\\/01916600701\",\"twitter\":\"\"},\"special_note\":\"He is performing the duties of an SM alongside the Senior JCO.\"}', NULL, 7, '2026-08-15 05:19:33', 'approved', 5, '2026-08-15 05:26:42'),
(11, 'edit', 7, '{\"csrf_token\":\"22def5a8337187a68467e9e5bcf76a229ea99a23120194ac224bce95c84de46a\",\"status\":\"active\",\"ipft_1st\":\"PASS\",\"ipft_2nd\":\"\",\"ret\":\"\",\"speed_march\":\"1\",\"leaves\":{\"from_date\":[\"\"],\"to_date\":[\"\"],\"total_days\":[\"\"],\"leave_type\":[\"\"]},\"family_member\":\"No\",\"fm_date_from\":\"\",\"fm_date_to\":\"\",\"fm_current_address\":\"\",\"living_status\":\"\"}', NULL, 6, '2026-08-16 08:44:17', 'rejected', 4, '2026-08-16 13:46:58'),
(12, 'edit', 7, '{\"csrf_token\":\"87df0a637fc9abd2597c87bbd3592164871d1cee41b57b58a4fd3e6ab1dec254\",\"status\":\"active\",\"ipft_1st\":\"PASS\",\"ipft_2nd\":\"\",\"ret\":\"\",\"speed_march\":\"1\",\"leaves\":{\"from_date\":[\"\"],\"to_date\":[\"\"],\"total_days\":[\"\"],\"leave_type\":[\"\"]},\"family_member\":\"No\",\"fm_date_from\":\"\",\"fm_date_to\":\"\",\"fm_current_address\":\"\",\"living_status\":\"\"}', NULL, 6, '2026-08-16 13:47:21', 'rejected', 5, '2026-08-16 13:50:50');

-- --------------------------------------------------------

--
-- Table structure for table `personnel_cadres`
--

CREATE TABLE `personnel_cadres` (
  `id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `cadre_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personnel_cadres`
--

INSERT INTO `personnel_cadres` (`id`, `personnel_id`, `cadre_id`) VALUES
(207, 5, 2),
(208, 5, 1),
(221, 2, 2),
(222, 2, 3),
(223, 2, 4);

-- --------------------------------------------------------

--
-- Table structure for table `personnel_courses`
--

CREATE TABLE `personnel_courses` (
  `id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `result` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personnel_courses`
--

INSERT INTO `personnel_courses` (`id`, `personnel_id`, `course_id`, `result`) VALUES
(216, 5, 4, 'B+'),
(229, 2, 1, 'B+ Y+'),
(230, 2, 7, 'B+'),
(231, 2, 3, 'B+'),
(232, 2, 2, 'B+');

-- --------------------------------------------------------

--
-- Table structure for table `personnel_education`
--

CREATE TABLE `personnel_education` (
  `id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `civil_education` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personnel_family`
--

CREATE TABLE `personnel_family` (
  `id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `birthdate` date DEFAULT NULL,
  `marriage_date` date DEFAULT NULL,
  `marital_status` enum('single','married','widowed','divorced') DEFAULT NULL,
  `parents_name` varchar(200) DEFAULT NULL,
  `children_count` int(11) DEFAULT 0,
  `family_member_notes` text DEFAULT NULL,
  `father_name` varchar(200) DEFAULT NULL,
  `father_mobile` varchar(30) DEFAULT NULL,
  `mother_name` varchar(200) DEFAULT NULL,
  `mother_mobile` varchar(30) DEFAULT NULL,
  `spouse_name` varchar(200) DEFAULT NULL,
  `spouse_mobile` varchar(30) DEFAULT NULL,
  `family_member` varchar(30) DEFAULT 'No',
  `living_status` varchar(30) DEFAULT NULL,
  `fm_date_from` date DEFAULT NULL,
  `fm_date_to` date DEFAULT NULL,
  `fm_current_address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personnel_family`
--

INSERT INTO `personnel_family` (`id`, `personnel_id`, `birthdate`, `marriage_date`, `marital_status`, `parents_name`, `children_count`, `family_member_notes`, `father_name`, `father_mobile`, `mother_name`, `mother_mobile`, `spouse_name`, `spouse_mobile`, `family_member`, `living_status`, `fm_date_from`, `fm_date_to`, `fm_current_address`) VALUES
(58, 5, '1980-10-20', '2004-10-25', 'married', NULL, 2, NULL, 'Md Hafiz Uddin', '01916600701', 'Mst Hosne Ara Begum', '01916600701', 'Mst. Jhora Akhter', '01916600701', 'No', NULL, NULL, NULL, NULL),
(59, 6, '1987-01-27', '2016-04-01', 'married', NULL, 2, NULL, 'Abul Kashem Badal', NULL, 'Monjura Begum', NULL, 'Nira Rahman', NULL, 'No', NULL, NULL, NULL, NULL),
(68, 2, '1989-10-20', '2024-10-21', 'married', NULL, 2, NULL, 'Md. Yousuf', '01914331734', 'Noor Nahar', '01914331734', 'Sharmin Zahan', '01914331734', 'Yes', 'Out Living', '2026-08-06', '2027-08-05', '12/3 Sankibhanga, Sohid Road, Beside Public School'),
(69, 8, '1997-05-08', '2023-05-01', 'married', NULL, 1, NULL, 'Md Alal Sarkar', '1748710092', 'Mst Momtaj Begum', NULL, 'Rifat Sultana Ritu', '1704319888', 'No', 'Out Living', NULL, NULL, 'no'),
(70, 7, NULL, NULL, 'married', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'No', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `personnel_health`
--

CREATE TABLE `personnel_health` (
  `id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `medical_category_id` int(11) DEFAULT NULL,
  `height_cm` decimal(5,2) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `any_disease` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personnel_health`
--

INSERT INTO `personnel_health` (`id`, `personnel_id`, `medical_category_id`, `height_cm`, `weight_kg`, `any_disease`) VALUES
(93, 5, 1, 175.00, 79.00, 'No'),
(96, 8, 1, 150.00, 72.00, 'no'),
(98, 2, 1, 175.00, 74.00, 'Diabetic, L4 & L5 Vertebra lower back pain');

-- --------------------------------------------------------

--
-- Table structure for table `personnel_leaves`
--

CREATE TABLE `personnel_leaves` (
  `id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `leave_type` varchar(100) DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `total_days` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personnel_leaves`
--

INSERT INTO `personnel_leaves` (`id`, `personnel_id`, `leave_type`, `from_date`, `to_date`, `total_days`, `created_at`) VALUES
(185, 5, 'Casual Leave', '2026-08-16', '2026-08-26', 11, '2026-08-16 03:54:39'),
(204, 2, 'Weekend Leave', '2026-08-14', '2026-08-15', 2, '2026-08-16 13:58:59'),
(205, 2, 'Weekend Leave', '2026-08-06', '2026-08-08', 3, '2026-08-16 13:58:59'),
(206, 2, 'Weekend Leave', '2026-08-02', '2026-08-04', 3, '2026-08-16 13:58:59'),
(207, 2, 'Weekend Leave', '2026-07-31', '2026-08-01', 2, '2026-08-16 13:58:59'),
(208, 2, 'Weekend Leave', '2026-06-09', '2026-06-10', 2, '2026-08-16 13:58:59');

-- --------------------------------------------------------

--
-- Table structure for table `personnel_moqs`
--

CREATE TABLE `personnel_moqs` (
  `id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `moq_id` int(11) NOT NULL,
  `result` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personnel_moqs`
--

INSERT INTO `personnel_moqs` (`id`, `personnel_id`, `moq_id`, `result`) VALUES
(38, 7, 2, 'PASS'),
(39, 7, 1, 'PASS'),
(42, 2, 5, 'Pass'),
(43, 2, 10, 'PASS');

-- --------------------------------------------------------

--
-- Table structure for table `personnel_notes`
--

CREATE TABLE `personnel_notes` (
  `id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personnel_notes`
--

INSERT INTO `personnel_notes` (`id`, `personnel_id`, `note`, `created_at`) VALUES
(84, 5, 'He is performing the duties of an SM alongside the Senior JCO.', '2026-08-16 03:54:39'),
(87, 8, 'aaa', '2026-08-16 08:04:43'),
(89, 2, 'Special Note', '2026-08-16 13:58:59');

-- --------------------------------------------------------

--
-- Table structure for table `personnel_service`
--

CREATE TABLE `personnel_service` (
  `id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `admission_date` date DEFAULT NULL,
  `retirement_date` date DEFAULT NULL,
  `un_mission` varchar(255) DEFAULT NULL,
  `punishment_note` text DEFAULT NULL,
  `ipft_1st` varchar(255) DEFAULT NULL,
  `ipft_2nd` varchar(255) DEFAULT NULL,
  `ret` varchar(255) DEFAULT NULL,
  `speed_march` varchar(255) DEFAULT NULL,
  `cycle_1` varchar(100) DEFAULT NULL,
  `cycle_2` varchar(100) DEFAULT NULL,
  `cycle_3` varchar(100) DEFAULT NULL,
  `cycle_4` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personnel_service`
--

INSERT INTO `personnel_service` (`id`, `personnel_id`, `admission_date`, `retirement_date`, `un_mission`, `punishment_note`, `ipft_1st`, `ipft_2nd`, `ret`, `speed_march`, `cycle_1`, `cycle_2`, `cycle_3`, `cycle_4`) VALUES
(109, 5, '1998-11-01', '2017-11-10', 'MONUSCO', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(111, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Administration', 'Training', 'Administration', 'Pre Leave'),
(117, 8, '2016-01-23', NULL, NULL, NULL, 'PASS', NULL, NULL, '1', 'Administration', 'Group Training', 'Training', 'Administration'),
(119, 7, '2018-07-22', NULL, NULL, NULL, 'PASS', NULL, NULL, NULL, 'Administration', NULL, NULL, NULL),
(121, 2, '2008-01-14', '2031-01-14', 'BANBAT 1/20', 'No', 'PASS', NULL, 'PASS', '1', 'Administration', 'Pre Leave', 'Group Training', 'Administration');

-- --------------------------------------------------------

--
-- Table structure for table `personnel_social_links`
--

CREATE TABLE `personnel_social_links` (
  `id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personnel_social_links`
--

INSERT INTO `personnel_social_links` (`id`, `personnel_id`, `platform`, `url`) VALUES
(228, 5, 'whatsapp', 'https://wa.me/01916600701'),
(241, 2, 'facebook', 'https://web.whatsapp.com'),
(242, 2, 'linkedin', 'https://web.whatsapp.com'),
(243, 2, 'whatsapp', 'https://web.whatsapp.com'),
(244, 2, 'twitter', 'https://web.whatsapp.com');

-- --------------------------------------------------------

--
-- Table structure for table `platoons`
--

CREATE TABLE `platoons` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `platoons`
--

INSERT INTO `platoons` (`id`, `name`) VALUES
(5, 'Coy HQ'),
(1, 'LMG'),
(4, 'Metis'),
(2, 'MG');

-- --------------------------------------------------------

--
-- Table structure for table `ranks`
--

CREATE TABLE `ranks` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ranks`
--

INSERT INTO `ranks` (`id`, `name`) VALUES
(12, 'ATT'),
(9, 'CK'),
(2, 'Corporal'),
(5, 'Lance Corporal'),
(3, 'Major'),
(10, 'NC(E)'),
(11, 'NC(U)'),
(4, 'Sainik'),
(7, 'Senior Warrant Officer'),
(1, 'Sergeant'),
(6, 'Warrant Officer');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(2, 'admin'),
(6, 'Daily'),
(5, 'operator'),
(1, 'superadmin'),
(4, 'techadmin'),
(3, 'user');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 7),
(1, 8),
(1, 9),
(1, 19),
(1, 20),
(1, 21),
(1, 22),
(1, 23),
(1, 24),
(1, 25),
(1, 26),
(1, 27),
(1, 28),
(1, 29),
(1, 30),
(1, 31),
(1, 32),
(1, 38),
(1, 42),
(1, 43),
(1, 44),
(1, 46),
(1, 47),
(1, 49),
(2, 1),
(2, 4),
(2, 5),
(2, 7),
(2, 8),
(2, 19),
(2, 20),
(2, 21),
(2, 22),
(2, 23),
(2, 24),
(2, 25),
(2, 26),
(2, 27),
(2, 28),
(2, 29),
(2, 30),
(2, 31),
(2, 32),
(2, 38),
(2, 42),
(2, 43),
(2, 44),
(2, 46),
(2, 49),
(3, 4),
(4, 1),
(4, 2),
(4, 3),
(4, 4),
(4, 5),
(4, 7),
(4, 8),
(4, 9),
(4, 19),
(4, 20),
(4, 21),
(4, 22),
(4, 23),
(4, 24),
(4, 25),
(4, 26),
(4, 27),
(4, 28),
(4, 29),
(4, 30),
(4, 31),
(4, 32),
(4, 38),
(4, 42),
(4, 43),
(4, 44),
(4, 46),
(4, 47),
(4, 48),
(4, 49),
(5, 1),
(5, 4),
(5, 5),
(5, 19),
(5, 20),
(5, 21),
(5, 22),
(5, 23),
(5, 24),
(5, 25),
(5, 26),
(5, 28),
(5, 29),
(5, 31),
(5, 45),
(6, 1),
(6, 4),
(6, 27),
(6, 30),
(6, 32),
(6, 42),
(6, 43);

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `name`) VALUES
(5, '12 EB'),
(4, '29 EB');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `status` enum('active','disabled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_token` varchar(255) DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password_hash`, `role_id`, `status`, `created_at`, `session_token`, `last_activity`) VALUES
(1, 'Super Admin', 'superadmin', '$2y$10$HcqBKaVW4.5g8IQCYfFwZeGbp09TNYA8sib9yWCoyqOhzTAgnFr3a', 1, 'active', '2026-07-23 07:03:57', '98b42de859cd9171a304c5cb4bd57febc781bb8b1ca3dd8bd21e75aa3dc98978', '2026-08-16 14:52:11'),
(2, 'Admin', 'admin', '$2y$10$BITdlv13i4ZZJERx/b3EKuZE1o/JR1.t8FGLLF58LjDCo2UuY1U16', 2, 'active', '2026-07-25 03:45:14', NULL, NULL),
(3, 'User', 'User', '$2y$10$H3Fx78KOV4PUqH/XbTNKleFOfkintOdQ63DDcHkabbDeVVjKg8fuO', 3, 'active', '2026-07-25 03:46:03', NULL, '2026-08-16 13:53:51'),
(4, 'Noor Palash', 'palash', '$2y$10$FpDsy7E3e3QqLObRK/L34.1nHnl4NtogxhvDDHIXa8b3X29gqn9vm', 4, 'active', '2026-07-28 03:39:23', NULL, '2026-08-16 20:36:31'),
(5, 'OC', 'oc', '$2y$10$OS5UlJ1qadP1uDNaG8J5R.dNK9d95094yU7Zs5DvwMh.s1QG85TiS', 2, 'active', '2026-08-04 04:08:58', NULL, '2026-08-16 19:53:47'),
(6, 'Raj Rahman', 'raj', '$2y$10$hL7/OeVPQDCS1m75AmxjG.mLb/wOdL.aHuFYk.sm1jT6T6kTScdZ6', 6, 'active', '2026-08-06 08:07:13', NULL, '2026-08-16 19:54:17'),
(7, 'OP', 'op', '$2y$10$A6X1x6IvEJqiIxv0vgNwuOErRGRw/NczHQXQhwdHPT2BWj700xaM.', 5, 'active', '2026-08-14 02:30:28', NULL, '2026-08-16 20:43:58'),
(8, 'Saiful', 'saiful', '$2y$10$/T7XK0502mBnSChzIPsBSuPiyllOy14fMjmQGE9Y8eIJj.X0.k5z6', 5, 'active', '2026-08-16 14:03:32', NULL, '2026-08-16 20:05:14'),
(9, 'Rifat', 'rifat', '$2y$10$RJMR.IW6aaCm.S1CltWvke4SWmksFCSKERRcbPD.70/31pokHRs3a', 5, 'active', '2026-08-16 14:03:54', NULL, '2026-08-16 20:05:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `target_personnel_id` (`target_personnel_id`);

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `blood_groups`
--
ALTER TABLE `blood_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `cadres`
--
ALTER TABLE `cadres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `manpower_state`
--
ALTER TABLE `manpower_state`
  ADD PRIMARY KEY (`category`);

--
-- Indexes for table `medical_categories`
--
ALTER TABLE `medical_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `moqs`
--
ALTER TABLE `moqs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `personnel`
--
ALTER TABLE `personnel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_number` (`personal_number`),
  ADD KEY `rank_id` (`rank_id`),
  ADD KEY `unit_id` (`unit_id`),
  ADD KEY `cadre_id` (`cadre_id`),
  ADD KEY `platoon_id` (`platoon_id`),
  ADD KEY `blood_group_id` (`blood_group_id`),
  ADD KEY `fk_personnel_appointment` (`appointment_id`);

--
-- Indexes for table `personnel_approvals`
--
ALTER TABLE `personnel_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `personnel_cadres`
--
ALTER TABLE `personnel_cadres`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personnel_id` (`personnel_id`),
  ADD KEY `cadre_id` (`cadre_id`);

--
-- Indexes for table `personnel_courses`
--
ALTER TABLE `personnel_courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personnel_id` (`personnel_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `personnel_education`
--
ALTER TABLE `personnel_education`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personnel_id` (`personnel_id`);

--
-- Indexes for table `personnel_family`
--
ALTER TABLE `personnel_family`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personnel_id` (`personnel_id`);

--
-- Indexes for table `personnel_health`
--
ALTER TABLE `personnel_health`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personnel_id` (`personnel_id`),
  ADD KEY `medical_category_id` (`medical_category_id`);

--
-- Indexes for table `personnel_leaves`
--
ALTER TABLE `personnel_leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personnel_id` (`personnel_id`);

--
-- Indexes for table `personnel_moqs`
--
ALTER TABLE `personnel_moqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personnel_id` (`personnel_id`),
  ADD KEY `moq_id` (`moq_id`);

--
-- Indexes for table `personnel_notes`
--
ALTER TABLE `personnel_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personnel_id` (`personnel_id`);

--
-- Indexes for table `personnel_service`
--
ALTER TABLE `personnel_service`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personnel_id` (`personnel_id`);

--
-- Indexes for table `personnel_social_links`
--
ALTER TABLE `personnel_social_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personnel_id` (`personnel_id`);

--
-- Indexes for table `platoons`
--
ALTER TABLE `platoons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `ranks`
--
ALTER TABLE `ranks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=203;

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `blood_groups`
--
ALTER TABLE `blood_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cadres`
--
ALTER TABLE `cadres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `medical_categories`
--
ALTER TABLE `medical_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `moqs`
--
ALTER TABLE `moqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `personnel`
--
ALTER TABLE `personnel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `personnel_approvals`
--
ALTER TABLE `personnel_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `personnel_cadres`
--
ALTER TABLE `personnel_cadres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=224;

--
-- AUTO_INCREMENT for table `personnel_courses`
--
ALTER TABLE `personnel_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=233;

--
-- AUTO_INCREMENT for table `personnel_education`
--
ALTER TABLE `personnel_education`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personnel_family`
--
ALTER TABLE `personnel_family`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `personnel_health`
--
ALTER TABLE `personnel_health`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `personnel_leaves`
--
ALTER TABLE `personnel_leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=209;

--
-- AUTO_INCREMENT for table `personnel_moqs`
--
ALTER TABLE `personnel_moqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `personnel_notes`
--
ALTER TABLE `personnel_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `personnel_service`
--
ALTER TABLE `personnel_service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `personnel_social_links`
--
ALTER TABLE `personnel_social_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=245;

--
-- AUTO_INCREMENT for table `platoons`
--
ALTER TABLE `platoons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ranks`
--
ALTER TABLE `ranks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_logs_ibfk_2` FOREIGN KEY (`target_personnel_id`) REFERENCES `personnel` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `personnel`
--
ALTER TABLE `personnel`
  ADD CONSTRAINT `fk_personnel_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`),
  ADD CONSTRAINT `personnel_ibfk_1` FOREIGN KEY (`rank_id`) REFERENCES `ranks` (`id`),
  ADD CONSTRAINT `personnel_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`),
  ADD CONSTRAINT `personnel_ibfk_3` FOREIGN KEY (`cadre_id`) REFERENCES `cadres` (`id`),
  ADD CONSTRAINT `personnel_ibfk_4` FOREIGN KEY (`platoon_id`) REFERENCES `platoons` (`id`),
  ADD CONSTRAINT `personnel_ibfk_5` FOREIGN KEY (`blood_group_id`) REFERENCES `blood_groups` (`id`);

--
-- Constraints for table `personnel_approvals`
--
ALTER TABLE `personnel_approvals`
  ADD CONSTRAINT `personnel_approvals_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `personnel_approvals_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `personnel_cadres`
--
ALTER TABLE `personnel_cadres`
  ADD CONSTRAINT `personnel_cadres_ibfk_1` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `personnel_cadres_ibfk_2` FOREIGN KEY (`cadre_id`) REFERENCES `cadres` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `personnel_courses`
--
ALTER TABLE `personnel_courses`
  ADD CONSTRAINT `personnel_courses_ibfk_1` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `personnel_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`);

--
-- Constraints for table `personnel_education`
--
ALTER TABLE `personnel_education`
  ADD CONSTRAINT `personnel_education_ibfk_1` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `personnel_family`
--
ALTER TABLE `personnel_family`
  ADD CONSTRAINT `personnel_family_ibfk_1` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `personnel_health`
--
ALTER TABLE `personnel_health`
  ADD CONSTRAINT `personnel_health_ibfk_1` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `personnel_health_ibfk_2` FOREIGN KEY (`medical_category_id`) REFERENCES `medical_categories` (`id`);

--
-- Constraints for table `personnel_leaves`
--
ALTER TABLE `personnel_leaves`
  ADD CONSTRAINT `personnel_leaves_ibfk_1` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `personnel_moqs`
--
ALTER TABLE `personnel_moqs`
  ADD CONSTRAINT `personnel_moqs_ibfk_1` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `personnel_moqs_ibfk_2` FOREIGN KEY (`moq_id`) REFERENCES `moqs` (`id`);

--
-- Constraints for table `personnel_notes`
--
ALTER TABLE `personnel_notes`
  ADD CONSTRAINT `personnel_notes_ibfk_1` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `personnel_service`
--
ALTER TABLE `personnel_service`
  ADD CONSTRAINT `personnel_service_ibfk_1` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `personnel_social_links`
--
ALTER TABLE `personnel_social_links`
  ADD CONSTRAINT `personnel_social_links_ibfk_1` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
