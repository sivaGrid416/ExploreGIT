-- phpMyAdmin SQL Dump
-- version 4.0.10.20
-- https://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 26, 2022 at 05:26 AM
-- Server version: 5.1.73
-- PHP Version: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `arattukulam53db`
--

-- --------------------------------------------------------

--
-- Table structure for table `users_in_queue`
--

CREATE TABLE IF NOT EXISTS `users_in_queue` (
  `user_queue_id` int(11) NOT NULL AUTO_INCREMENT,
  `queue_id` int(11) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `date_time` datetime NOT NULL,
  `start_time` varchar(11) NOT NULL,
  `end_time` varchar(11) NOT NULL,
  `status` int(11) DEFAULT '0',
  `day` varchar(20) NOT NULL,
  PRIMARY KEY (`user_queue_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1413 ;

--
-- Dumping data for table `users_in_queue`
--

INSERT INTO `users_in_queue` (`user_queue_id`, `queue_id`, `user_id`, `date_time`, `start_time`, `end_time`, `status`, `day`) VALUES
(1381, 11, '8861205390', '2022-11-25 12:45:54', '8', '17', 0, 'mon'),
(1390, 11, '7406274400', '2022-11-25 12:50:05', '9', '18', 0, 'mon'),
(1391, 11, '7406273324', '2022-11-25 12:50:11', '10', '19', 0, 'mon'),
(1392, 11, '7406273334', '2022-11-25 12:50:15', '11', '20', 0, 'mon'),
(1393, 11, '7406273311', '2022-11-25 12:50:20', '12', '21', 0, 'mon'),
(1394, 11, '7406273336', '2022-11-25 12:50:24', '13', '22', 0, 'mon'),
(1395, 11, '9880657187', '2022-11-25 12:50:31', '21', '24', 0, 'mon'),
(1396, 11, '7406273335', '2022-11-25 12:56:06', '8', '17', 0, 'mon'),
(1397, 11, '8861205390', '2022-11-25 12:56:06', '8', '17', 0, 'mon'),
(1399, 11, '7259433006', '2022-11-25 12:56:22', '0', '8', 0, 'mon'),
(1400, 11, '7406273324', '2022-11-25 12:56:22', '0', '8', 0, 'mon'),
(1401, 11, '7406273335', '2022-11-25 12:56:32', '9', '18', 0, 'mon'),
(1402, 11, '9880657187', '2022-11-25 12:56:32', '9', '18', 0, 'mon'),
(1403, 11, '8861205390', '2022-11-25 12:56:38', '10', '19', 0, 'mon'),
(1404, 11, '7406274400', '2022-11-25 12:56:42', '10', '19', 0, 'mon'),
(1405, 11, '7406273332', '2022-11-25 12:57:01', '11', '20', 0, 'mon'),
(1406, 11, '7406274400', '2022-11-25 12:57:01', '11', '20', 0, 'mon'),
(1407, 11, '7406273330', '2022-11-25 12:57:11', '12', '21', 0, 'mon'),
(1408, 11, '7406273336', '2022-11-25 12:57:11', '12', '21', 0, 'mon'),
(1409, 11, '7406273311', '2022-11-25 12:57:19', '13', '22', 0, 'mon'),
(1410, 11, '7406273324', '2022-11-25 12:57:19', '13', '22', 0, 'mon'),
(1411, 11, '8861205390', '2022-11-25 12:57:19', '13', '22', 0, 'mon'),
(1412, 11, '7406273334', '2022-11-25 12:57:26', '21', '24', 0, 'mon');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
