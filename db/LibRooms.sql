-- phpMyAdmin SQL Dump
-- version 3.5.2.2
-- http://www.phpmyadmin.net
--
-- Host: mysql.lib.pdx.edu
-- Generation Time: Sep 24, 2014 at 10:05 AM
-- Server version: 5.1.73
-- PHP Version: 5.3.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `sr`
--

-- --------------------------------------------------------

--
-- Table structure for table `amenities`
--

CREATE TABLE IF NOT EXISTS `amenities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` mediumtext,
  `search_filter` varchar(3) NOT NULL DEFAULT 'No',
  `date_added` datetime NOT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` varchar(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=20 ;

-- --------------------------------------------------------

--
-- Table structure for table `email_log`
--

CREATE TABLE IF NOT EXISTS `email_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` mediumtext NOT NULL,
  `date_added` datetime NOT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` char(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=75409 ;

-- --------------------------------------------------------

--
-- Table structure for table `email_templates`
--

CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` mediumtext NOT NULL,
  `tokens` varchar(255) NOT NULL,
  `example` mediumtext NOT NULL,
  `date_added` datetime NOT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` char(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Table structure for table `fines`
--

CREATE TABLE IF NOT EXISTS `fines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) NOT NULL,
  `amount` varchar(50) NOT NULL,
  `description` mediumtext,
  `resolved` char(1) DEFAULT '0',
  `date_added` datetime NOT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` char(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2334 ;

-- --------------------------------------------------------

--
-- Table structure for table `fines_reductions`
--

CREATE TABLE IF NOT EXISTS `fines_reductions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fine_id` int(11) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `description` mediumtext,
  `date_added` datetime NOT NULL,
  `added_by` int(11) NOT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` char(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=32 ;

-- --------------------------------------------------------

--
-- Table structure for table `hours`
--

CREATE TABLE IF NOT EXISTS `hours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `open_time` datetime NOT NULL,
  `close_time` datetime NOT NULL,
  `closed` char(1) NOT NULL DEFAULT '0',
  `closure_reason` varchar(255) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` char(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3018 ;

-- --------------------------------------------------------

--
-- Table structure for table `psu_hours`
--

CREATE TABLE IF NOT EXISTS `psu_hours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dept` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'library',
  `date` date NOT NULL,
  `open_hour` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `close_hour` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `closed` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `exception` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT 'Are the hours for this day irregular?',
  `exception_reason` mediumtext COLLATE utf8_unicode_ci,
  `entry_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `active` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2250 ;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE IF NOT EXISTS `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `room_section` int(11) NOT NULL DEFAULT '1',
  `user_id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `sched_start_time` datetime NOT NULL,
  `sched_end_time` datetime NOT NULL,
  `key_barcode` varchar(50) DEFAULT NULL,
  `key_checkout_time` datetime DEFAULT NULL,
  `key_checkout_by` int(11) DEFAULT NULL,
  `key_checkin_time` datetime DEFAULT NULL,
  `key_checkin_by` int(11) DEFAULT NULL,
  `cancelled` char(1) NOT NULL DEFAULT '0',
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `number_of_attendees` varchar(255) DEFAULT NULL,
  `notes` mediumtext,
  `status` varchar(255) DEFAULT NULL,
  `mobile` char(1) NOT NULL DEFAULT '0',
  `date_added` datetime NOT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` char(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=67619 ;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` char(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `rooms_keys`
--

CREATE TABLE IF NOT EXISTS `rooms_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_barcode` varchar(50) NOT NULL,
  `room_id` int(11) NOT NULL,
  `status` varchar(255) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` char(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=223 ;

-- --------------------------------------------------------

--
-- Table structure for table `room_groups`
--

CREATE TABLE IF NOT EXISTS `room_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `ordering` int(11) NOT NULL DEFAULT '999999',
  `date_added` datetime NOT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` char(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Table structure for table `room_images`
--

CREATE TABLE IF NOT EXISTS `room_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `format` varchar(255) NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `ordering` int(11) NOT NULL DEFAULT '9999',
  `date_added` datetime NOT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` varchar(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=318 ;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `description` mediumtext NOT NULL,
  `ordering` int(11) NOT NULL DEFAULT '99999',
  `date_added` datetime NOT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` char(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=21 ;

-- --------------------------------------------------------

--
-- Table structure for table `status_history`
--

CREATE TABLE IF NOT EXISTS `status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) NOT NULL,
  `status` varchar(255) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `changed_by` int(11) NOT NULL,
  `active` char(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=172458 ;

-- --------------------------------------------------------

--
-- Table structure for table `study_rooms`
--

CREATE TABLE IF NOT EXISTS `study_rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_group_id` int(11) DEFAULT '4',
  `room_number` varchar(10) DEFAULT NULL,
  `room_name` varchar(255) DEFAULT NULL,
  `room_description` varchar(255) DEFAULT NULL,
  `capacity` tinyint(4) DEFAULT NULL,
  `min_res_len` int(11) DEFAULT NULL,
  `max_res_len` int(11) DEFAULT NULL,
  `out_of_order` varchar(3) NOT NULL DEFAULT 'Yes',
  `fcfs` varchar(3) NOT NULL DEFAULT 'No',
  `max_simultaneous_reservations` tinyint(4) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` char(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=37 ;

-- --------------------------------------------------------

--
-- Table structure for table `study_rooms_amenities`
--

CREATE TABLE IF NOT EXISTS `study_rooms_amenities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `amenity_id` int(11) NOT NULL,
  `quantity` tinyint(4) NOT NULL DEFAULT '1',
  `ordering` int(11) NOT NULL DEFAULT '9999',
  `date_added` datetime NOT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` char(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=128 ;

-- --------------------------------------------------------

--
-- Table structure for table `transaction_log`
--

CREATE TABLE IF NOT EXISTS `transaction_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(255) CHARACTER SET latin1 NOT NULL,
  `row_id` int(11) DEFAULT NULL,
  `operation` varchar(255) CHARACTER SET latin1 NOT NULL,
  `query` longtext CHARACTER SET latin1 NOT NULL,
  `log_message` mediumtext CHARACTER SET latin1,
  `user_id` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `active` char(1) CHARACTER SET latin1 NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=802780 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `patron_id` varchar(255) DEFAULT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `ptype` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` char(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10037 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_roles`
--

CREATE TABLE IF NOT EXISTS `users_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` char(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=98 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
