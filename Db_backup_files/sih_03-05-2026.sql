-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 04, 2026 at 12:36 AM
-- Server version: 8.3.0
-- PHP Version: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sih`
--

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `s_pr_add_disease`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `s_pr_add_disease` (IN `p_disease_name` VARCHAR(100), IN `p_one_line_description` VARCHAR(1000), IN `p_description` TEXT, IN `p_causes` TEXT, IN `p_symptoms` TEXT, IN `p_prevention` TEXT, IN `p_user_id` INT, IN `p_cur_datetime` DATETIME)   BEGIN
	insert into `diseases`(
			`disease_name`,
			`description`,
			`one_line_description`,
			`causes`,
			`symptoms`,
			`prevention`,
			`created_on`,
			`created_by`
		)
	values(
			p_disease_name,
			p_description,
			p_one_line_description,
			p_causes,
			p_symptoms,
			p_prevention,
			p_cur_datetime,
			p_user_id
		);	
END$$

DROP PROCEDURE IF EXISTS `s_pr_add_prediction`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `s_pr_add_prediction` (IN `p_image` VARCHAR(100), IN `p_user_id` INT, IN `p_cur_datetime` DATETIME)   BEGIN
	insert into `prediction_master`(
			`image`,
			`created_by`,
			`created_on`
		)
		values(
			p_image,
			p_user_id,
			p_cur_datetime
		);
		
		select last_insert_id() as id;
END$$

DROP PROCEDURE IF EXISTS `s_pr_add_prediction_details`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `s_pr_add_prediction_details` (IN `p_pre_ms_id` INT, IN `p_predicted_disease` VARCHAR(100), IN `p_confidence_percent` VARCHAR(100), IN `p_user_id` INT, IN `p_cur_datetime` DATETIME)   BEGIN
	insert into `prediction_details`(
				`pre_ms_id`,
				`predicted_disease`,
				`confidence_percent`,
				`created_by`,
				`created_on`
			)
		values(
				p_pre_ms_id,
				p_predicted_disease,
				p_confidence_percent,
				p_user_id,
				p_cur_datetime
		
			);
END$$

DROP PROCEDURE IF EXISTS `s_pr_add_user`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `s_pr_add_user` (IN `p_email` VARCHAR(100), IN `p_name` VARCHAR(100), IN `p_password` VARCHAR(100), IN `p_number` VARCHAR(10), IN `p_role` VARCHAR(50), IN `p_user_id` INT, IN `p_cur_datetime` DATETIME, OUT `p_status_code` INT, OUT `p_msg` VARCHAR(50))   BEGIN
	DECLARE v_exists BOOL;

	SET v_exists = IF((SELECT COUNT(user_id) FROM users WHERE users.`email` = p_email AND is_active = 'Y') > 0, TRUE, FALSE);

	IF v_exists IS true THEN
		
		set p_status_code = 409;
		set p_msg = "Email Already Exists";
	
	elseif  v_exists IS false THEN 
		
		insert into users(
				`user_name`,
				`email`,
				`password`,
				`role`,
				`mb_number`,
				`created_on`,
				`created_by`
			)
			value(
				p_name,
				p_email,
				p_password,
				p_role,
				p_number,
				p_cur_datetime,
				p_user_id				
			);
		SET p_status_code = 200;
		SET p_msg = "User Added Successfuly.";	
		
	END IF;

	
	
 
END$$

DROP PROCEDURE IF EXISTS `s_pr_get_all_diseases`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `s_pr_get_all_diseases` ()   BEGIN
	select * from diseases where is_active = 'Y';
	
 
END$$

DROP PROCEDURE IF EXISTS `s_pr_get_all_users`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `s_pr_get_all_users` ()   BEGIN
	SELECT * FROM users WHERE role !="admin" and is_active="Y";
END$$

DROP PROCEDURE IF EXISTS `s_pr_get_all_video_tutorial`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `s_pr_get_all_video_tutorial` ()   BEGIN
	select 
		video_tutorial.`title`,	
		video_tutorial.`description`,
		video_tutorial.`thumbnail`,
		video_tutorial.`video`,
		video_tutorial.`video_tutorial_id`
	from `video_tutorial`
	where is_active='Y'
	order by `video_tutorial_id` desc;	
END$$

DROP PROCEDURE IF EXISTS `s_pr_get_disease`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `s_pr_get_disease` (IN `p_disease_id` INT)   BEGIN
	select * from `diseases` where `diseases_id`=p_disease_id;	
END$$

DROP PROCEDURE IF EXISTS `s_pr_get_diseases`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `s_pr_get_diseases` (IN `p_name` VARCHAR(100))   BEGIN
	select
		`diseases_id`,
		`disease_name`,
		`description`,
		`causes`,
		`symptoms`,
		`prevention`,
		one_line_description
	from `diseases`
	where is_active="Y"
	and `disease_name`=p_name;
END$$

DROP PROCEDURE IF EXISTS `s_pr_get_user`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `s_pr_get_user` (IN `p_user_id` INT)   BEGIN
	SELECT * FROM users WHERE users.`user_id`=p_user_id AND is_active="Y";
END$$

DROP PROCEDURE IF EXISTS `s_pr_get_video_tutorial`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `s_pr_get_video_tutorial` (IN `p_video_tutorial_id` INT)   BEGIN
	SELECT 
		video_tutorial.`title`,	
		video_tutorial.`description`,
		video_tutorial.`thumbnail`,
		video_tutorial.`video`,
		video_tutorial.`video_tutorial_id`
	FROM `video_tutorial`
	WHERE is_active='Y'
	and video_tutorial_id=p_video_tutorial_id;	
END$$

DROP PROCEDURE IF EXISTS `s_pr_login`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `s_pr_login` (IN `p_email` VARCHAR(100), IN `p_password` VARCHAR(100))   BEGIN
	select * from users where `email`=p_email and `password`=p_password and is_active="Y";
END$$

DROP PROCEDURE IF EXISTS `s_pr_signup`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `s_pr_signup` (IN `p_name` VARCHAR(100), IN `p_email` VARCHAR(100), IN `p_password` VARCHAR(100), IN `p_role` VARCHAR(50), IN `p_cur_datetime` DATETIME, OUT `p_status_code` INT, OUT `p_msg` VARCHAR(50))   BEGIN
	DECLARE v_exists BOOL;

	SET v_exists = IF((SELECT COUNT(user_id) FROM users WHERE users.`email` = p_email AND is_active = 'Y') > 0, TRUE, FALSE);

	IF v_exists IS TRUE THEN
		
		SET p_status_code = 409;
		SET p_msg = "Email Already Exists";
	
	ELSEIF  v_exists IS FALSE THEN 
		
		INSERT INTO users(
				`user_name`,
				`email`,
				`password`,
				`role`,
				`created_on`
			)
			VALUE(
				p_name,
				p_email,
				p_password,
				p_role,
				p_cur_datetime			
			);
		SET p_status_code = 200;
		SET p_msg = "User Added Successfuly.";	
		
	END IF;

	
	
 
END$$

DROP PROCEDURE IF EXISTS `s_pr_update_disease`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `s_pr_update_disease` (IN `p_id` INT, IN `p_disease_name` VARCHAR(100), IN `p_one_line_description` VARCHAR(1000), IN `p_description` TEXT, IN `p_causes` TEXT, IN `p_symptoms` TEXT, IN `p_prevention` TEXT, IN `p_user_id` INT, IN `p_cur_datetime` DATETIME)   BEGIN
	update `diseases` set 
			`disease_name`= p_disease_name,
			`description`= p_description,
			`one_line_description`= p_one_line_description,
			`causes`= p_causes,
			`symptoms`= p_symptoms,
			`prevention`=p_prevention ,
			`modified_on`= p_cur_datetime,
			`modified_by`= p_user_id
	where `diseases_id`=p_id;		
END$$

DROP PROCEDURE IF EXISTS `s_pr_update_user`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `s_pr_update_user` (IN `p_id` INT, IN `p_email` VARCHAR(100), IN `p_name` VARCHAR(100), IN `p_password` VARCHAR(100), IN `p_number` VARCHAR(10), IN `p_role` VARCHAR(50), IN `p_user_id` INT, IN `p_cur_datetime` DATETIME, OUT `p_status_code` INT, OUT `p_msg` VARCHAR(50))   BEGIN
	DECLARE v_exists BOOL;

	SET v_exists = IF((SELECT COUNT(user_id) FROM users WHERE users.`email` = p_email AND user_id != p_id and is_active = 'Y') > 0, TRUE, FALSE);

	IF v_exists IS TRUE THEN
		
		SET p_status_code = 409;
		SET p_msg = "Email Already Exists";
	
	ELSEIF  v_exists IS FALSE THEN 
		
		if p_password is not null and length(p_password) > 0 then
			update users set
					`user_name`=p_name,
					`email`=p_email,
					`password`=p_password,
					`role`=p_role,
					`mb_number`=p_number,
					`modified_on`=p_cur_datetime,
					`modified_by`=p_user_id
				where users.`user_id`=p_id;
		else
			UPDATE users SET
					`user_name`=p_name,
					`email`=p_email,
					`role`=p_role,
					`mb_number`=p_number,
					`modified_on`=p_cur_datetime,
					`modified_by`=p_user_id
				WHERE users.`user_id`=p_id;
		end if;		
			
		SET p_status_code = 200;
		SET p_msg = "User Updated Successfuly.";	
		
	END IF;

	
	
 
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE IF NOT EXISTS `announcements` (
  `announcement_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_role` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender_id` int NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_on` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int NOT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`announcement_id`),
  KEY `idx_active_created` (`is_active`,`created_on`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `description`, `target_role`, `sender_id`, `is_active`, `created_on`, `modified_on`, `created_by`, `modified_by`) VALUES
(1, 'Testing annousment', 'testing description', 'all', 1, 1, '2026-05-03 23:36:26', NULL, 1, NULL),
(2, 'Testing annousment', 'testing description', 'all', 1, 1, '2026-05-03 23:39:12', NULL, 1, NULL),
(3, 'Testing annousment', 'testing description', 'all', 1, 1, '2026-05-03 23:45:29', NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `announcement_reads`
--

DROP TABLE IF EXISTS `announcement_reads`;
CREATE TABLE IF NOT EXISTS `announcement_reads` (
  `read_id` int NOT NULL AUTO_INCREMENT,
  `announcement_id` int NOT NULL,
  `user_id` int NOT NULL,
  `read_on` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`read_id`),
  UNIQUE KEY `unique_read` (`announcement_id`,`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcement_reads`
--

INSERT INTO `announcement_reads` (`read_id`, `announcement_id`, `user_id`, `read_on`) VALUES
(1, 2, 9, '2026-05-03 23:43:20'),
(2, 1, 9, '2026-05-03 23:43:21'),
(3, 2, 10, '2026-05-03 23:44:41'),
(4, 1, 10, '2026-05-03 23:44:41'),
(5, 3, 10, '2026-05-03 23:45:35'),
(6, 3, 9, '2026-05-03 23:50:57'),
(7, 3, 1, '2026-05-04 00:02:10'),
(8, 2, 1, '2026-05-04 00:02:10'),
(9, 1, 1, '2026-05-04 00:02:10'),
(10, 3, 11, '2026-05-04 00:02:19'),
(11, 2, 11, '2026-05-04 00:02:19'),
(12, 1, 11, '2026-05-04 00:02:19'),
(13, 3, 12, '2026-05-04 00:02:34'),
(14, 2, 12, '2026-05-04 00:02:34'),
(15, 1, 12, '2026-05-04 00:02:34');

-- --------------------------------------------------------

--
-- Table structure for table `buyers`
--

DROP TABLE IF EXISTS `buyers`;
CREATE TABLE IF NOT EXISTS `buyers` (
  `buyer_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `phone_number` varchar(10) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `modified_on` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`buyer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `buyers`
--

INSERT INTO `buyers` (`buyer_id`, `user_id`, `address`, `phone_number`, `is_active`, `created_on`, `created_by`, `modified_on`, `modified_by`) VALUES
(1, 12, 'nashik', '1234567890', 'Y', '2026-05-01 16:01:23', 12, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chat_connections`
--

DROP TABLE IF EXISTS `chat_connections`;
CREATE TABLE IF NOT EXISTS `chat_connections` (
  `connection_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connected_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `disconnected_at` datetime DEFAULT NULL,
  PRIMARY KEY (`connection_id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `idx_user_active` (`user_id`,`disconnected_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `message_id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `message_text` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int NOT NULL,
  PRIMARY KEY (`message_id`),
  KEY `idx_conversation` (`sender_id`,`receiver_id`),
  KEY `idx_created_on` (`created_on`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`message_id`, `sender_id`, `receiver_id`, `message_text`, `is_read`, `created_on`, `created_by`) VALUES
(1, 1, 9, 'hii', 0, '2026-05-04 00:21:49', 1),
(2, 1, 10, 'hii', 0, '2026-05-04 00:22:09', 1),
(3, 10, 1, 'helo', 0, '2026-05-04 00:22:29', 10),
(4, 10, 9, 'hii', 0, '2026-05-04 00:28:06', 10),
(5, 1, 12, 'hii', 0, '2026-05-04 00:30:12', 1),
(6, 10, 9, 'testing message', 0, '2026-05-04 00:32:32', 10),
(7, 9, 10, 'testing replay msg', 0, '2026-05-04 00:33:12', 9);

-- --------------------------------------------------------

--
-- Table structure for table `communication`
--

DROP TABLE IF EXISTS `communication`;
CREATE TABLE IF NOT EXISTS `communication` (
  `comm_id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int DEFAULT NULL,
  `reciver_id` int DEFAULT NULL,
  `message` text COLLATE utf8mb4_0900_ai_ci,
  `is_seen` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'N',
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `is_reported` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'N',
  `created_by` int DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `modified_on` datetime DEFAULT NULL,
  PRIMARY KEY (`comm_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consultancy_ratings`
--

DROP TABLE IF EXISTS `consultancy_ratings`;
CREATE TABLE IF NOT EXISTS `consultancy_ratings` (
  `rating_id` int NOT NULL AUTO_INCREMENT,
  `subscription_id` int NOT NULL,
  `consultant_id` int NOT NULL,
  `farmer_id` int NOT NULL,
  `rating` int DEFAULT NULL,
  `review` text COLLATE utf8mb4_0900_ai_ci,
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`rating_id`),
  KEY `subscription_id` (`subscription_id`),
  KEY `consultant_id` (`consultant_id`),
  KEY `farmer_id` (`farmer_id`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `consultancy_services`
--

DROP TABLE IF EXISTS `consultancy_services`;
CREATE TABLE IF NOT EXISTS `consultancy_services` (
  `service_id` int NOT NULL AUTO_INCREMENT,
  `consultant_id` int NOT NULL,
  `service_name` varchar(255) COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `description` text COLLATE utf8mb4_0900_ai_ci,
  `price_per_month` decimal(10,2) NOT NULL,
  `duration_months` int DEFAULT '1',
  `max_consultations` int DEFAULT '4',
  `consultation_duration_mins` int DEFAULT '30',
  `expertise_areas` text COLLATE utf8mb4_0900_ai_ci,
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `modified_on` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`service_id`),
  KEY `consultant_id` (`consultant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `consultancy_services`
--

INSERT INTO `consultancy_services` (`service_id`, `consultant_id`, `service_name`, `description`, `price_per_month`, `duration_months`, `max_consultations`, `consultation_duration_mins`, `expertise_areas`, `is_active`, `created_on`, `created_by`, `modified_on`, `modified_by`) VALUES
(1, 1, 'Organic Farming Consultation', 'Testing', 100.00, 1, 4, 30, '0', 'Y', '2026-05-02 22:10:48', 11, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `consultancy_sessions`
--

DROP TABLE IF EXISTS `consultancy_sessions`;
CREATE TABLE IF NOT EXISTS `consultancy_sessions` (
  `session_id` int NOT NULL AUTO_INCREMENT,
  `subscription_id` int NOT NULL,
  `consultant_id` int NOT NULL,
  `farmer_id` int NOT NULL,
  `session_date` datetime DEFAULT NULL,
  `duration_mins` int DEFAULT '30',
  `session_status` enum('scheduled','completed','cancelled','no-show') COLLATE utf8mb4_0900_ai_ci DEFAULT 'scheduled',
  `meeting_link` varchar(500) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `meeting_notes` text COLLATE utf8mb4_0900_ai_ci,
  `session_feedback` text COLLATE utf8mb4_0900_ai_ci,
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`session_id`),
  KEY `subscription_id` (`subscription_id`),
  KEY `consultant_id` (`consultant_id`),
  KEY `farmer_id` (`farmer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consultants`
--

DROP TABLE IF EXISTS `consultants`;
CREATE TABLE IF NOT EXISTS `consultants` (
  `consultant_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `specialization` varchar(255) COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `degree` varchar(255) COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `bio` text COLLATE utf8mb4_0900_ai_ci,
  `license_no` varchar(100) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `verification_status` enum('pending','approved','rejected') COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending',
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `modified_on` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `profile_image` varchar(255) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  PRIMARY KEY (`consultant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `consultants`
--

INSERT INTO `consultants` (`consultant_id`, `user_id`, `specialization`, `degree`, `bio`, `license_no`, `verification_status`, `is_active`, `created_on`, `created_by`, `modified_on`, `modified_by`, `profile_image`) VALUES
(1, 11, 'Crop managment', 'B.Sc', 'bio', 'test lic', 'approved', 'Y', '2026-05-01 10:36:48', 11, '2026-05-02 21:17:58', 1, 'assets/dist/img/consultant_profiles/consultant_11_1777612008.png');

-- --------------------------------------------------------

--
-- Table structure for table `consultents_feedback`
--

DROP TABLE IF EXISTS `consultents_feedback`;
CREATE TABLE IF NOT EXISTS `consultents_feedback` (
  `con_feed_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `consultent_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_0900_ai_ci,
  `created_by` int DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  PRIMARY KEY (`con_feed_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consultent_rating`
--

DROP TABLE IF EXISTS `consultent_rating`;
CREATE TABLE IF NOT EXISTS `consultent_rating` (
  `cons_rating_id` int NOT NULL AUTO_INCREMENT,
  `consultent_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `rating` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  PRIMARY KEY (`cons_rating_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crop_yield`
--

DROP TABLE IF EXISTS `crop_yield`;
CREATE TABLE IF NOT EXISTS `crop_yield` (
  `yield_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `crop_type` varchar(100) COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `area_in_acres` decimal(10,2) DEFAULT NULL,
  `expected_yield` decimal(10,2) DEFAULT NULL,
  `actual_yield` decimal(10,2) DEFAULT NULL,
  `yield_unit` varchar(50) COLLATE utf8mb4_0900_ai_ci DEFAULT 'kg',
  `season` varchar(50) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `year` int DEFAULT NULL,
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`yield_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `diseases`
--

DROP TABLE IF EXISTS `diseases`;
CREATE TABLE IF NOT EXISTS `diseases` (
  `diseases_id` int NOT NULL AUTO_INCREMENT,
  `disease_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `one_line_description` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `causes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `symptoms` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `prevention` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `is_active` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_on` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`diseases_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `diseases`
--

INSERT INTO `diseases` (`diseases_id`, `disease_name`, `description`, `one_line_description`, `causes`, `symptoms`, `prevention`, `is_active`, `created_on`, `created_by`, `modified_on`, `modified_by`) VALUES
(1, 'Black Rot', 'Black Rot is a seed-borne bacterial disease of cruciferous crops caused by Xanthomonas campestris pv. campestris, characterized by yellow V-shaped leaf lesions with blackened veins.\r\n\r\n', 'Black Rot is fungal disease of Grapes ', 'Caueses Black Rot is caused by the bacterium Xanthomonas campestris pv. campestris, which spreads mainly through infected seeds, rain splash, insects, and contaminated tools.\r\n\r\n', ' Black Rot symptoms appear as yellow V-shaped lesions from leaf margins with blackened veins, leading to wilting and stunted growth.\r\n\r\n', 'Black Rot disease can be prevented by using disease-free seeds, practicing crop rotation, maintaining sanitation, and avoiding overhead irrigation.\r\n\r\n', 'Y', '2025-09-15 09:43:25', 1, '2025-09-17 14:25:31', 1),
(2, 'powdery mealdew', 'Powdery mildew is a common fungal disease that affects a wide range of plants, including vegetables, fruits, and ornamentals. It is caused by various species of fungi in the order Erysiphales and is characterized by white or grayish powdery spots that typically appear on the upper surfaces of leaves, stems, buds, and sometimes flowers and fruits. These spots consist of fungal mycelium and spores, which can spread rapidly under favorable conditions—usually dry, warm days with high humidity at night. Infected leaves may become distorted, yellow, and eventually drop prematurely, reducing photosynthesis and weakening the plant. While rarely fatal, severe infections can significantly decrease crop yield and quality. Management involves cultural practices like proper spacing, pruning for air circulation, resistant varieties, and, if necessary, fungicidal treatments.\r\n\r\n', 'Powdery mildew is a fungal disease causing white, powdery spots on plant surfaces.', 'Caused by fungal spores thriving in warm, dry days with high nighttime humidity.\r\n\r\n', 'White powdery spots on leaves, stems, buds, yellowing, curling, and premature leaf drop.\r\n\r\n', 'Ensure good air circulation, avoid overcrowding, water at the base, use resistant varieties, and apply fungicides if needed.\r\n\r\n', 'Y', '2025-09-17 14:28:46', 1, NULL, NULL),
(3, 'downy Mealdew', 'Downy mildew is a plant disease caused by oomycete pathogens (Peronospora, Plasmopara, etc.) that thrive in cool, moist conditions, leading to yellow or pale green spots on the upper leaf surface and grayish-white downy fungal growth underneath, ultimately weakening the plant and reducing yield.\r\n\r\n', 'Downy mildew is a fungal-like disease that produces yellow leaf spots with downy growth on the undersides.', 'Downy mildew is caused by oomycete pathogens that thrive in cool, humid conditions and spread through infected debris, water, and wind.\r\n\r\n', ' Symptoms of Downy Mildew:\r\nYellow or pale green angular spots on upper leaf surfaces with grayish-white downy growth underneath, leading to leaf curling, stunting, and yield loss.\r\n\r\n', 'Prevention of Downy Mildew:\r\nUse disease-free seeds, practice crop rotation, ensure good air circulation, avoid overhead irrigation, and apply resistant varieties or preventive fungicides.\r\n\r\n', 'Y', '2025-09-17 14:33:37', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `disease_reports`
--

DROP TABLE IF EXISTS `disease_reports`;
CREATE TABLE IF NOT EXISTS `disease_reports` (
  `report_id` int NOT NULL AUTO_INCREMENT,
  `report_type` enum('farmer','crop') COLLATE utf8mb4_0900_ai_ci DEFAULT 'farmer',
  `user_id` int NOT NULL,
  `disease_id` int NOT NULL,
  `crop_type` varchar(100) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `severity` enum('mild','moderate','severe') COLLATE utf8mb4_0900_ai_ci DEFAULT 'moderate',
  `description` text COLLATE utf8mb4_0900_ai_ci,
  `image_url` varchar(255) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `report_data` json DEFAULT NULL,
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `farmers`
--

DROP TABLE IF EXISTS `farmers`;
CREATE TABLE IF NOT EXISTS `farmers` (
  `farmer_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `farm_name` varchar(255) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `phone_number` varchar(10) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `farm_size` varchar(50) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `crops_grown` text COLLATE utf8mb4_0900_ai_ci,
  `verification_status` enum('pending','approved','rejected') COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending',
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `modified_on` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`farmer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `farmers`
--

INSERT INTO `farmers` (`farmer_id`, `user_id`, `farm_name`, `location`, `phone_number`, `farm_size`, `crops_grown`, `verification_status`, `is_active`, `created_on`, `created_by`, `modified_on`, `modified_by`) VALUES
(1, 9, 'Abc Farmer', 'Nashik', '1234567890', '5 acres', 'wheat', 'approved', 'Y', '2026-05-01 15:55:08', 9, '2026-05-02 21:39:48', 1);

-- --------------------------------------------------------

--
-- Table structure for table `farmer_consultancy_subscriptions`
--

DROP TABLE IF EXISTS `farmer_consultancy_subscriptions`;
CREATE TABLE IF NOT EXISTS `farmer_consultancy_subscriptions` (
  `subscription_id` int NOT NULL AUTO_INCREMENT,
  `farmer_id` int NOT NULL,
  `service_id` int NOT NULL,
  `consultant_id` int NOT NULL,
  `subscription_status` enum('active','expired','cancelled') COLLATE utf8mb4_0900_ai_ci DEFAULT 'active',
  `start_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `end_date` datetime DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `remaining_consultations` int DEFAULT NULL,
  `payment_status` enum('pending','completed','failed') COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending',
  `transaction_id` varchar(100) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `rating` int DEFAULT NULL,
  `review` text COLLATE utf8mb4_0900_ai_ci,
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `modified_on` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`subscription_id`),
  KEY `farmer_id` (`farmer_id`),
  KEY `service_id` (`service_id`),
  KEY `consultant_id` (`consultant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `farmer_consultancy_subscriptions`
--

INSERT INTO `farmer_consultancy_subscriptions` (`subscription_id`, `farmer_id`, `service_id`, `consultant_id`, `subscription_status`, `start_date`, `end_date`, `amount_paid`, `remaining_consultations`, `payment_status`, `transaction_id`, `rating`, `review`, `is_active`, `created_on`, `created_by`, `modified_on`, `modified_by`) VALUES
(1, 1, 1, 1, 'active', '2026-05-02 22:11:23', '0000-00-00 00:00:00', 100.00, 4, 'completed', 'TXN-1777740083-1', NULL, NULL, 'Y', '2026-05-02 22:11:23', 9, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `farmer_products`
--

DROP TABLE IF EXISTS `farmer_products`;
CREATE TABLE IF NOT EXISTS `farmer_products` (
  `farmer_product_id` int NOT NULL AUTO_INCREMENT,
  `farmer_id` int NOT NULL,
  `pro_id` int NOT NULL,
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_on` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`farmer_product_id`),
  UNIQUE KEY `unique_farmer_product` (`farmer_id`,`pro_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `farmer_products`
--

INSERT INTO `farmer_products` (`farmer_product_id`, `farmer_id`, `pro_id`, `created_on`, `modified_on`, `modified_by`) VALUES
(1, 1, 2, '2026-05-02 22:40:53', NULL, NULL),
(2, 1, 3, '2026-05-02 22:55:58', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `farmer_product_approval`
--

DROP TABLE IF EXISTS `farmer_product_approval`;
CREATE TABLE IF NOT EXISTS `farmer_product_approval` (
  `approval_id` int NOT NULL AUTO_INCREMENT,
  `pro_id` int NOT NULL,
  `farmer_id` int NOT NULL,
  `approval_status` enum('pending','approved','rejected') COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending',
  `rejection_reason` text COLLATE utf8mb4_0900_ai_ci,
  `approval_date` datetime DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`approval_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `farmer_product_approval`
--

INSERT INTO `farmer_product_approval` (`approval_id`, `pro_id`, `farmer_id`, `approval_status`, `rejection_reason`, `approval_date`, `approved_by`, `is_active`, `created_on`, `created_by`) VALUES
(1, 2, 1, 'approved', NULL, '2026-05-02 22:44:40', 1, 'Y', '2026-05-02 22:40:53', 9),
(2, 3, 1, 'rejected', 'Chnage Image', '2026-05-02 23:37:55', 1, 'Y', '2026-05-02 22:55:58', 9);

-- --------------------------------------------------------

--
-- Table structure for table `feedback_reports`
--

DROP TABLE IF EXISTS `feedback_reports`;
CREATE TABLE IF NOT EXISTS `feedback_reports` (
  `feedback_report_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `feedback_type` enum('farmer','vendor','consultant') COLLATE utf8mb4_0900_ai_ci DEFAULT 'farmer',
  `target_user_id` int DEFAULT NULL,
  `rating` int DEFAULT NULL,
  `comment` text COLLATE utf8mb4_0900_ai_ci,
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`feedback_report_id`)
) ;

--
-- Dumping data for table `feedback_reports`
--

INSERT INTO `feedback_reports` (`feedback_report_id`, `user_id`, `feedback_type`, `target_user_id`, `rating`, `comment`, `is_active`, `created_on`, `created_by`) VALUES
(1, 9, 'farmer', NULL, 5, '0', 'Y', '2026-05-02 21:54:40', 9);

-- --------------------------------------------------------

--
-- Table structure for table `prediction_details`
--

DROP TABLE IF EXISTS `prediction_details`;
CREATE TABLE IF NOT EXISTS `prediction_details` (
  `pre_det_id` int NOT NULL AUTO_INCREMENT,
  `pre_ms_id` int DEFAULT NULL,
  `predicted_disease` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `confidence_percent` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `is_active` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_on` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`pre_det_id`)
) ENGINE=MyISAM AUTO_INCREMENT=351 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `prediction_details`
--

INSERT INTO `prediction_details` (`pre_det_id`, `pre_ms_id`, `predicted_disease`, `confidence_percent`, `is_active`, `created_on`, `created_by`, `modified_on`, `modified_by`) VALUES
(1, 1, 'Black Rot', '99.38%', 'Y', '2025-09-14 20:53:05', 9, NULL, NULL),
(2, 1, 'ESCA', '0.62%', 'Y', '2025-09-14 20:53:05', 9, NULL, NULL),
(3, 1, 'Leaf Blight', '0.00%', 'Y', '2025-09-14 20:53:05', 9, NULL, NULL),
(4, 1, 'Healthy', '0.00%', 'Y', '2025-09-14 20:53:05', 9, NULL, NULL),
(5, 1, 'Bacterial Rot', '0.00%', 'Y', '2025-09-14 20:53:05', 9, NULL, NULL),
(6, 1, 'Powdery Mildew', '0.00%', 'Y', '2025-09-14 20:53:05', 9, NULL, NULL),
(7, 1, 'Downey Mildew', '0.00%', 'Y', '2025-09-14 20:53:05', 9, NULL, NULL),
(8, 2, 'Downey Mildew', '93.59%', 'Y', '2025-09-14 21:46:25', 9, NULL, NULL),
(9, 2, 'Leaf Blight', '5.86%', 'Y', '2025-09-14 21:46:25', 9, NULL, NULL),
(10, 2, 'Bacterial Rot', '0.44%', 'Y', '2025-09-14 21:46:25', 9, NULL, NULL),
(11, 2, 'Black Rot', '0.08%', 'Y', '2025-09-14 21:46:25', 9, NULL, NULL),
(12, 2, 'Powdery Mildew', '0.03%', 'Y', '2025-09-14 21:46:25', 9, NULL, NULL),
(13, 2, 'Healthy', '0.01%', 'Y', '2025-09-14 21:46:25', 9, NULL, NULL),
(14, 2, 'ESCA', '0.00%', 'Y', '2025-09-14 21:46:25', 9, NULL, NULL),
(15, 3, 'Downey Mildew', '93.59%', 'Y', '2025-09-14 21:52:40', 9, NULL, NULL),
(16, 3, 'Leaf Blight', '5.86%', 'Y', '2025-09-14 21:52:40', 9, NULL, NULL),
(17, 3, 'Bacterial Rot', '0.44%', 'Y', '2025-09-14 21:52:40', 9, NULL, NULL),
(18, 3, 'Black Rot', '0.08%', 'Y', '2025-09-14 21:52:40', 9, NULL, NULL),
(19, 3, 'Powdery Mildew', '0.03%', 'Y', '2025-09-14 21:52:40', 9, NULL, NULL),
(20, 3, 'Healthy', '0.01%', 'Y', '2025-09-14 21:52:40', 9, NULL, NULL),
(21, 3, 'ESCA', '0.00%', 'Y', '2025-09-14 21:52:40', 9, NULL, NULL),
(22, 4, 'Downey Mildew', '66.62%', 'Y', '2025-09-14 21:56:23', 9, NULL, NULL),
(23, 4, 'Powdery Mildew', '23.38%', 'Y', '2025-09-14 21:56:23', 9, NULL, NULL),
(24, 4, 'Healthy', '9.74%', 'Y', '2025-09-14 21:56:23', 9, NULL, NULL),
(25, 4, 'Bacterial Rot', '0.24%', 'Y', '2025-09-14 21:56:23', 9, NULL, NULL),
(26, 4, 'Black Rot', '0.02%', 'Y', '2025-09-14 21:56:23', 9, NULL, NULL),
(27, 4, 'ESCA', '0.00%', 'Y', '2025-09-14 21:56:23', 9, NULL, NULL),
(28, 4, 'Leaf Blight', '0.00%', 'Y', '2025-09-14 21:56:23', 9, NULL, NULL),
(29, 5, 'Healthy', '100.00%', 'Y', '2025-09-14 22:03:07', 9, NULL, NULL),
(30, 5, 'Powdery Mildew', '0.00%', 'Y', '2025-09-14 22:03:07', 9, NULL, NULL),
(31, 5, 'Downey Mildew', '0.00%', 'Y', '2025-09-14 22:03:07', 9, NULL, NULL),
(32, 5, 'Bacterial Rot', '0.00%', 'Y', '2025-09-14 22:03:07', 9, NULL, NULL),
(33, 5, 'Leaf Blight', '0.00%', 'Y', '2025-09-14 22:03:07', 9, NULL, NULL),
(34, 5, 'Black Rot', '0.00%', 'Y', '2025-09-14 22:03:07', 9, NULL, NULL),
(35, 5, 'ESCA', '0.00%', 'Y', '2025-09-14 22:03:07', 9, NULL, NULL),
(36, 6, 'Healthy', '98.41%', 'Y', '2025-09-15 09:13:33', 9, NULL, NULL),
(37, 6, 'Leaf Blight', '0.96%', 'Y', '2025-09-15 09:13:33', 9, NULL, NULL),
(38, 6, 'Black Rot', '0.58%', 'Y', '2025-09-15 09:13:33', 9, NULL, NULL),
(39, 6, 'ESCA', '0.04%', 'Y', '2025-09-15 09:13:33', 9, NULL, NULL),
(40, 6, 'Bacterial Rot', '0.00%', 'Y', '2025-09-15 09:13:33', 9, NULL, NULL),
(41, 6, 'Powdery Mildew', '0.00%', 'Y', '2025-09-15 09:13:33', 9, NULL, NULL),
(42, 6, 'Downey Mildew', '0.00%', 'Y', '2025-09-15 09:13:33', 9, NULL, NULL),
(43, 7, 'Black Rot', '98.85%', 'Y', '2025-09-15 09:14:08', 9, NULL, NULL),
(44, 7, 'Leaf Blight', '0.65%', 'Y', '2025-09-15 09:14:08', 9, NULL, NULL),
(45, 7, 'ESCA', '0.37%', 'Y', '2025-09-15 09:14:08', 9, NULL, NULL),
(46, 7, 'Healthy', '0.14%', 'Y', '2025-09-15 09:14:08', 9, NULL, NULL),
(47, 7, 'Bacterial Rot', '0.00%', 'Y', '2025-09-15 09:14:08', 9, NULL, NULL),
(48, 7, 'Powdery Mildew', '0.00%', 'Y', '2025-09-15 09:14:08', 9, NULL, NULL),
(49, 7, 'Downey Mildew', '0.00%', 'Y', '2025-09-15 09:14:08', 9, NULL, NULL),
(50, 8, 'Black Rot', '99.48%', 'Y', '2025-09-15 09:45:10', 9, NULL, NULL),
(51, 8, 'ESCA', '0.32%', 'Y', '2025-09-15 09:45:10', 9, NULL, NULL),
(52, 8, 'Leaf Blight', '0.11%', 'Y', '2025-09-15 09:45:10', 9, NULL, NULL),
(53, 8, 'Healthy', '0.09%', 'Y', '2025-09-15 09:45:10', 9, NULL, NULL),
(54, 8, 'Powdery Mildew', '0.00%', 'Y', '2025-09-15 09:45:10', 9, NULL, NULL),
(55, 8, 'Bacterial Rot', '0.00%', 'Y', '2025-09-15 09:45:10', 9, NULL, NULL),
(56, 8, 'Downey Mildew', '0.00%', 'Y', '2025-09-15 09:45:10', 9, NULL, NULL),
(57, 9, 'Black Rot', '99.48%', 'Y', '2025-09-15 10:03:07', 9, NULL, NULL),
(58, 9, 'ESCA', '0.32%', 'Y', '2025-09-15 10:03:07', 9, NULL, NULL),
(59, 9, 'Leaf Blight', '0.11%', 'Y', '2025-09-15 10:03:07', 9, NULL, NULL),
(60, 9, 'Healthy', '0.09%', 'Y', '2025-09-15 10:03:07', 9, NULL, NULL),
(61, 9, 'Powdery Mildew', '0.00%', 'Y', '2025-09-15 10:03:07', 9, NULL, NULL),
(62, 9, 'Bacterial Rot', '0.00%', 'Y', '2025-09-15 10:03:07', 9, NULL, NULL),
(63, 9, 'Downey Mildew', '0.00%', 'Y', '2025-09-15 10:03:07', 9, NULL, NULL),
(64, 10, 'Black Rot', '99.38%', 'Y', '2025-09-15 13:45:31', 9, NULL, NULL),
(65, 10, 'ESCA', '0.62%', 'Y', '2025-09-15 13:45:31', 9, NULL, NULL),
(66, 10, 'Leaf Blight', '0.00%', 'Y', '2025-09-15 13:45:31', 9, NULL, NULL),
(67, 10, 'Healthy', '0.00%', 'Y', '2025-09-15 13:45:31', 9, NULL, NULL),
(68, 10, 'Bacterial Rot', '0.00%', 'Y', '2025-09-15 13:45:31', 9, NULL, NULL),
(69, 10, 'Powdery Mildew', '0.00%', 'Y', '2025-09-15 13:45:31', 9, NULL, NULL),
(70, 10, 'Downey Mildew', '0.00%', 'Y', '2025-09-15 13:45:31', 9, NULL, NULL),
(71, 11, 'Black Rot', '99.38%', 'Y', '2025-09-15 14:10:20', 9, NULL, NULL),
(72, 11, 'ESCA', '0.62%', 'Y', '2025-09-15 14:10:20', 9, NULL, NULL),
(73, 11, 'Leaf Blight', '0.00%', 'Y', '2025-09-15 14:10:20', 9, NULL, NULL),
(74, 11, 'Healthy', '0.00%', 'Y', '2025-09-15 14:10:20', 9, NULL, NULL),
(75, 11, 'Bacterial Rot', '0.00%', 'Y', '2025-09-15 14:10:20', 9, NULL, NULL),
(76, 11, 'Powdery Mildew', '0.00%', 'Y', '2025-09-15 14:10:20', 9, NULL, NULL),
(77, 11, 'Downey Mildew', '0.00%', 'Y', '2025-09-15 14:10:20', 9, NULL, NULL),
(78, 12, 'Black Rot', '99.38%', 'Y', '2025-09-15 14:14:36', 9, NULL, NULL),
(79, 12, 'ESCA', '0.62%', 'Y', '2025-09-15 14:14:36', 9, NULL, NULL),
(80, 12, 'Leaf Blight', '0.00%', 'Y', '2025-09-15 14:14:36', 9, NULL, NULL),
(81, 12, 'Healthy', '0.00%', 'Y', '2025-09-15 14:14:36', 9, NULL, NULL),
(82, 12, 'Bacterial Rot', '0.00%', 'Y', '2025-09-15 14:14:36', 9, NULL, NULL),
(83, 12, 'Powdery Mildew', '0.00%', 'Y', '2025-09-15 14:14:36', 9, NULL, NULL),
(84, 12, 'Downey Mildew', '0.00%', 'Y', '2025-09-15 14:14:36', 9, NULL, NULL),
(85, 13, 'Downey Mildew', '72.12%', 'Y', '2025-09-15 14:23:29', 9, NULL, NULL),
(86, 13, 'Healthy', '23.04%', 'Y', '2025-09-15 14:23:29', 9, NULL, NULL),
(87, 13, 'Powdery Mildew', '3.79%', 'Y', '2025-09-15 14:23:29', 9, NULL, NULL),
(88, 13, 'Black Rot', '0.60%', 'Y', '2025-09-15 14:23:29', 9, NULL, NULL),
(89, 13, 'Bacterial Rot', '0.39%', 'Y', '2025-09-15 14:23:29', 9, NULL, NULL),
(90, 13, 'Leaf Blight', '0.05%', 'Y', '2025-09-15 14:23:29', 9, NULL, NULL),
(91, 13, 'ESCA', '0.02%', 'Y', '2025-09-15 14:23:29', 9, NULL, NULL),
(92, 14, 'Black Rot', '99.38%', 'Y', '2025-09-15 14:23:54', 9, NULL, NULL),
(93, 14, 'ESCA', '0.62%', 'Y', '2025-09-15 14:23:54', 9, NULL, NULL),
(94, 14, 'Leaf Blight', '0.00%', 'Y', '2025-09-15 14:23:54', 9, NULL, NULL),
(95, 14, 'Healthy', '0.00%', 'Y', '2025-09-15 14:23:54', 9, NULL, NULL),
(96, 14, 'Bacterial Rot', '0.00%', 'Y', '2025-09-15 14:23:54', 9, NULL, NULL),
(97, 14, 'Powdery Mildew', '0.00%', 'Y', '2025-09-15 14:23:54', 9, NULL, NULL),
(98, 14, 'Downey Mildew', '0.00%', 'Y', '2025-09-15 14:23:54', 9, NULL, NULL),
(99, 15, 'Black Rot', '64.85%', 'Y', '2025-09-15 14:24:07', 9, NULL, NULL),
(100, 15, 'Leaf Blight', '26.44%', 'Y', '2025-09-15 14:24:07', 9, NULL, NULL),
(101, 15, 'ESCA', '5.55%', 'Y', '2025-09-15 14:24:07', 9, NULL, NULL),
(102, 15, 'Bacterial Rot', '3.16%', 'Y', '2025-09-15 14:24:07', 9, NULL, NULL),
(103, 15, 'Downey Mildew', '0.00%', 'Y', '2025-09-15 14:24:07', 9, NULL, NULL),
(104, 15, 'Healthy', '0.00%', 'Y', '2025-09-15 14:24:07', 9, NULL, NULL),
(105, 15, 'Powdery Mildew', '0.00%', 'Y', '2025-09-15 14:24:07', 9, NULL, NULL),
(106, 16, 'Bacterial Rot', '97.23%', 'Y', '2025-09-15 14:24:46', 9, NULL, NULL),
(107, 16, 'Black Rot', '1.37%', 'Y', '2025-09-15 14:24:46', 9, NULL, NULL),
(108, 16, 'Healthy', '0.87%', 'Y', '2025-09-15 14:24:46', 9, NULL, NULL),
(109, 16, 'Powdery Mildew', '0.32%', 'Y', '2025-09-15 14:24:46', 9, NULL, NULL),
(110, 16, 'Downey Mildew', '0.14%', 'Y', '2025-09-15 14:24:46', 9, NULL, NULL),
(111, 16, 'ESCA', '0.06%', 'Y', '2025-09-15 14:24:46', 9, NULL, NULL),
(112, 16, 'Leaf Blight', '0.00%', 'Y', '2025-09-15 14:24:46', 9, NULL, NULL),
(113, 17, 'Bacterial Rot', '49.01%', 'Y', '2025-09-15 14:26:26', 9, NULL, NULL),
(114, 17, 'ESCA', '42.00%', 'Y', '2025-09-15 14:26:26', 9, NULL, NULL),
(115, 17, 'Black Rot', '6.60%', 'Y', '2025-09-15 14:26:26', 9, NULL, NULL),
(116, 17, 'Healthy', '1.18%', 'Y', '2025-09-15 14:26:26', 9, NULL, NULL),
(117, 17, 'Powdery Mildew', '0.72%', 'Y', '2025-09-15 14:26:26', 9, NULL, NULL),
(118, 17, 'Downey Mildew', '0.45%', 'Y', '2025-09-15 14:26:26', 9, NULL, NULL),
(119, 17, 'Leaf Blight', '0.04%', 'Y', '2025-09-15 14:26:26', 9, NULL, NULL),
(120, 18, 'Leaf Blight', '76.49%', 'Y', '2025-09-15 14:29:38', 9, NULL, NULL),
(121, 18, 'ESCA', '23.51%', 'Y', '2025-09-15 14:29:38', 9, NULL, NULL),
(122, 18, 'Bacterial Rot', '0.00%', 'Y', '2025-09-15 14:29:38', 9, NULL, NULL),
(123, 18, 'Black Rot', '0.00%', 'Y', '2025-09-15 14:29:38', 9, NULL, NULL),
(124, 18, 'Healthy', '0.00%', 'Y', '2025-09-15 14:29:38', 9, NULL, NULL),
(125, 18, 'Downey Mildew', '0.00%', 'Y', '2025-09-15 14:29:38', 9, NULL, NULL),
(126, 18, 'Powdery Mildew', '0.00%', 'Y', '2025-09-15 14:29:38', 9, NULL, NULL),
(127, 19, 'Black Rot', '99.38%', 'Y', '2025-09-15 23:04:16', 9, NULL, NULL),
(128, 19, 'ESCA', '0.62%', 'Y', '2025-09-15 23:04:16', 9, NULL, NULL),
(129, 19, 'Leaf Blight', '0.00%', 'Y', '2025-09-15 23:04:16', 9, NULL, NULL),
(130, 19, 'Healthy', '0.00%', 'Y', '2025-09-15 23:04:16', 9, NULL, NULL),
(131, 19, 'Bacterial Rot', '0.00%', 'Y', '2025-09-15 23:04:16', 9, NULL, NULL),
(132, 19, 'Powdery Mildew', '0.00%', 'Y', '2025-09-15 23:04:16', 9, NULL, NULL),
(133, 19, 'Downey Mildew', '0.00%', 'Y', '2025-09-15 23:04:16', 9, NULL, NULL),
(134, 20, 'Black Rot', '99.38%', 'Y', '2025-09-16 08:57:23', 9, NULL, NULL),
(135, 20, 'ESCA', '0.62%', 'Y', '2025-09-16 08:57:23', 9, NULL, NULL),
(136, 20, 'Leaf Blight', '0.00%', 'Y', '2025-09-16 08:57:23', 9, NULL, NULL),
(137, 20, 'Healthy', '0.00%', 'Y', '2025-09-16 08:57:23', 9, NULL, NULL),
(138, 20, 'Bacterial Rot', '0.00%', 'Y', '2025-09-16 08:57:23', 9, NULL, NULL),
(139, 20, 'Powdery Mildew', '0.00%', 'Y', '2025-09-16 08:57:23', 9, NULL, NULL),
(140, 20, 'Downey Mildew', '0.00%', 'Y', '2025-09-16 08:57:23', 9, NULL, NULL),
(141, 21, 'Black Rot', '99.38%', 'Y', '2025-09-17 12:16:00', 9, NULL, NULL),
(142, 21, 'ESCA', '0.62%', 'Y', '2025-09-17 12:16:00', 9, NULL, NULL),
(143, 21, 'Leaf Blight', '0.00%', 'Y', '2025-09-17 12:16:00', 9, NULL, NULL),
(144, 21, 'Healthy', '0.00%', 'Y', '2025-09-17 12:16:00', 9, NULL, NULL),
(145, 21, 'Bacterial Rot', '0.00%', 'Y', '2025-09-17 12:16:00', 9, NULL, NULL),
(146, 21, 'Powdery Mildew', '0.00%', 'Y', '2025-09-17 12:16:00', 9, NULL, NULL),
(147, 21, 'Downey Mildew', '0.00%', 'Y', '2025-09-17 12:16:00', 9, NULL, NULL),
(148, 22, 'Black Rot', '99.38%', 'Y', '2025-09-17 13:10:45', 9, NULL, NULL),
(149, 22, 'ESCA', '0.62%', 'Y', '2025-09-17 13:10:45', 9, NULL, NULL),
(150, 22, 'Leaf Blight', '0.00%', 'Y', '2025-09-17 13:10:45', 9, NULL, NULL),
(151, 22, 'Healthy', '0.00%', 'Y', '2025-09-17 13:10:45', 9, NULL, NULL),
(152, 22, 'Bacterial Rot', '0.00%', 'Y', '2025-09-17 13:10:45', 9, NULL, NULL),
(153, 22, 'Powdery Mildew', '0.00%', 'Y', '2025-09-17 13:10:45', 9, NULL, NULL),
(154, 22, 'Downey Mildew', '0.00%', 'Y', '2025-09-17 13:10:45', 9, NULL, NULL),
(155, 23, 'Black Rot', '99.38%', 'Y', '2025-09-17 13:35:02', 9, NULL, NULL),
(156, 23, 'ESCA', '0.62%', 'Y', '2025-09-17 13:35:02', 9, NULL, NULL),
(157, 23, 'Leaf Blight', '0.00%', 'Y', '2025-09-17 13:35:02', 9, NULL, NULL),
(158, 23, 'Healthy', '0.00%', 'Y', '2025-09-17 13:35:02', 9, NULL, NULL),
(159, 23, 'Bacterial Rot', '0.00%', 'Y', '2025-09-17 13:35:02', 9, NULL, NULL),
(160, 23, 'Powdery Mildew', '0.00%', 'Y', '2025-09-17 13:35:02', 9, NULL, NULL),
(161, 23, 'Downey Mildew', '0.00%', 'Y', '2025-09-17 13:35:02', 9, NULL, NULL),
(162, 24, 'Black Rot', '99.86%', 'Y', '2025-09-17 13:54:37', 9, NULL, NULL),
(163, 24, 'Downey Mildew', '0.11%', 'Y', '2025-09-17 13:54:37', 9, NULL, NULL),
(164, 24, 'Leaf Blight', '0.03%', 'Y', '2025-09-17 13:54:37', 9, NULL, NULL),
(165, 24, 'Bacterial Rot', '0.00%', 'Y', '2025-09-17 13:54:37', 9, NULL, NULL),
(166, 24, 'Powdery Mildew', '0.00%', 'Y', '2025-09-17 13:54:37', 9, NULL, NULL),
(167, 24, 'ESCA', '0.00%', 'Y', '2025-09-17 13:54:37', 9, NULL, NULL),
(168, 24, 'Healthy', '0.00%', 'Y', '2025-09-17 13:54:37', 9, NULL, NULL),
(169, 25, 'Black Rot', '90.47%', 'Y', '2025-09-17 13:55:14', 9, NULL, NULL),
(170, 25, 'Healthy', '7.19%', 'Y', '2025-09-17 13:55:14', 9, NULL, NULL),
(171, 25, 'Powdery Mildew', '1.04%', 'Y', '2025-09-17 13:55:14', 9, NULL, NULL),
(172, 25, 'Leaf Blight', '0.99%', 'Y', '2025-09-17 13:55:14', 9, NULL, NULL),
(173, 25, 'Bacterial Rot', '0.26%', 'Y', '2025-09-17 13:55:14', 9, NULL, NULL),
(174, 25, 'Downey Mildew', '0.04%', 'Y', '2025-09-17 13:55:14', 9, NULL, NULL),
(175, 25, 'ESCA', '0.01%', 'Y', '2025-09-17 13:55:14', 9, NULL, NULL),
(176, 26, 'ESCA', '99.10%', 'Y', '2025-09-17 13:59:49', 9, NULL, NULL),
(177, 26, 'Bacterial Rot', '0.90%', 'Y', '2025-09-17 13:59:49', 9, NULL, NULL),
(178, 26, 'Healthy', '0.00%', 'Y', '2025-09-17 13:59:49', 9, NULL, NULL),
(179, 26, 'Leaf Blight', '0.00%', 'Y', '2025-09-17 13:59:49', 9, NULL, NULL),
(180, 26, 'Black Rot', '0.00%', 'Y', '2025-09-17 13:59:49', 9, NULL, NULL),
(181, 26, 'Downey Mildew', '0.00%', 'Y', '2025-09-17 13:59:49', 9, NULL, NULL),
(182, 26, 'Powdery Mildew', '0.00%', 'Y', '2025-09-17 13:59:49', 9, NULL, NULL),
(183, 27, 'Leaf Blight', '94.95%', 'Y', '2025-09-17 14:07:10', 9, NULL, NULL),
(184, 27, 'Black Rot', '3.64%', 'Y', '2025-09-17 14:07:10', 9, NULL, NULL),
(185, 27, 'ESCA', '1.02%', 'Y', '2025-09-17 14:07:10', 9, NULL, NULL),
(186, 27, 'Healthy', '0.35%', 'Y', '2025-09-17 14:07:10', 9, NULL, NULL),
(187, 27, 'Bacterial Rot', '0.04%', 'Y', '2025-09-17 14:07:10', 9, NULL, NULL),
(188, 27, 'Powdery Mildew', '0.00%', 'Y', '2025-09-17 14:07:10', 9, NULL, NULL),
(189, 27, 'Downey Mildew', '0.00%', 'Y', '2025-09-17 14:07:10', 9, NULL, NULL),
(190, 28, 'Leaf Blight', '99.71%', 'Y', '2025-09-17 14:07:21', 9, NULL, NULL),
(191, 28, 'Downey Mildew', '0.28%', 'Y', '2025-09-17 14:07:21', 9, NULL, NULL),
(192, 28, 'Bacterial Rot', '0.01%', 'Y', '2025-09-17 14:07:21', 9, NULL, NULL),
(193, 28, 'Black Rot', '0.00%', 'Y', '2025-09-17 14:07:21', 9, NULL, NULL),
(194, 28, 'Powdery Mildew', '0.00%', 'Y', '2025-09-17 14:07:21', 9, NULL, NULL),
(195, 28, 'ESCA', '0.00%', 'Y', '2025-09-17 14:07:21', 9, NULL, NULL),
(196, 28, 'Healthy', '0.00%', 'Y', '2025-09-17 14:07:21', 9, NULL, NULL),
(197, 29, 'Black Rot', '90.47%', 'Y', '2025-09-17 14:11:01', 9, NULL, NULL),
(198, 29, 'Healthy', '7.19%', 'Y', '2025-09-17 14:11:01', 9, NULL, NULL),
(199, 29, 'Powdery Mildew', '1.04%', 'Y', '2025-09-17 14:11:01', 9, NULL, NULL),
(200, 29, 'Leaf Blight', '0.99%', 'Y', '2025-09-17 14:11:01', 9, NULL, NULL),
(201, 29, 'Bacterial Rot', '0.26%', 'Y', '2025-09-17 14:11:01', 9, NULL, NULL),
(202, 29, 'Downey Mildew', '0.04%', 'Y', '2025-09-17 14:11:01', 9, NULL, NULL),
(203, 29, 'ESCA', '0.01%', 'Y', '2025-09-17 14:11:01', 9, NULL, NULL),
(204, 30, 'Downey Mildew', '97.49%', 'Y', '2025-09-17 14:13:28', 9, NULL, NULL),
(205, 30, 'Leaf Blight', '2.49%', 'Y', '2025-09-17 14:13:28', 9, NULL, NULL),
(206, 30, 'Black Rot', '0.02%', 'Y', '2025-09-17 14:13:28', 9, NULL, NULL),
(207, 30, 'Bacterial Rot', '0.00%', 'Y', '2025-09-17 14:13:28', 9, NULL, NULL),
(208, 30, 'Powdery Mildew', '0.00%', 'Y', '2025-09-17 14:13:28', 9, NULL, NULL),
(209, 30, 'Healthy', '0.00%', 'Y', '2025-09-17 14:13:28', 9, NULL, NULL),
(210, 30, 'ESCA', '0.00%', 'Y', '2025-09-17 14:13:28', 9, NULL, NULL),
(211, 31, 'Downey Mildew', '97.49%', 'Y', '2025-09-17 14:16:35', 9, NULL, NULL),
(212, 31, 'Leaf Blight', '2.49%', 'Y', '2025-09-17 14:16:35', 9, NULL, NULL),
(213, 31, 'Black Rot', '0.02%', 'Y', '2025-09-17 14:16:35', 9, NULL, NULL),
(214, 31, 'Bacterial Rot', '0.00%', 'Y', '2025-09-17 14:16:35', 9, NULL, NULL),
(215, 31, 'Powdery Mildew', '0.00%', 'Y', '2025-09-17 14:16:35', 9, NULL, NULL),
(216, 31, 'Healthy', '0.00%', 'Y', '2025-09-17 14:16:35', 9, NULL, NULL),
(217, 31, 'ESCA', '0.00%', 'Y', '2025-09-17 14:16:35', 9, NULL, NULL),
(218, 32, 'Leaf Blight', '99.71%', 'Y', '2025-09-17 14:28:27', 9, NULL, NULL),
(219, 32, 'Downey Mildew', '0.28%', 'Y', '2025-09-17 14:28:27', 9, NULL, NULL),
(220, 32, 'Bacterial Rot', '0.01%', 'Y', '2025-09-17 14:28:27', 9, NULL, NULL),
(221, 32, 'Black Rot', '0.00%', 'Y', '2025-09-17 14:28:27', 9, NULL, NULL),
(222, 32, 'Powdery Mildew', '0.00%', 'Y', '2025-09-17 14:28:27', 9, NULL, NULL),
(223, 32, 'ESCA', '0.00%', 'Y', '2025-09-17 14:28:27', 9, NULL, NULL),
(224, 32, 'Healthy', '0.00%', 'Y', '2025-09-17 14:28:27', 9, NULL, NULL),
(225, 33, 'Black Rot', '99.38%', 'Y', '2025-09-17 14:35:25', 9, NULL, NULL),
(226, 33, 'ESCA', '0.62%', 'Y', '2025-09-17 14:35:25', 9, NULL, NULL),
(227, 33, 'Leaf Blight', '0.00%', 'Y', '2025-09-17 14:35:25', 9, NULL, NULL),
(228, 33, 'Healthy', '0.00%', 'Y', '2025-09-17 14:35:25', 9, NULL, NULL),
(229, 33, 'Bacterial Rot', '0.00%', 'Y', '2025-09-17 14:35:25', 9, NULL, NULL),
(230, 33, 'Powdery Mildew', '0.00%', 'Y', '2025-09-17 14:35:25', 9, NULL, NULL),
(231, 33, 'Downey Mildew', '0.00%', 'Y', '2025-09-17 14:35:25', 9, NULL, NULL),
(232, 34, 'Downey Mildew', '72.12%', 'Y', '2025-09-17 14:36:01', 9, NULL, NULL),
(233, 34, 'Healthy', '23.04%', 'Y', '2025-09-17 14:36:01', 9, NULL, NULL),
(234, 34, 'Powdery Mildew', '3.79%', 'Y', '2025-09-17 14:36:01', 9, NULL, NULL),
(235, 34, 'Black Rot', '0.60%', 'Y', '2025-09-17 14:36:01', 9, NULL, NULL),
(236, 34, 'Bacterial Rot', '0.39%', 'Y', '2025-09-17 14:36:01', 9, NULL, NULL),
(237, 34, 'Leaf Blight', '0.05%', 'Y', '2025-09-17 14:36:01', 9, NULL, NULL),
(238, 34, 'ESCA', '0.02%', 'Y', '2025-09-17 14:36:01', 9, NULL, NULL),
(239, 35, 'Downey Mildew', '72.12%', 'Y', '2025-09-17 14:36:28', 9, NULL, NULL),
(240, 35, 'Healthy', '23.04%', 'Y', '2025-09-17 14:36:28', 9, NULL, NULL),
(241, 35, 'Powdery Mildew', '3.79%', 'Y', '2025-09-17 14:36:28', 9, NULL, NULL),
(242, 35, 'Black Rot', '0.60%', 'Y', '2025-09-17 14:36:28', 9, NULL, NULL),
(243, 35, 'Bacterial Rot', '0.39%', 'Y', '2025-09-17 14:36:28', 9, NULL, NULL),
(244, 35, 'Leaf Blight', '0.05%', 'Y', '2025-09-17 14:36:28', 9, NULL, NULL),
(245, 35, 'ESCA', '0.02%', 'Y', '2025-09-17 14:36:28', 9, NULL, NULL),
(246, 36, 'Black Rot', '99.38%', 'Y', '2025-09-19 10:03:39', 9, NULL, NULL),
(247, 36, 'ESCA', '0.62%', 'Y', '2025-09-19 10:03:39', 9, NULL, NULL),
(248, 36, 'Leaf Blight', '0.00%', 'Y', '2025-09-19 10:03:39', 9, NULL, NULL),
(249, 36, 'Healthy', '0.00%', 'Y', '2025-09-19 10:03:39', 9, NULL, NULL),
(250, 36, 'Bacterial Rot', '0.00%', 'Y', '2025-09-19 10:03:39', 9, NULL, NULL),
(251, 36, 'Powdery Mildew', '0.00%', 'Y', '2025-09-19 10:03:39', 9, NULL, NULL),
(252, 36, 'Downey Mildew', '0.00%', 'Y', '2025-09-19 10:03:39', 9, NULL, NULL),
(253, 37, 'Black Rot', '99.38%', 'Y', '2025-09-26 20:44:17', 9, NULL, NULL),
(254, 37, 'ESCA', '0.62%', 'Y', '2025-09-26 20:44:17', 9, NULL, NULL),
(255, 37, 'Leaf Blight', '0.00%', 'Y', '2025-09-26 20:44:17', 9, NULL, NULL),
(256, 37, 'Healthy', '0.00%', 'Y', '2025-09-26 20:44:17', 9, NULL, NULL),
(257, 37, 'Bacterial Rot', '0.00%', 'Y', '2025-09-26 20:44:17', 9, NULL, NULL),
(258, 37, 'Powdery Mildew', '0.00%', 'Y', '2025-09-26 20:44:17', 9, NULL, NULL),
(259, 37, 'Downey Mildew', '0.00%', 'Y', '2025-09-26 20:44:17', 9, NULL, NULL),
(260, 38, 'Black Rot', '99.13%', 'Y', '2025-10-17 09:18:38', 9, NULL, NULL),
(261, 38, 'ESCA', '0.87%', 'Y', '2025-10-17 09:18:38', 9, NULL, NULL),
(262, 38, 'Leaf Blight', '0.00%', 'Y', '2025-10-17 09:18:38', 9, NULL, NULL),
(263, 38, 'Healthy', '0.00%', 'Y', '2025-10-17 09:18:38', 9, NULL, NULL),
(264, 38, 'Bacterial Rot', '0.00%', 'Y', '2025-10-17 09:18:38', 9, NULL, NULL),
(265, 38, 'Powdery Mildew', '0.00%', 'Y', '2025-10-17 09:18:38', 9, NULL, NULL),
(266, 38, 'Downey Mildew', '0.00%', 'Y', '2025-10-17 09:18:38', 9, NULL, NULL),
(267, 39, 'Black Rot', '99.38%', 'Y', '2025-10-26 21:10:03', 9, NULL, NULL),
(268, 39, 'ESCA', '0.62%', 'Y', '2025-10-26 21:10:03', 9, NULL, NULL),
(269, 39, 'Leaf Blight', '0.00%', 'Y', '2025-10-26 21:10:03', 9, NULL, NULL),
(270, 39, 'Healthy', '0.00%', 'Y', '2025-10-26 21:10:03', 9, NULL, NULL),
(271, 39, 'Bacterial Rot', '0.00%', 'Y', '2025-10-26 21:10:03', 9, NULL, NULL),
(272, 39, 'Powdery Mildew', '0.00%', 'Y', '2025-10-26 21:10:03', 9, NULL, NULL),
(273, 39, 'Downey Mildew', '0.00%', 'Y', '2025-10-26 21:10:03', 9, NULL, NULL),
(274, 40, 'Downey Mildew', '99.46%', 'Y', '2025-10-30 11:49:57', 9, NULL, NULL),
(275, 40, 'Bacterial Rot', '0.28%', 'Y', '2025-10-30 11:49:57', 9, NULL, NULL),
(276, 40, 'Leaf Blight', '0.18%', 'Y', '2025-10-30 11:49:57', 9, NULL, NULL),
(277, 40, 'Healthy', '0.07%', 'Y', '2025-10-30 11:49:57', 9, NULL, NULL),
(278, 40, 'Powdery Mildew', '0.01%', 'Y', '2025-10-30 11:49:57', 9, NULL, NULL),
(279, 40, 'Black Rot', '0.00%', 'Y', '2025-10-30 11:49:57', 9, NULL, NULL),
(280, 40, 'ESCA', '0.00%', 'Y', '2025-10-30 11:49:57', 9, NULL, NULL),
(281, 41, 'Black Rot', '99.38%', 'Y', '2025-10-30 11:50:22', 9, NULL, NULL),
(282, 41, 'ESCA', '0.62%', 'Y', '2025-10-30 11:50:22', 9, NULL, NULL),
(283, 41, 'Leaf Blight', '0.00%', 'Y', '2025-10-30 11:50:22', 9, NULL, NULL),
(284, 41, 'Healthy', '0.00%', 'Y', '2025-10-30 11:50:22', 9, NULL, NULL),
(285, 41, 'Bacterial Rot', '0.00%', 'Y', '2025-10-30 11:50:22', 9, NULL, NULL),
(286, 41, 'Powdery Mildew', '0.00%', 'Y', '2025-10-30 11:50:22', 9, NULL, NULL),
(287, 41, 'Downey Mildew', '0.00%', 'Y', '2025-10-30 11:50:22', 9, NULL, NULL),
(288, 42, 'Black Rot', '99.38%', 'Y', '2025-10-30 12:16:11', 9, NULL, NULL),
(289, 42, 'ESCA', '0.62%', 'Y', '2025-10-30 12:16:11', 9, NULL, NULL),
(290, 42, 'Leaf Blight', '0.00%', 'Y', '2025-10-30 12:16:11', 9, NULL, NULL),
(291, 42, 'Healthy', '0.00%', 'Y', '2025-10-30 12:16:11', 9, NULL, NULL),
(292, 42, 'Bacterial Rot', '0.00%', 'Y', '2025-10-30 12:16:11', 9, NULL, NULL),
(293, 42, 'Powdery Mildew', '0.00%', 'Y', '2025-10-30 12:16:11', 9, NULL, NULL),
(294, 42, 'Downey Mildew', '0.00%', 'Y', '2025-10-30 12:16:11', 9, NULL, NULL),
(295, 43, 'Black Rot', '98.85%', 'Y', '2025-10-30 12:25:44', 9, NULL, NULL),
(296, 43, 'Leaf Blight', '0.65%', 'Y', '2025-10-30 12:25:44', 9, NULL, NULL),
(297, 43, 'ESCA', '0.37%', 'Y', '2025-10-30 12:25:44', 9, NULL, NULL),
(298, 43, 'Healthy', '0.14%', 'Y', '2025-10-30 12:25:44', 9, NULL, NULL),
(299, 43, 'Bacterial Rot', '0.00%', 'Y', '2025-10-30 12:25:44', 9, NULL, NULL),
(300, 43, 'Powdery Mildew', '0.00%', 'Y', '2025-10-30 12:25:44', 9, NULL, NULL),
(301, 43, 'Downey Mildew', '0.00%', 'Y', '2025-10-30 12:25:44', 9, NULL, NULL),
(302, 44, 'Black Rot', '99.13%', 'Y', '2025-10-30 12:36:25', 9, NULL, NULL),
(303, 44, 'ESCA', '0.87%', 'Y', '2025-10-30 12:36:25', 9, NULL, NULL),
(304, 44, 'Leaf Blight', '0.00%', 'Y', '2025-10-30 12:36:25', 9, NULL, NULL),
(305, 44, 'Healthy', '0.00%', 'Y', '2025-10-30 12:36:25', 9, NULL, NULL),
(306, 44, 'Bacterial Rot', '0.00%', 'Y', '2025-10-30 12:36:25', 9, NULL, NULL),
(307, 44, 'Powdery Mildew', '0.00%', 'Y', '2025-10-30 12:36:25', 9, NULL, NULL),
(308, 44, 'Downey Mildew', '0.00%', 'Y', '2025-10-30 12:36:25', 9, NULL, NULL),
(309, 45, 'Black Rot', '99.38%', 'Y', '2025-10-30 12:38:15', 9, NULL, NULL),
(310, 45, 'ESCA', '0.62%', 'Y', '2025-10-30 12:38:15', 9, NULL, NULL),
(311, 45, 'Leaf Blight', '0.00%', 'Y', '2025-10-30 12:38:15', 9, NULL, NULL),
(312, 45, 'Healthy', '0.00%', 'Y', '2025-10-30 12:38:15', 9, NULL, NULL),
(313, 45, 'Bacterial Rot', '0.00%', 'Y', '2025-10-30 12:38:15', 9, NULL, NULL),
(314, 45, 'Powdery Mildew', '0.00%', 'Y', '2025-10-30 12:38:15', 9, NULL, NULL),
(315, 45, 'Downey Mildew', '0.00%', 'Y', '2025-10-30 12:38:15', 9, NULL, NULL),
(316, 46, 'Black Rot', '99.38%', 'Y', '2025-11-04 11:44:48', 9, NULL, NULL),
(317, 46, 'ESCA', '0.62%', 'Y', '2025-11-04 11:44:48', 9, NULL, NULL),
(318, 46, 'Leaf Blight', '0.00%', 'Y', '2025-11-04 11:44:48', 9, NULL, NULL),
(319, 46, 'Healthy', '0.00%', 'Y', '2025-11-04 11:44:48', 9, NULL, NULL),
(320, 46, 'Bacterial Rot', '0.00%', 'Y', '2025-11-04 11:44:48', 9, NULL, NULL),
(321, 46, 'Powdery Mildew', '0.00%', 'Y', '2025-11-04 11:44:48', 9, NULL, NULL),
(322, 46, 'Downey Mildew', '0.00%', 'Y', '2025-11-04 11:44:48', 9, NULL, NULL),
(323, 47, 'Black Rot', '99.38%', 'Y', '2025-11-04 11:45:19', 9, NULL, NULL),
(324, 47, 'ESCA', '0.62%', 'Y', '2025-11-04 11:45:19', 9, NULL, NULL),
(325, 47, 'Leaf Blight', '0.00%', 'Y', '2025-11-04 11:45:19', 9, NULL, NULL),
(326, 47, 'Healthy', '0.00%', 'Y', '2025-11-04 11:45:19', 9, NULL, NULL),
(327, 47, 'Bacterial Rot', '0.00%', 'Y', '2025-11-04 11:45:19', 9, NULL, NULL),
(328, 47, 'Powdery Mildew', '0.00%', 'Y', '2025-11-04 11:45:19', 9, NULL, NULL),
(329, 47, 'Downey Mildew', '0.00%', 'Y', '2025-11-04 11:45:19', 9, NULL, NULL),
(330, 48, 'Black Rot', '99.38%', 'Y', '2025-11-04 12:06:47', 9, NULL, NULL),
(331, 48, 'ESCA', '0.62%', 'Y', '2025-11-04 12:06:47', 9, NULL, NULL),
(332, 48, 'Leaf Blight', '0.00%', 'Y', '2025-11-04 12:06:47', 9, NULL, NULL),
(333, 48, 'Healthy', '0.00%', 'Y', '2025-11-04 12:06:47', 9, NULL, NULL),
(334, 48, 'Bacterial Rot', '0.00%', 'Y', '2025-11-04 12:06:47', 9, NULL, NULL),
(335, 48, 'Powdery Mildew', '0.00%', 'Y', '2025-11-04 12:06:47', 9, NULL, NULL),
(336, 48, 'Downey Mildew', '0.00%', 'Y', '2025-11-04 12:06:47', 9, NULL, NULL),
(337, 49, 'Black Rot', '99.38%', 'Y', '2025-11-12 09:36:56', 9, NULL, NULL),
(338, 49, 'ESCA', '0.62%', 'Y', '2025-11-12 09:36:56', 9, NULL, NULL),
(339, 49, 'Leaf Blight', '0.00%', 'Y', '2025-11-12 09:36:56', 9, NULL, NULL),
(340, 49, 'Healthy', '0.00%', 'Y', '2025-11-12 09:36:56', 9, NULL, NULL),
(341, 49, 'Bacterial Rot', '0.00%', 'Y', '2025-11-12 09:36:56', 9, NULL, NULL),
(342, 49, 'Powdery Mildew', '0.00%', 'Y', '2025-11-12 09:36:56', 9, NULL, NULL),
(343, 49, 'Downey Mildew', '0.00%', 'Y', '2025-11-12 09:36:56', 9, NULL, NULL),
(344, 50, 'Black Rot', '99.38%', 'Y', '2025-12-07 21:39:54', 9, NULL, NULL),
(345, 50, 'ESCA', '0.62%', 'Y', '2025-12-07 21:39:54', 9, NULL, NULL),
(346, 50, 'Leaf Blight', '0.00%', 'Y', '2025-12-07 21:39:54', 9, NULL, NULL),
(347, 50, 'Healthy', '0.00%', 'Y', '2025-12-07 21:39:54', 9, NULL, NULL),
(348, 50, 'Bacterial Rot', '0.00%', 'Y', '2025-12-07 21:39:54', 9, NULL, NULL),
(349, 50, 'Powdery Mildew', '0.00%', 'Y', '2025-12-07 21:39:54', 9, NULL, NULL),
(350, 50, 'Downey Mildew', '0.00%', 'Y', '2025-12-07 21:39:54', 9, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `prediction_master`
--

DROP TABLE IF EXISTS `prediction_master`;
CREATE TABLE IF NOT EXISTS `prediction_master` (
  `pre_ms_id` int NOT NULL AUTO_INCREMENT,
  `image` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `is_active` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_on` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`pre_ms_id`)
) ENGINE=MyISAM AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `prediction_master`
--

INSERT INTO `prediction_master` (`pre_ms_id`, `image`, `is_active`, `created_on`, `created_by`, `modified_on`, `modified_by`) VALUES
(1, '../Prediction_images/user9/image_predict_14-09-25_20-53-05.jpg', 'Y', '2025-09-14 20:53:05', 9, NULL, NULL),
(2, '../Prediction_images/user9/image_predict_14-09-25_21-46-25.jpg', 'Y', '2025-09-14 21:46:25', 9, NULL, NULL),
(3, '../Prediction_images/user9/image_predict_14-09-25_21-52-40.jpg', 'Y', '2025-09-14 21:52:40', 9, NULL, NULL),
(4, '../Prediction_images/user9/image_predict_14-09-25_21-56-23.jpg', 'Y', '2025-09-14 21:56:23', 9, NULL, NULL),
(5, '../Prediction_images/user9/image_predict_14-09-25_22-03-07.jpg', 'Y', '2025-09-14 22:03:07', 9, NULL, NULL),
(6, '../Prediction_images/user9/image_predict_15-09-25_09-13-33.jpg', 'Y', '2025-09-15 09:13:33', 9, NULL, NULL),
(7, '../Prediction_images/user9/image_predict_15-09-25_09-14-08.jpg', 'Y', '2025-09-15 09:14:08', 9, NULL, NULL),
(8, '../Prediction_images/user9/image_predict_15-09-25_09-45-10.jpg', 'Y', '2025-09-15 09:45:10', 9, NULL, NULL),
(9, '../Prediction_images/user9/image_predict_15-09-25_10-03-07.jpg', 'Y', '2025-09-15 10:03:07', 9, NULL, NULL),
(10, '../Prediction_images/user9/image_predict_15-09-25_13-45-31.jpg', 'Y', '2025-09-15 13:45:31', 9, NULL, NULL),
(11, '../Prediction_images/user9/image_predict_15-09-25_14-10-20.jpg', 'Y', '2025-09-15 14:10:20', 9, NULL, NULL),
(12, '../Prediction_images/user9/image_predict_15-09-25_14-14-36.jpg', 'Y', '2025-09-15 14:14:36', 9, NULL, NULL),
(13, '../Prediction_images/user9/image_predict_15-09-25_14-23-29.jpg', 'Y', '2025-09-15 14:23:29', 9, NULL, NULL),
(14, '../Prediction_images/user9/image_predict_15-09-25_14-23-54.jpg', 'Y', '2025-09-15 14:23:54', 9, NULL, NULL),
(15, '../Prediction_images/user9/image_predict_15-09-25_14-24-07.jpg', 'Y', '2025-09-15 14:24:07', 9, NULL, NULL),
(16, '../Prediction_images/user9/image_predict_15-09-25_14-24-46.jpg', 'Y', '2025-09-15 14:24:46', 9, NULL, NULL),
(17, '../Prediction_images/user9/image_predict_15-09-25_14-26-26.jpg', 'Y', '2025-09-15 14:26:26', 9, NULL, NULL),
(18, '../Prediction_images/user9/image_predict_15-09-25_14-29-38.png', 'Y', '2025-09-15 14:29:38', 9, NULL, NULL),
(19, '../Prediction_images/user9/image_predict_15_09_25_23_04_16.jpg', 'Y', '2025-09-15 23:04:16', 9, NULL, NULL),
(20, '../Prediction_images/user9/image_predict_16_09_25_08_57_23.jpg', 'Y', '2025-09-16 08:57:23', 9, NULL, NULL),
(21, '../Prediction_images/user9/image_predict_17_09_25_12_16_00.jpg', 'Y', '2025-09-17 12:16:00', 9, NULL, NULL),
(22, '../Prediction_images/user9/image_predict_17_09_25_13_10_45.jpg', 'Y', '2025-09-17 13:10:45', 9, NULL, NULL),
(23, '../Prediction_images/user9/image_predict_17_09_25_13_35_02.jpg', 'Y', '2025-09-17 13:35:02', 9, NULL, NULL),
(24, '../Prediction_images/user9/image_predict_17_09_25_13_54_37.jpg', 'Y', '2025-09-17 13:54:37', 9, NULL, NULL),
(25, '../Prediction_images/user9/image_predict_17_09_25_13_55_14.jpg', 'Y', '2025-09-17 13:55:14', 9, NULL, NULL),
(26, '../Prediction_images/user9/image_predict_17_09_25_13_59_49.jpg', 'Y', '2025-09-17 13:59:49', 9, NULL, NULL),
(27, '../Prediction_images/user9/image_predict_17_09_25_14_07_10.jpg', 'Y', '2025-09-17 14:07:10', 9, NULL, NULL),
(28, '../Prediction_images/user9/image_predict_17_09_25_14_07_21.jpg', 'Y', '2025-09-17 14:07:21', 9, NULL, NULL),
(29, '../Prediction_images/user9/image_predict_17_09_25_14_11_01.jpg', 'Y', '2025-09-17 14:11:01', 9, NULL, NULL),
(30, '../Prediction_images/user9/image_predict_17_09_25_14_13_28.png', 'Y', '2025-09-17 14:13:28', 9, NULL, NULL),
(31, '../Prediction_images/user9/image_predict_17_09_25_14_16_35.png', 'Y', '2025-09-17 14:16:35', 9, NULL, NULL),
(32, '../Prediction_images/user9/image_predict_17_09_25_14_28_27.jpeg', 'Y', '2025-09-17 14:28:27', 9, NULL, NULL),
(33, '../Prediction_images/user9/image_predict_17_09_25_14_35_25.jpg', 'Y', '2025-09-17 14:35:25', 9, NULL, NULL),
(34, '../Prediction_images/user9/image_predict_17_09_25_14_36_01.jpg', 'Y', '2025-09-17 14:36:01', 9, NULL, NULL),
(35, '../Prediction_images/user9/image_predict_17_09_25_14_36_28.jpg', 'Y', '2025-09-17 14:36:28', 9, NULL, NULL),
(36, '../Prediction_images/user9/image_predict_19_09_25_10_03_39.jpg', 'Y', '2025-09-19 10:03:39', 9, NULL, NULL),
(37, '../Prediction_images/user9/image_predict_26_09_25_20_44_17.jpg', 'Y', '2025-09-26 20:44:17', 9, NULL, NULL),
(38, '../Prediction_images/user9/image_predict_17_10_25_09_18_38.jpg', 'Y', '2025-10-17 09:18:38', 9, NULL, NULL),
(39, '../Prediction_images/user9/image_predict_26_10_25_21_10_03.jpg', 'Y', '2025-10-26 21:10:03', 9, NULL, NULL),
(40, '../Prediction_images/user9/image_predict_30_10_25_11_49_57.jpg', 'Y', '2025-10-30 11:49:57', 9, NULL, NULL),
(41, '../Prediction_images/user9/image_predict_30_10_25_11_50_22.jpg', 'Y', '2025-10-30 11:50:22', 9, NULL, NULL),
(42, '../Prediction_images/user9/image_predict_30_10_25_12_16_11.jpg', 'Y', '2025-10-30 12:16:11', 9, NULL, NULL),
(43, '../Prediction_images/user9/image_predict_30_10_25_12_25_44.jpg', 'Y', '2025-10-30 12:25:44', 9, NULL, NULL),
(44, '../Prediction_images/user9/image_predict_30_10_25_12_36_25.jpg', 'Y', '2025-10-30 12:36:25', 9, NULL, NULL),
(45, '../Prediction_images/user9/image_predict_30_10_25_12_38_15.jpg', 'Y', '2025-10-30 12:38:15', 9, NULL, NULL),
(46, '../Prediction_images/user9/image_predict_04_11_25_11_44_48.jpg', 'Y', '2025-11-04 11:44:48', 9, NULL, NULL),
(47, '../Prediction_images/user9/image_predict_04_11_25_11_45_19.jpg', 'Y', '2025-11-04 11:45:19', 9, NULL, NULL),
(48, '../Prediction_images/user9/image_predict_04_11_25_12_06_47.jpg', 'Y', '2025-11-04 12:06:47', 9, NULL, NULL),
(49, '../Prediction_images/user9/image_predict_12_11_25_09_36_56.jpg', 'Y', '2025-11-12 09:36:56', 9, NULL, NULL),
(50, '../Prediction_images/user9/image_predict_07_12_25_21_39_54.jpg', 'Y', '2025-12-07 21:39:54', 9, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `pro_id` int NOT NULL AUTO_INCREMENT,
  `pro_name` text COLLATE utf8mb4_0900_ai_ci,
  `pro_image` text COLLATE utf8mb4_0900_ai_ci,
  `pro_description` text COLLATE utf8mb4_0900_ai_ci,
  `pro_uses` text COLLATE utf8mb4_0900_ai_ci,
  `pro_contents` text COLLATE utf8mb4_0900_ai_ci,
  `type` varchar(200) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `is_block` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'N',
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_on` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `vendor_id` int DEFAULT NULL,
  `farmer_id` int DEFAULT NULL,
  `product_source` enum('farmer','vendor') COLLATE utf8mb4_0900_ai_ci DEFAULT 'vendor',
  PRIMARY KEY (`pro_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`pro_id`, `pro_name`, `pro_image`, `pro_description`, `pro_uses`, `pro_contents`, `type`, `is_block`, `is_active`, `created_on`, `created_by`, `modified_on`, `modified_by`, `vendor_id`, `farmer_id`, `product_source`) VALUES
(1, 'Product Test', '', 'Testing Description', 'use to plat trees', 'seeds', 'seed', 'N', 'Y', '2026-05-01 09:41:30', 10, NULL, NULL, 1, NULL, 'vendor'),
(2, 'testing', 'uploads/farmer_products/product_9_1777741853_89d2b757.jpg', 'testing', 'testing', 'testing', 'Fruits', 'N', 'Y', '2026-05-02 22:40:53', 9, NULL, NULL, NULL, 1, 'farmer'),
(3, 'abc', 'uploads/farmer_products/product_9_1777742758_cdee77f9.jpeg', 'testing', 'testing', 'testing', 'Vegetables', 'N', 'Y', '2026-05-02 22:55:58', 9, NULL, NULL, NULL, 1, 'farmer');

-- --------------------------------------------------------

--
-- Table structure for table `product_approval`
--

DROP TABLE IF EXISTS `product_approval`;
CREATE TABLE IF NOT EXISTS `product_approval` (
  `approval_id` int NOT NULL AUTO_INCREMENT,
  `pro_id` int NOT NULL,
  `vendor_id` int NOT NULL,
  `approval_status` enum('pending','approved','rejected') COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending',
  `rejection_reason` text COLLATE utf8mb4_0900_ai_ci,
  `approval_date` datetime DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`approval_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `product_approval`
--

INSERT INTO `product_approval` (`approval_id`, `pro_id`, `vendor_id`, `approval_status`, `rejection_reason`, `approval_date`, `approved_by`, `is_active`, `created_on`, `created_by`) VALUES
(1, 1, 1, 'approved', NULL, '2026-05-01 09:42:24', 1, 'Y', '2026-05-01 09:41:30', 10);

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

DROP TABLE IF EXISTS `product_reviews`;
CREATE TABLE IF NOT EXISTS `product_reviews` (
  `review_id` int NOT NULL AUTO_INCREMENT,
  `pro_id` int NOT NULL,
  `user_id` int NOT NULL,
  `rating` int DEFAULT NULL,
  `review_text` text COLLATE utf8mb4_0900_ai_ci,
  `seller_id` int DEFAULT NULL,
  `product_source` enum('farmer','vendor') COLLATE utf8mb4_0900_ai_ci DEFAULT 'vendor',
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_on` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`review_id`)
) ;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`review_id`, `pro_id`, `user_id`, `rating`, `review_text`, `seller_id`, `product_source`, `is_active`, `created_on`, `modified_on`, `modified_by`) VALUES
(1, 1, 12, 5, 'good', 1, 'vendor', 'Y', '2026-05-01 16:06:38', NULL, NULL),
(2, 2, 12, 5, 'Testing', 1, 'farmer', 'Y', '2026-05-02 22:47:22', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pro_feedback`
--

DROP TABLE IF EXISTS `pro_feedback`;
CREATE TABLE IF NOT EXISTS `pro_feedback` (
  `feedback_id ineger` int NOT NULL AUTO_INCREMENT,
  `pro_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_0900_ai_ci,
  `created_on` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`feedback_id ineger`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pro_inventory`
--

DROP TABLE IF EXISTS `pro_inventory`;
CREATE TABLE IF NOT EXISTS `pro_inventory` (
  `pro_inventory_id` int NOT NULL AUTO_INCREMENT,
  `pro_id` int DEFAULT NULL,
  `pro_price` varchar(50) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `pro_mfg_date` date DEFAULT NULL,
  `pro_exp_date` date DEFAULT NULL,
  `pro_discunt` varchar(3) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `pro_weigth` varchar(300) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `pro_qty` varchar(300) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_by` int DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `modified_on` datetime DEFAULT NULL,
  PRIMARY KEY (`pro_inventory_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pro_inventory`
--

INSERT INTO `pro_inventory` (`pro_inventory_id`, `pro_id`, `pro_price`, `pro_mfg_date`, `pro_exp_date`, `pro_discunt`, `pro_weigth`, `pro_qty`, `is_active`, `created_by`, `created_on`, `modified_by`, `modified_on`) VALUES
(1, 1, '100', NULL, NULL, NULL, NULL, '500', 'Y', 10, '2026-05-01 09:41:30', NULL, NULL),
(2, 2, '200', NULL, NULL, NULL, NULL, '4521', 'Y', 9, '2026-05-02 22:40:53', NULL, NULL),
(3, 3, '100', NULL, NULL, NULL, NULL, '4500', 'Y', 9, '2026-05-02 22:55:58', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pro_rating`
--

DROP TABLE IF EXISTS `pro_rating`;
CREATE TABLE IF NOT EXISTS `pro_rating` (
  `pro_rating_id` int NOT NULL AUTO_INCREMENT,
  `pro_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `rating` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  PRIMARY KEY (`pro_rating_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_product`
--

DROP TABLE IF EXISTS `purchase_product`;
CREATE TABLE IF NOT EXISTS `purchase_product` (
  `purchas_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `pro_id` int DEFAULT NULL,
  `pro_qty` int DEFAULT NULL,
  `total_amt` int DEFAULT NULL,
  `payment_method` text COLLATE utf8mb4_0900_ai_ci,
  `transaction_id` text COLLATE utf8mb4_0900_ai_ci,
  `created_by` int DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `product_source` enum('farmer','vendor') COLLATE utf8mb4_0900_ai_ci DEFAULT 'vendor',
  `seller_id` int DEFAULT NULL,
  PRIMARY KEY (`purchas_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase_product`
--

INSERT INTO `purchase_product` (`purchas_id`, `user_id`, `pro_id`, `pro_qty`, `total_amt`, `payment_method`, `transaction_id`, `created_by`, `created_on`, `product_source`, `seller_id`) VALUES
(1, 9, 1, 3, 300, 'UPI', 'TXN202605010943409', 9, '2026-05-01 09:43:40', 'vendor', NULL),
(2, 12, 2, 3, 600, 'UPI', 'T485157485157895156578515', 12, '2026-05-02 22:50:12', 'farmer', NULL),
(3, 12, 1, 1, 100, 'UPI', 'T485157485157895156578515', 12, '2026-05-02 22:50:12', 'vendor', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `user_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `role` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `image` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `mb_number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `is_active` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_on` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `user_name`, `email`, `password`, `role`, `image`, `mb_number`, `is_active`, `created_on`, `created_by`, `modified_on`, `modified_by`) VALUES
(1, 'Admin', 'admin@admin', 'c4ca4238a0b923820dcc509a6f75849b', 'admin', NULL, NULL, 'Y', NULL, NULL, NULL, NULL),
(9, 'ABC Farmer', 'abc@user', 'c4ca4238a0b923820dcc509a6f75849b', 'farmer', 'assets/dist/img/farmer_profiles/farmer_9_1777631382.png', NULL, 'Y', '2025-09-02 22:22:59', NULL, NULL, NULL),
(10, 'Vendor', 'vendor@cropintel.com', 'c4ca4238a0b923820dcc509a6f75849b', 'PesticideVendor', 'assets/dist/img/vendor_profiles/vendor_10_1777608559.png', NULL, 'Y', '2026-05-01 00:28:31', NULL, NULL, NULL),
(11, 'consultent', 'consultent@cropintel.com', 'c4ca4238a0b923820dcc509a6f75849b', 'consultant', NULL, NULL, 'Y', '2026-05-01 10:11:07', NULL, NULL, NULL),
(12, 'buyer', 'buyer@cropintel.com', 'c4ca4238a0b923820dcc509a6f75849b', 'buyer', 'assets/dist/img/buyer_profiles/buyer_12_1777631483.jpg', NULL, 'Y', '2026-05-01 15:41:44', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_cart`
--

DROP TABLE IF EXISTS `user_cart`;
CREATE TABLE IF NOT EXISTS `user_cart` (
  `cart_id` int NOT NULL AUTO_INCREMENT,
  `pro_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `pro_qty` int DEFAULT NULL,
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_on` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`cart_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_cart`
--

INSERT INTO `user_cart` (`cart_id`, `pro_id`, `user_id`, `pro_qty`, `is_active`, `created_on`, `created_by`, `modified_on`, `modified_by`) VALUES
(1, 1, 9, 3, 'N', '2026-05-01 09:43:31', 9, '2026-05-01 09:43:40', 9);

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
CREATE TABLE IF NOT EXISTS `vendors` (
  `vendor_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `license_no` varchar(100) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `phone_number` varchar(10) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `license_verified` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'N',
  `verification_status` enum('pending','approved','rejected') COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending',
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `modified_on` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  PRIMARY KEY (`vendor_id`),
  UNIQUE KEY `license_no` (`license_no`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`vendor_id`, `user_id`, `company_name`, `license_no`, `location`, `phone_number`, `license_verified`, `verification_status`, `is_active`, `created_on`, `created_by`, `modified_on`, `modified_by`) VALUES
(1, 10, 'Test', 'test lic', 'nashik', '1234567890', 'Y', 'approved', 'Y', '2026-05-01 00:36:38', 10, '2026-05-01 09:40:02', 1);

-- --------------------------------------------------------

--
-- Table structure for table `vendor_feedback`
--

DROP TABLE IF EXISTS `vendor_feedback`;
CREATE TABLE IF NOT EXISTS `vendor_feedback` (
  `ven_feed_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `vendor_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_0900_ai_ci,
  `created_on` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`ven_feed_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_products`
--

DROP TABLE IF EXISTS `vendor_products`;
CREATE TABLE IF NOT EXISTS `vendor_products` (
  `vendor_product_id` int NOT NULL AUTO_INCREMENT,
  `vendor_id` int NOT NULL,
  `pro_id` int NOT NULL,
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`vendor_product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `vendor_products`
--

INSERT INTO `vendor_products` (`vendor_product_id`, `vendor_id`, `pro_id`, `is_active`, `created_on`) VALUES
(1, 1, 1, 'Y', '2026-05-01 09:41:30');

-- --------------------------------------------------------

--
-- Table structure for table `vendor_rating`
--

DROP TABLE IF EXISTS `vendor_rating`;
CREATE TABLE IF NOT EXISTS `vendor_rating` (
  `vendor_rating_id` int NOT NULL,
  `vendor_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `rating` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  PRIMARY KEY (`vendor_rating_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `video_tutorial`
--

DROP TABLE IF EXISTS `video_tutorial`;
CREATE TABLE IF NOT EXISTS `video_tutorial` (
  `video_tutorial_id` int NOT NULL AUTO_INCREMENT,
  `video` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `uploaded_by` int DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `thumbnail` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `is_active` varchar(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `modified_on` datetime DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `video_url` varchar(500) COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  PRIMARY KEY (`video_tutorial_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `video_tutorial`
--

INSERT INTO `video_tutorial` (`video_tutorial_id`, `video`, `title`, `description`, `uploaded_by`, `approval_status`, `approved_by`, `thumbnail`, `is_active`, `created_on`, `created_by`, `modified_on`, `modified_by`, `video_url`) VALUES
(1, '../Video_tutorials/1.mp4', 'how to identify and manage common crop disease', 'In this episode, we reveal how to identify and manage common crop diseases that threaten your yield. Whether you’re a small scale farmer or a large-scale farmer, this podcast gives you the tools to protect your crops and increase your harvest!', NULL, 'pending', NULL, '../Thumbnail/1.png', 'Y', NULL, NULL, NULL, NULL, NULL),
(7, '../Video_tutorials/6.mp4', 'Diagnosing Bacterial Diseases in Onions', 'This video provides an overview of Xanthomonas leaf spot, a bacterial disease that affects onions . It discusses the symptoms of the disease, such as long, brownish lesions on the leaves, and the importance of laboratory confirmation . The video also covers the impact of the disease, which can lead to a significant decrease in bulb size and a potential yield loss of up to 25% . Finally, it suggests several management strategies, including crop rotation, planting certified seeds, and using pesticides .', NULL, 'pending', NULL, '../Thumbnail/7.png', 'Y', NULL, NULL, NULL, NULL, NULL),
(2, '../Video_tutorials/2.mp4', 'Crop Disease Detection Using UAV and Deep Learning Techniques', 'The video discusses the use of UAV-based remote sensing, specifically machine learning and deep learning, for detecting crop diseases . The video highlights how this technology can help in advancing precision agriculture to monitor crops, prevent disease, and increase productivity.', NULL, 'pending', NULL, '../Thumbnail/2.png', 'Y', NULL, NULL, NULL, NULL, NULL),
(3, '../Video_tutorials/3.mp4', 'Aerial Potato Disease Detection with Hyperspectral Systems', 'In this video, plant pathologist Katie Gold discusses the importance of early disease detection in agriculture to prevent significant crop losses . She explains her team\'s technology, which combines plant pathology, remote sensing, and data science to measure how light interacts with plants . This method can detect subtle changes in light reflection that indicate plant stress or disease long before they are visible to the human eye .\r\n\r\nThe video notes that this aerial detection technology can accurately map specific disease incidents in potato fields, even when the disease incidence is very low . The preliminary findings suggest that aerial detection is possible in real-world applications and that early data acquisition provides better insights into crop health than just a visual inspection .', NULL, 'pending', NULL, '../Thumbnail/3.png', 'Y', NULL, NULL, NULL, NULL, NULL),
(4, '../Video_tutorials/4.mp4', 'Monitoring Crop Health With Drones | Maryland Farm & Harvest', 'REmote sensing Crop disease detection \r\nThis video explains how a Maryland-based company, Mad Tech, uses drones to help farmers like Sam Parker manage their fields more effectively. The drones are equipped with multispectral cameras that create a detailed health map of the entire field, which uses colors to indicate the health of the crops . This technology helps farmers pinpoint specific problem areas, saving them time and money by allowing them to scout only the stressed areas instead of the entire field. The video concludes that this approach combines modern technology with traditional farming practices to help farmers ensure the long-term success of their operations', NULL, 'pending', NULL, '../Thumbnail/6.png', 'Y', NULL, NULL, NULL, NULL, NULL),
(5, '../Video_tutorials/5.mp4', 'Researchers Develop Disease-Resistant Grapes', 'This video discusses a new solution to Pierce\'s disease, which has been a chronic problem for California winemakers for 20 years. UC Davis researchers have created a disease-resistant grape by crossbreeding traditional wine grapes with a naturally resistant grape from Mexico', NULL, 'pending', NULL, '../Thumbnail/5.png', 'Y', NULL, NULL, NULL, NULL, NULL),
(8, 'Video_tutorials/video_11_1777612762.mp4', 'testing', 'test', 11, 'pending', NULL, '0', 'Y', '2026-05-01 10:49:22', 11, NULL, NULL, NULL),
(9, 'Video_tutorials/video_11_1777613021.mp4', 'testing', 'test', 11, 'approved', NULL, 'Thumbnail/thumb_11_1777613021.jpg', 'Y', '2026-05-01 10:53:41', 11, '2026-05-01 11:49:36', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `video_tutorials_approval`
--

DROP TABLE IF EXISTS `video_tutorials_approval`;
CREATE TABLE IF NOT EXISTS `video_tutorials_approval` (
  `video_approval_id` int NOT NULL AUTO_INCREMENT,
  `video_tutorial_id` int NOT NULL,
  `approval_status` enum('pending','approved','rejected') COLLATE utf8mb4_0900_ai_ci DEFAULT 'pending',
  `rejection_reason` text COLLATE utf8mb4_0900_ai_ci,
  `approval_date` datetime DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `is_active` varchar(1) COLLATE utf8mb4_0900_ai_ci DEFAULT 'Y',
  `created_on` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`video_approval_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `consultancy_ratings`
--
ALTER TABLE `consultancy_ratings`
  ADD CONSTRAINT `consultancy_ratings_ibfk_1` FOREIGN KEY (`subscription_id`) REFERENCES `farmer_consultancy_subscriptions` (`subscription_id`),
  ADD CONSTRAINT `consultancy_ratings_ibfk_2` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`consultant_id`),
  ADD CONSTRAINT `consultancy_ratings_ibfk_3` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`farmer_id`);

--
-- Constraints for table `consultancy_services`
--
ALTER TABLE `consultancy_services`
  ADD CONSTRAINT `consultancy_services_ibfk_1` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`consultant_id`);

--
-- Constraints for table `consultancy_sessions`
--
ALTER TABLE `consultancy_sessions`
  ADD CONSTRAINT `consultancy_sessions_ibfk_1` FOREIGN KEY (`subscription_id`) REFERENCES `farmer_consultancy_subscriptions` (`subscription_id`),
  ADD CONSTRAINT `consultancy_sessions_ibfk_2` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`consultant_id`),
  ADD CONSTRAINT `consultancy_sessions_ibfk_3` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`farmer_id`);

--
-- Constraints for table `farmer_consultancy_subscriptions`
--
ALTER TABLE `farmer_consultancy_subscriptions`
  ADD CONSTRAINT `farmer_consultancy_subscriptions_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`farmer_id`),
  ADD CONSTRAINT `farmer_consultancy_subscriptions_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `consultancy_services` (`service_id`),
  ADD CONSTRAINT `farmer_consultancy_subscriptions_ibfk_3` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`consultant_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
