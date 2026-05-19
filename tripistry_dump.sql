-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               12.2.2-MariaDB - MariaDB Server
-- Server OS:                    Win64
-- HeidiSQL Version:             12.14.0.7165
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for tripistry
CREATE DATABASE IF NOT EXISTS `tripistry` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `tripistry`;

-- Dumping structure for table tripistry.accomodation
CREATE TABLE IF NOT EXISTS `accomodation` (
  `AccID` int(11) NOT NULL AUTO_INCREMENT,
  `TOID` int(11) NOT NULL,
  `Attribute` varchar(255) NOT NULL,
  PRIMARY KEY (`AccID`),
  UNIQUE KEY `TOID` (`TOID`),
  CONSTRAINT `fk_acc_to` FOREIGN KEY (`TOID`) REFERENCES `tourism_offerings` (`TOID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.accomodation: ~10 rows (approximately)
REPLACE INTO `accomodation` (`AccID`, `TOID`, `Attribute`) VALUES
	(1, 11, 'Pool, Spa, Free WiFi, Concierge'),
	(2, 12, 'Infinity Pool, Jungle View, Yoga Studio'),
	(3, 13, 'Rooftop Pool, Bar, Mountain View'),
	(4, 14, 'Gym, Sauna, Restaurant, City View'),
	(5, 15, 'Private Beach, Water Park, Multiple Pools'),
	(6, 16, 'Garden Terrace, Bar, Spa, Free WiFi'),
	(7, 17, 'Infinity Pool, Casino, Multiple Restaurants'),
	(8, 18, 'Overwater Villa, Private Pool, Snorkelling'),
	(9, 19, 'Beach Access, Spa, Pool, Water Sports'),
	(10, 20, 'Caldera View, Private Plunge Pool, Breakfast');

-- Dumping structure for table tripistry.agencies
CREATE TABLE IF NOT EXISTS `agencies` (
  `AgentID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  PRIMARY KEY (`AgentID`),
  UNIQUE KEY `UserID` (`UserID`),
  CONSTRAINT `fk_ag_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.agencies: ~10 rows (approximately)
REPLACE INTO `agencies` (`AgentID`, `UserID`, `Name`) VALUES
	(1, 21, 'Suntrail Travel'),
	(2, 22, 'Horizons Travel'),
	(3, 23, 'WanderSA Tours'),
	(4, 24, 'GlobeTreks SA'),
	(5, 25, 'African Roots Expeditions'),
	(6, 26, 'Skybound Travel'),
	(7, 27, 'Coastal Escapes'),
	(8, 28, 'Nomad Pathways'),
	(9, 29, 'Velvet Voyages'),
	(10, 30, 'Peak Adventures');

-- Dumping structure for table tripistry.agency_experiences
CREATE TABLE IF NOT EXISTS `agency_experiences` (
  `ExpNum` int(11) NOT NULL AUTO_INCREMENT,
  `AgentID` int(11) NOT NULL,
  `ClientID` int(11) NOT NULL,
  `Description` text NOT NULL,
  `Rating` tinyint(4) NOT NULL CHECK (`Rating` between 1 and 5),
  PRIMARY KEY (`ExpNum`),
  KEY `fk_exp_agent` (`AgentID`),
  KEY `fk_exp_client` (`ClientID`),
  CONSTRAINT `fk_exp_agent` FOREIGN KEY (`AgentID`) REFERENCES `agencies` (`AgentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_exp_client` FOREIGN KEY (`ClientID`) REFERENCES `clients` (`ClientID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.agency_experiences: ~30 rows (approximately)
REPLACE INTO `agency_experiences` (`ExpNum`, `AgentID`, `ClientID`, `Description`, `Rating`) VALUES
	(1, 1, 1, 'Suntrail handled all our transfers seamlessly. Professional from start to finish.', 5),
	(2, 1, 3, 'Booking process was smooth and the Paris itinerary was perfect for a couple.', 5),
	(3, 2, 2, 'Horizons Travel was responsive and flexible when we needed to change our dates.', 4),
	(4, 2, 5, 'Good overall experience. Cape Town package was well priced and well organised.', 4),
	(5, 3, 4, 'WanderSA curated a lovely Bali trip. Very attentive and personal service.', 5),
	(6, 3, 9, 'Bangkok tour was well structured. Guide was knowledgeable and friendly.', 4),
	(7, 4, 6, 'GlobeTreks arranged an amazing Tokyo experience. Attention to detail was excellent.', 5),
	(8, 4, 13, 'Singapore package exceeded our expectations. Top notch agency.', 5),
	(9, 5, 7, 'African Roots truly knows the continent. Kruger safari was unforgettable.', 5),
	(10, 5, 17, 'Victoria Falls trip was life-changing. Every detail was looked after.', 5),
	(11, 6, 8, 'Skybound arranged a seamless Dubai trip. Luxury transfers and great hotel choice.', 4),
	(12, 6, 11, 'New York package was comprehensive. Got excellent seats to a Broadway show.', 4),
	(13, 7, 10, 'Coastal Escapes know the SA coastline inside out. Durban trip was fantastic.', 4),
	(14, 7, 12, 'Cape Point day trip was well priced and perfectly timed. Great value.', 4),
	(15, 8, 14, 'Nomad Pathways arranged our Zanzibar honeymoon perfectly. Magical experience.', 5),
	(16, 8, 23, 'Rome and Vatican tour was brilliant. Private guide made all the difference.', 5),
	(17, 9, 15, 'Velvet Voyages is the most luxurious agency we have used. Maldives was dreamlike.', 5),
	(18, 9, 16, 'Santorini package from Velvet was impeccable. Every detail was thoughtful.', 5),
	(19, 10, 18, 'Peak Adventures planned a fantastic Serengeti safari. Guides were exceptional.', 5),
	(20, 10, 19, 'Kyoto cherry blossom tour was magical. Perfect timing and beautiful hotels.', 5),
	(21, 1, 26, 'Suntrail helped us plan a last minute trip and pulled it off perfectly.', 4),
	(22, 2, 21, 'Horizons Travel found us great deals on the Barcelona package. Very happy.', 4),
	(23, 3, 22, 'WanderSA arranged a wonderful Marrakech cultural tour. Souks and palaces.', 4),
	(24, 4, 24, 'GlobeTreks Phuket island hopping package was great value for money.', 4),
	(25, 5, 28, 'African Roots budget Kruger package was excellent. More than we expected.', 4),
	(26, 6, 29, 'Skybound Mauritius holiday was beautifully organised. Beach resort was stunning.', 5),
	(27, 7, 20, 'Coastal Escapes Mauritius trip had flawless logistics and a gorgeous hotel.', 5),
	(28, 8, 25, 'Nomad Pathways Nairobi and Maasai Mara tour was the trip of a lifetime.', 5),
	(29, 9, 27, 'Velvet Voyages Lisbon package was charming and well priced for the quality.', 4),
	(30, 10, 30, 'Peak Adventures organised our Prague city break superbly. Great hidden gems tour.', 4);

-- Dumping structure for table tripistry.airplanes
CREATE TABLE IF NOT EXISTS `airplanes` (
  `PlaneID` int(11) NOT NULL AUTO_INCREMENT,
  `PortID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  PRIMARY KEY (`PlaneID`),
  KEY `fk_plane_port` (`PortID`),
  CONSTRAINT `fk_plane_port` FOREIGN KEY (`PortID`) REFERENCES `airports` (`PortID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.airplanes: ~30 rows (approximately)
REPLACE INTO `airplanes` (`PlaneID`, `PortID`, `Name`) VALUES
	(1, 1, 'Boeing 777-300ER'),
	(2, 2, 'Airbus A320neo'),
	(3, 3, 'Boeing 737-800'),
	(4, 4, 'Boeing 787-9 Dreamliner'),
	(5, 5, 'Airbus A380-800'),
	(6, 6, 'Boeing 777-200LR'),
	(7, 7, 'Airbus A330-300'),
	(8, 8, 'Boeing 787-8'),
	(9, 9, 'Airbus A350-900'),
	(10, 10, 'Boeing 737 MAX 8'),
	(11, 11, 'Airbus A320'),
	(12, 12, 'Boeing 757-200'),
	(13, 13, 'Boeing 767-300ER'),
	(14, 14, 'Airbus A380-800'),
	(15, 15, 'Boeing 777-300'),
	(16, 16, 'De Havilland Dash 8'),
	(17, 17, 'Airbus A321neo'),
	(18, 18, 'Airbus A330-200'),
	(19, 19, 'Cessna Caravan 208'),
	(20, 20, 'ATR 72-600'),
	(21, 21, 'Boeing 737-900'),
	(22, 22, 'Airbus A320neo'),
	(23, 23, 'De Havilland Dash 8'),
	(24, 24, 'Boeing 737-800'),
	(25, 25, 'Airbus A321'),
	(26, 26, 'Boeing 737-700'),
	(27, 27, 'Airbus A330-300'),
	(28, 28, 'Boeing 787-9'),
	(29, 29, 'Airbus A320'),
	(30, 30, 'Boeing 737-800');

-- Dumping structure for table tripistry.airports
CREATE TABLE IF NOT EXISTS `airports` (
  `PortID` int(11) NOT NULL AUTO_INCREMENT,
  `DestID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `City` varchar(100) NOT NULL,
  PRIMARY KEY (`PortID`),
  KEY `fk_ap_dest` (`DestID`),
  CONSTRAINT `fk_ap_dest` FOREIGN KEY (`DestID`) REFERENCES `destinations` (`DestID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.airports: ~30 rows (approximately)
REPLACE INTO `airports` (`PortID`, `DestID`, `Name`, `City`) VALUES
	(1, 1, 'Charles de Gaulle Airport', 'Paris'),
	(2, 2, 'Ngurah Rai International Airport', 'Denpasar'),
	(3, 3, 'Cape Town International Airport', 'Cape Town'),
	(4, 4, 'Narita International Airport', 'Tokyo'),
	(5, 5, 'John F. Kennedy International', 'New York City'),
	(6, 6, 'Dubai International Airport', 'Dubai'),
	(7, 7, 'Leonardo da Vinci Airport', 'Rome'),
	(8, 8, 'Suvarnabhumi Airport', 'Bangkok'),
	(9, 9, 'Sydney Kingsford Smith Airport', 'Sydney'),
	(10, 10, 'OR Tambo International Airport', 'Johannesburg'),
	(11, 11, 'Marrakech Menara Airport', 'Marrakech'),
	(12, 12, 'El Prat Airport', 'Barcelona'),
	(13, 13, 'Jomo Kenyatta International', 'Nairobi'),
	(14, 14, 'Changi Airport', 'Singapore'),
	(15, 15, 'Istanbul Airport', 'Istanbul'),
	(16, 16, 'Abeid Amani Karume Airport', 'Zanzibar City'),
	(17, 17, 'Humberto Delgado Airport', 'Lisbon'),
	(18, 18, 'Velana International Airport', 'Male'),
	(19, 19, 'Eastgate Airport', 'Phalaborwa'),
	(20, 20, 'Santorini National Airport', 'Fira'),
	(21, 21, 'Amsterdam Airport Schiphol', 'Amsterdam'),
	(22, 22, 'Phuket International Airport', 'Phuket City'),
	(23, 23, 'Harry Mwanga Nkumbula Airport', 'Livingstone'),
	(24, 24, 'Kilimanjaro International Airport', 'Arusha'),
	(25, 25, 'Miami International Airport', 'Miami'),
	(26, 26, 'Vaclav Havel Airport', 'Prague'),
	(27, 27, 'Sir Seewoosagur Ramgoolam Airport', 'Port Louis'),
	(28, 28, 'Kansai International Airport', 'Kyoto'),
	(29, 29, 'Cairo International Airport', 'Cairo'),
	(30, 30, 'King Shaka International Airport', 'Durban');

-- Dumping structure for table tripistry.attractions
CREATE TABLE IF NOT EXISTS `attractions` (
  `AttID` int(11) NOT NULL AUTO_INCREMENT,
  `TOID` int(11) NOT NULL,
  `Price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `TimeOpen` time NOT NULL,
  `TimeClose` time NOT NULL,
  PRIMARY KEY (`AttID`),
  UNIQUE KEY `TOID` (`TOID`),
  CONSTRAINT `fk_att_to` FOREIGN KEY (`TOID`) REFERENCES `tourism_offerings` (`TOID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.attractions: ~0 rows (approximately)
REPLACE INTO `attractions` (`AttID`, `TOID`, `Price`, `TimeOpen`, `TimeClose`) VALUES
	(1, 1, 280.00, '09:00:00', '23:45:00'),
	(2, 2, 75.00, '07:00:00', '19:00:00'),
	(3, 3, 350.00, '08:00:00', '17:00:00'),
	(4, 4, 0.00, '06:00:00', '17:00:00'),
	(5, 5, 380.00, '08:00:00', '23:00:00'),
	(6, 6, 180.00, '09:00:00', '19:00:00'),
	(7, 7, 100.00, '08:00:00', '18:00:00'),
	(8, 8, 250.00, '09:00:00', '20:00:00'),
	(9, 9, 0.00, '06:00:00', '18:00:00'),
	(10, 10, 200.00, '08:00:00', '17:00:00');

-- Dumping structure for table tripistry.clients
CREATE TABLE IF NOT EXISTS `clients` (
  `ClientID` int(11) NOT NULL AUTO_INCREMENT,
  `TravID` int(11) NOT NULL,
  `AgentID` int(11) NOT NULL,
  PRIMARY KEY (`ClientID`),
  UNIQUE KEY `uq_client` (`TravID`,`AgentID`),
  KEY `fk_cl_agent` (`AgentID`),
  CONSTRAINT `fk_cl_agent` FOREIGN KEY (`AgentID`) REFERENCES `agencies` (`AgentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cl_trav` FOREIGN KEY (`TravID`) REFERENCES `travellers` (`TravID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.clients: ~0 rows (approximately)
REPLACE INTO `clients` (`ClientID`, `TravID`, `AgentID`) VALUES
	(1, 1, 1),
	(2, 1, 2),
	(3, 2, 1),
	(4, 2, 3),
	(5, 3, 2),
	(6, 3, 4),
	(7, 4, 1),
	(8, 4, 5),
	(9, 5, 3),
	(10, 5, 6),
	(11, 6, 2),
	(12, 6, 7),
	(13, 7, 4),
	(14, 7, 8),
	(15, 8, 1),
	(16, 8, 9),
	(17, 9, 5),
	(18, 9, 10),
	(19, 10, 3),
	(20, 10, 6),
	(21, 11, 2),
	(22, 11, 7),
	(23, 12, 8),
	(24, 12, 4),
	(25, 13, 9),
	(26, 13, 1),
	(27, 14, 10),
	(28, 14, 5),
	(29, 15, 6),
	(30, 15, 3);

-- Dumping structure for table tripistry.contact_numbers
CREATE TABLE IF NOT EXISTS `contact_numbers` (
  `CDID` int(11) NOT NULL,
  `Number` varchar(20) NOT NULL,
  `Type` varchar(20) NOT NULL DEFAULT 'Mobile',
  PRIMARY KEY (`CDID`,`Number`),
  CONSTRAINT `fk_cn_cd` FOREIGN KEY (`CDID`) REFERENCES `contactdetails` (`CDID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.contact_numbers: ~30 rows (approximately)
REPLACE INTO `contact_numbers` (`CDID`, `Number`, `Type`) VALUES
	(1, '+27811234001', 'Mobile'),
	(2, '+27821234002', 'Mobile'),
	(3, '+27831234003', 'Mobile'),
	(4, '+27841234004', 'Mobile'),
	(5, '+27851234005', 'Mobile'),
	(6, '+27861234006', 'Mobile'),
	(7, '+27871234007', 'Mobile'),
	(8, '+27881234008', 'Mobile'),
	(9, '+27891234009', 'Mobile'),
	(10, '+27801234010', 'Mobile'),
	(11, '+27811234011', 'Mobile'),
	(12, '+27821234012', 'Mobile'),
	(13, '+27831234013', 'Mobile'),
	(14, '+27841234014', 'Mobile'),
	(15, '+27851234015', 'Mobile'),
	(16, '+27861234016', 'Mobile'),
	(17, '+27871234017', 'Mobile'),
	(18, '+27881234018', 'Mobile'),
	(19, '+27891234019', 'Mobile'),
	(20, '+27801234020', 'Mobile'),
	(21, '+27110001121', 'Work'),
	(22, '+27110001122', 'Work'),
	(23, '+27110001123', 'Work'),
	(24, '+27110001124', 'Work'),
	(25, '+27110001125', 'Work'),
	(26, '+27110001126', 'Work'),
	(27, '+27110001127', 'Work'),
	(28, '+27110001128', 'Work'),
	(29, '+27110001129', 'Work'),
	(30, '+27110001130', 'Work');

-- Dumping structure for table tripistry.contactdetails
CREATE TABLE IF NOT EXISTS `contactdetails` (
  `CDID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `Email` varchar(100) NOT NULL,
  PRIMARY KEY (`CDID`),
  UNIQUE KEY `UserID` (`UserID`),
  CONSTRAINT `fk_cd_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.contactdetails: ~0 rows (approximately)
REPLACE INTO `contactdetails` (`CDID`, `UserID`, `Email`) VALUES
	(1, 1, 'james.olivier@gmail.com'),
	(2, 2, 'priya.naidoo@gmail.com'),
	(3, 3, 'luca.ferrari@hotmail.com'),
	(4, 4, 'amara.diallo@yahoo.com'),
	(5, 5, 'ethan.brooks@gmail.com'),
	(6, 6, 'sofia.martins@gmail.com'),
	(7, 7, 'kwame.asante@outlook.com'),
	(8, 8, 'nina.petrov@gmail.com'),
	(9, 9, 'diego.reyes@yahoo.com'),
	(10, 10, 'yuki.tanaka@gmail.com'),
	(11, 11, 'chloe.dupont@hotmail.com'),
	(12, 12, 'marco.rossi@gmail.com'),
	(13, 13, 'fatima.hassan@outlook.com'),
	(14, 14, 'ryan.mitchell@gmail.com'),
	(15, 15, 'aisha.kamara@gmail.com'),
	(16, 16, 'tom.vandenberg@gmail.com'),
	(17, 17, 'mei.chen@hotmail.com'),
	(18, 18, 'oliver.james@gmail.com'),
	(19, 19, 'sara.kowalski@yahoo.com'),
	(20, 20, 'ben.okafor@gmail.com'),
	(21, 21, 'info@suntrailtravel.co.za'),
	(22, 22, 'contact@horizonstravel.co.za'),
	(23, 23, 'hello@wandersatours.co.za'),
	(24, 24, 'admin@globetreks.co.za'),
	(25, 25, 'info@africanroots.co.za'),
	(26, 26, 'bookings@skyboundtravel.co.za'),
	(27, 27, 'info@coastalescapes.co.za'),
	(28, 28, 'travel@nomadpathways.co.za'),
	(29, 29, 'enquiries@velvetvoyages.co.za'),
	(30, 30, 'info@peakadventures.co.za');

-- Dumping structure for table tripistry.destinations
CREATE TABLE IF NOT EXISTS `destinations` (
  `DestID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(100) NOT NULL,
  `City` varchar(100) NOT NULL,
  PRIMARY KEY (`DestID`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.destinations: ~0 rows (approximately)
REPLACE INTO `destinations` (`DestID`, `Name`, `City`) VALUES
	(1, 'Paris', 'Paris'),
	(2, 'Bali', 'Denpasar'),
	(3, 'Cape Town', 'Cape Town'),
	(4, 'Tokyo', 'Tokyo'),
	(5, 'New York', 'New York City'),
	(6, 'Dubai', 'Dubai'),
	(7, 'Rome', 'Rome'),
	(8, 'Bangkok', 'Bangkok'),
	(9, 'Sydney', 'Sydney'),
	(10, 'Johannesburg', 'Johannesburg'),
	(11, 'Marrakech', 'Marrakech'),
	(12, 'Barcelona', 'Barcelona'),
	(13, 'Nairobi', 'Nairobi'),
	(14, 'Singapore', 'Singapore'),
	(15, 'Istanbul', 'Istanbul'),
	(16, 'Zanzibar', 'Zanzibar City'),
	(17, 'Lisbon', 'Lisbon'),
	(18, 'Maldives', 'Male'),
	(19, 'Kruger Park', 'Phalaborwa'),
	(20, 'Santorini', 'Fira'),
	(21, 'Amsterdam', 'Amsterdam'),
	(22, 'Phuket', 'Phuket City'),
	(23, 'Victoria Falls', 'Livingstone'),
	(24, 'Serengeti', 'Arusha'),
	(25, 'Miami', 'Miami'),
	(26, 'Prague', 'Prague'),
	(27, 'Mauritius', 'Port Louis'),
	(28, 'Kyoto', 'Kyoto'),
	(29, 'Cairo', 'Cairo'),
	(30, 'Durban', 'Durban');

-- Dumping structure for table tripistry.discounts
CREATE TABLE IF NOT EXISTS `discounts` (
  `DiscountID` int(11) NOT NULL AUTO_INCREMENT,
  `PackID` int(11) NOT NULL,
  `From` date NOT NULL,
  `To` date NOT NULL,
  `Details` varchar(255) NOT NULL,
  PRIMARY KEY (`DiscountID`),
  KEY `fk_disc_pack` (`PackID`),
  CONSTRAINT `fk_disc_pack` FOREIGN KEY (`PackID`) REFERENCES `packages` (`PackID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_discount_dates` CHECK (`To` > `From`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.discounts: ~0 rows (approximately)
REPLACE INTO `discounts` (`DiscountID`, `PackID`, `From`, `To`, `Details`) VALUES
	(1, 1, '2026-06-01', '2026-06-30', '10% early bird discount for June bookings'),
	(2, 2, '2026-06-01', '2026-07-15', '15% off Bali package booked in June'),
	(3, 3, '2026-07-01', '2026-07-31', '10% summer discount on Tokyo package'),
	(4, 4, '2026-06-01', '2026-08-31', '20% off Cape Town Explorer for SA residents'),
	(5, 5, '2026-07-01', '2026-08-15', '5% off Dubai Luxury — limited spots'),
	(6, 6, '2026-08-01', '2026-09-30', '25% off Kruger Safari — off-peak special'),
	(7, 7, '2026-06-15', '2026-07-31', '30% weekend flash sale — Cape Town'),
	(8, 8, '2026-09-01', '2026-10-31', '10% off Zanzibar — shoulder season deal'),
	(9, 9, '2026-08-01', '2026-09-15', '15% off Marrakech autumn package'),
	(10, 10, '2026-07-01', '2026-08-31', '10% Singapore city break early booking'),
	(11, 11, '2026-09-01', '2026-10-15', '8% off Maldives — September special'),
	(12, 12, '2026-06-01', '2026-09-30', '20% off Joburg Explorer budget deal'),
	(13, 13, '2026-10-01', '2026-11-30', '15% off Durban — spring holiday deal'),
	(14, 14, '2026-10-01', '2026-11-15', '10% off Victoria Falls adventure package'),
	(15, 15, '2026-10-01', '2026-11-30', '12% off Serengeti — migration season'),
	(16, 16, '2026-11-01', '2026-12-15', '10% off NYC — winter festive season'),
	(17, 17, '2026-09-01', '2026-10-31', '15% off Barcelona — autumn travel deal'),
	(18, 18, '2026-10-01', '2026-11-30', '20% off Bangkok — value season special'),
	(19, 19, '2026-09-15', '2026-11-30', '35% flash sale — Cape Point day trip'),
	(20, 20, '2026-11-01', '2026-12-20', '10% off Mauritius — festive early bird'),
	(21, 21, '2026-09-01', '2026-10-31', '8% Santorini autumn sunset special'),
	(22, 22, '2026-10-01', '2026-11-30', '15% off Nairobi and Mara combo'),
	(23, 23, '2026-08-01', '2026-09-30', '12% off Rome summer package'),
	(24, 24, '2026-10-01', '2026-11-30', '18% off Phuket — shoulder season'),
	(25, 25, '2026-11-01', '2026-12-31', '5% off Maldives Platinum — festive booking'),
	(26, 26, '2026-09-01', '2026-10-31', '20% off Istanbul — autumn history tour'),
	(27, 27, '2026-10-01', '2026-11-30', '22% off Lisbon — autumn city break'),
	(28, 28, '2026-09-01', '2026-11-30', '30% off Kruger Budget — spring special'),
	(29, 29, '2026-11-01', '2026-12-31', '10% off Cairo — winter discovery deal'),
	(30, 30, '2026-02-01', '2026-03-31', '15% off Kyoto cherry blossom — advance booking');

-- Dumping structure for table tripistry.flights
CREATE TABLE IF NOT EXISTS `flights` (
  `FlightID` int(11) NOT NULL AUTO_INCREMENT,
  `PlaneID` int(11) NOT NULL,
  `DepPortID` int(11) NOT NULL,
  `ArrPortID` int(11) NOT NULL,
  `Class` varchar(20) NOT NULL DEFAULT 'Economy' CHECK (`Class` in ('Economy','Business','First')),
  `Type` varchar(20) NOT NULL DEFAULT 'Direct' CHECK (`Type` in ('Direct','Connecting')),
  `DepDateTime` datetime NOT NULL,
  `ArrDateTime` datetime NOT NULL,
  PRIMARY KEY (`FlightID`),
  KEY `fk_fl_plane` (`PlaneID`),
  KEY `fk_fl_dep` (`DepPortID`),
  KEY `fk_fl_arr` (`ArrPortID`),
  CONSTRAINT `fk_fl_arr` FOREIGN KEY (`ArrPortID`) REFERENCES `airports` (`PortID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_fl_dep` FOREIGN KEY (`DepPortID`) REFERENCES `airports` (`PortID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_fl_plane` FOREIGN KEY (`PlaneID`) REFERENCES `airplanes` (`PlaneID`) ON UPDATE CASCADE,
  CONSTRAINT `chk_flight_times` CHECK (`ArrDateTime` > `DepDateTime`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.flights: ~30 rows (approximately)
REPLACE INTO `flights` (`FlightID`, `PlaneID`, `DepPortID`, `ArrPortID`, `Class`, `Type`, `DepDateTime`, `ArrDateTime`) VALUES
	(1, 1, 10, 1, 'Economy', 'Direct', '2026-07-01 08:00:00', '2026-07-01 19:30:00'),
	(2, 2, 10, 2, 'Economy', 'Connecting', '2026-07-05 06:00:00', '2026-07-05 22:00:00'),
	(3, 3, 10, 3, 'Business', 'Direct', '2026-07-10 09:00:00', '2026-07-10 11:00:00'),
	(4, 4, 10, 4, 'Economy', 'Connecting', '2026-07-15 07:00:00', '2026-07-16 08:00:00'),
	(5, 5, 10, 5, 'First', 'Direct', '2026-07-20 10:00:00', '2026-07-20 22:00:00'),
	(6, 6, 10, 6, 'Business', 'Direct', '2026-08-01 06:30:00', '2026-08-01 14:00:00'),
	(7, 7, 10, 7, 'Economy', 'Connecting', '2026-08-05 08:00:00', '2026-08-05 20:30:00'),
	(8, 8, 10, 8, 'Economy', 'Connecting', '2026-08-10 07:00:00', '2026-08-10 23:00:00'),
	(9, 9, 3, 9, 'Business', 'Direct', '2026-08-15 09:00:00', '2026-08-16 05:00:00'),
	(10, 10, 3, 11, 'Economy', 'Direct', '2026-08-20 11:00:00', '2026-08-20 22:30:00'),
	(11, 11, 1, 12, 'Economy', 'Direct', '2026-09-01 07:30:00', '2026-09-01 09:00:00'),
	(12, 12, 5, 7, 'Business', 'Direct', '2026-09-05 10:00:00', '2026-09-05 22:00:00'),
	(13, 13, 6, 13, 'Economy', 'Direct', '2026-09-10 08:00:00', '2026-09-10 13:00:00'),
	(14, 14, 14, 4, 'First', 'Direct', '2026-09-15 09:00:00', '2026-09-15 16:00:00'),
	(15, 15, 15, 6, 'Business', 'Direct', '2026-09-20 06:00:00', '2026-09-20 09:30:00'),
	(16, 16, 10, 16, 'Economy', 'Connecting', '2026-10-01 07:00:00', '2026-10-01 16:00:00'),
	(17, 17, 17, 12, 'Economy', 'Direct', '2026-10-05 08:00:00', '2026-10-05 09:30:00'),
	(18, 18, 10, 18, 'Business', 'Connecting', '2026-10-10 06:00:00', '2026-10-10 20:00:00'),
	(19, 19, 10, 19, 'Economy', 'Direct', '2026-10-15 07:00:00', '2026-10-15 09:00:00'),
	(20, 20, 7, 20, 'Economy', 'Direct', '2026-10-20 10:00:00', '2026-10-20 12:30:00'),
	(21, 21, 21, 1, 'Economy', 'Direct', '2026-11-01 06:00:00', '2026-11-01 07:30:00'),
	(22, 22, 8, 22, 'Economy', 'Direct', '2026-11-05 09:00:00', '2026-11-05 11:30:00'),
	(23, 23, 10, 23, 'Economy', 'Direct', '2026-11-10 07:00:00', '2026-11-10 09:30:00'),
	(24, 24, 10, 24, 'Economy', 'Connecting', '2026-11-15 08:00:00', '2026-11-15 14:00:00'),
	(25, 25, 5, 25, 'Business', 'Direct', '2026-11-20 10:00:00', '2026-11-20 13:00:00'),
	(26, 26, 21, 26, 'Economy', 'Direct', '2026-12-01 07:00:00', '2026-12-01 08:30:00'),
	(27, 27, 10, 27, 'Business', 'Direct', '2026-12-05 08:00:00', '2026-12-05 14:00:00'),
	(28, 28, 4, 28, 'First', 'Direct', '2026-12-10 09:00:00', '2026-12-10 11:30:00'),
	(29, 29, 10, 29, 'Economy', 'Connecting', '2026-12-15 06:00:00', '2026-12-15 18:00:00'),
	(30, 30, 10, 30, 'Economy', 'Direct', '2026-12-20 07:00:00', '2026-12-20 09:00:00');

-- Dumping structure for table tripistry.group_discount
CREATE TABLE IF NOT EXISTS `group_discount` (
  `DiscountID` int(11) NOT NULL,
  `MinSize` int(11) NOT NULL DEFAULT 2,
  PRIMARY KEY (`DiscountID`),
  CONSTRAINT `fk_grp_disc` FOREIGN KEY (`DiscountID`) REFERENCES `discounts` (`DiscountID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.group_discount: ~15 rows (approximately)
REPLACE INTO `group_discount` (`DiscountID`, `MinSize`) VALUES
	(2, 4),
	(4, 3),
	(6, 5),
	(8, 4),
	(10, 3),
	(12, 4),
	(14, 3),
	(16, 5),
	(18, 4),
	(20, 3),
	(22, 4),
	(24, 3),
	(26, 5),
	(28, 4),
	(30, 3);

-- Dumping structure for table tripistry.holidays
CREATE TABLE IF NOT EXISTS `holidays` (
  `HolidayID` int(11) NOT NULL AUTO_INCREMENT,
  `ClientID` int(11) NOT NULL,
  `PackID` int(11) NOT NULL,
  `FlightID` int(11) NOT NULL,
  `From` date NOT NULL,
  `To` date NOT NULL,
  PRIMARY KEY (`HolidayID`),
  KEY `fk_hol_client` (`ClientID`),
  KEY `fk_hol_pack` (`PackID`),
  KEY `fk_hol_flight` (`FlightID`),
  CONSTRAINT `fk_hol_client` FOREIGN KEY (`ClientID`) REFERENCES `clients` (`ClientID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_hol_flight` FOREIGN KEY (`FlightID`) REFERENCES `flights` (`FlightID`) ON UPDATE CASCADE,
  CONSTRAINT `fk_hol_pack` FOREIGN KEY (`PackID`) REFERENCES `packages` (`PackID`) ON UPDATE CASCADE,
  CONSTRAINT `chk_holiday_dates` CHECK (`To` > `From`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.holidays: ~30 rows (approximately)
REPLACE INTO `holidays` (`HolidayID`, `ClientID`, `PackID`, `FlightID`, `From`, `To`) VALUES
	(1, 1, 1, 1, '2026-07-01', '2026-07-06'),
	(2, 2, 2, 2, '2026-07-05', '2026-07-12'),
	(3, 3, 4, 3, '2026-07-10', '2026-07-14'),
	(4, 4, 3, 4, '2026-07-15', '2026-07-21'),
	(5, 5, 6, 6, '2026-08-01', '2026-08-05'),
	(6, 6, 5, 6, '2026-08-01', '2026-08-06'),
	(7, 7, 7, 3, '2026-08-10', '2026-08-13'),
	(8, 8, 8, 16, '2026-10-01', '2026-10-07'),
	(9, 9, 9, 10, '2026-09-10', '2026-09-15'),
	(10, 10, 10, 14, '2026-09-15', '2026-09-19'),
	(11, 11, 11, 18, '2026-10-10', '2026-10-17'),
	(12, 12, 12, 10, '2026-10-01', '2026-10-04'),
	(13, 13, 13, 30, '2026-11-01', '2026-11-05'),
	(14, 14, 14, 23, '2026-11-15', '2026-11-20'),
	(15, 15, 15, 24, '2026-11-20', '2026-11-27'),
	(16, 16, 16, 5, '2026-12-01', '2026-12-07'),
	(17, 17, 17, 12, '2026-10-05', '2026-10-10'),
	(18, 18, 18, 8, '2026-11-10', '2026-11-14'),
	(19, 19, 19, 3, '2026-10-15', '2026-10-17'),
	(20, 20, 20, 27, '2026-12-05', '2026-12-12'),
	(21, 21, 21, 20, '2026-10-20', '2026-10-25'),
	(22, 22, 22, 13, '2026-11-22', '2026-11-28'),
	(23, 23, 23, 7, '2026-09-22', '2026-09-27'),
	(24, 24, 24, 22, '2026-11-25', '2026-12-01'),
	(25, 25, 25, 18, '2026-12-01', '2026-12-11'),
	(26, 26, 26, 15, '2026-10-27', '2026-11-01'),
	(27, 27, 27, 17, '2026-11-28', '2026-12-02'),
	(28, 28, 28, 19, '2026-10-29', '2026-11-01'),
	(29, 29, 29, 29, '2026-12-15', '2026-12-20'),
	(30, 30, 30, 28, '2026-04-01', '2026-04-07');

-- Dumping structure for table tripistry.individual_discount
CREATE TABLE IF NOT EXISTS `individual_discount` (
  `DiscountID` int(11) NOT NULL,
  `Limit` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`DiscountID`),
  CONSTRAINT `fk_ind_disc` FOREIGN KEY (`DiscountID`) REFERENCES `discounts` (`DiscountID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.individual_discount: ~15 rows (approximately)
REPLACE INTO `individual_discount` (`DiscountID`, `Limit`) VALUES
	(1, 1),
	(3, 1),
	(5, 2),
	(7, 1),
	(9, 1),
	(11, 1),
	(13, 2),
	(15, 1),
	(17, 1),
	(19, 1),
	(21, 1),
	(23, 2),
	(25, 1),
	(27, 1),
	(29, 1);

-- Dumping structure for table tripistry.itinerary
CREATE TABLE IF NOT EXISTS `itinerary` (
  `ItiID` int(11) NOT NULL AUTO_INCREMENT,
  `InfoID` int(11) NOT NULL,
  `Type` varchar(50) NOT NULL,
  `DateTime` datetime NOT NULL,
  `Activities` varchar(255) NOT NULL,
  `Description` text DEFAULT NULL,
  PRIMARY KEY (`ItiID`),
  KEY `fk_iti_info` (`InfoID`),
  CONSTRAINT `fk_iti_info` FOREIGN KEY (`InfoID`) REFERENCES `packinfo` (`InfoID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.itinerary: ~0 rows (approximately)
REPLACE INTO `itinerary` (`ItiID`, `InfoID`, `Type`, `DateTime`, `Activities`, `Description`) VALUES
	(1, 1, 'Tour', '2026-07-02 09:00:00', 'Eiffel Tower, Seine River Cruise', 'Guided morning tour of iconic Parisian landmarks.'),
	(2, 2, 'Leisure', '2026-07-06 10:00:00', 'Rice Terraces, Temple Visits', 'Explore Ubud rice paddies and local temples.'),
	(3, 3, 'Tour', '2026-07-16 09:00:00', 'Senso-ji, Shibuya Crossing', 'Full day guided cultural tour of central Tokyo.'),
	(4, 4, 'Tour', '2026-07-11 08:00:00', 'Table Mountain Cable Car, V&A Waterfront', 'Panoramic views and waterfront exploration.'),
	(5, 5, 'Tour', '2026-08-02 10:00:00', 'Burj Khalifa, Dubai Mall', 'Visit to the worlds tallest building and luxury shopping.'),
	(6, 6, 'Safari', '2026-08-21 05:30:00', 'Morning Game Drive, Bush Braai', 'Early morning Big Five game drive with bush breakfast.'),
	(7, 7, 'Leisure', '2026-08-22 09:00:00', 'Boulders Beach Penguins, Cape Point', 'Day trip along the Cape Peninsula.'),
	(8, 8, 'Leisure', '2026-10-02 08:00:00', 'Spice Market Tour, Dhow Cruise', 'Explore Stonetown spice markets and sunset dhow cruise.'),
	(9, 9, 'Tour', '2026-09-11 09:00:00', 'Medina, Souks, Majorelle Garden', 'Guided walk through ancient Marrakech Medina.'),
	(10, 10, 'Tour', '2026-09-16 10:00:00', 'Gardens by the Bay, Marina Bay', 'Iconic Singapore landmarks and light show.'),
	(11, 11, 'Leisure', '2026-10-11 08:00:00', 'Snorkelling, Sunset Dinner', 'Private overwater villa experience with marine activities.'),
	(12, 12, 'Tour', '2026-10-22 09:00:00', 'Soweto Tour, Gold Reef City', 'History and entertainment in Johannesburg.'),
	(13, 13, 'Leisure', '2026-11-01 09:00:00', 'uShaka Marine World, Golden Mile', 'Beach day and marine park visit.'),
	(14, 14, 'Tour', '2026-11-16 07:00:00', 'Victoria Falls Walk, Bungee Jump', 'Experience the worlds largest waterfall up close.'),
	(15, 15, 'Safari', '2026-11-21 05:30:00', 'Full Day Game Drive, Sundowner', 'Witness the Great Migration in the Serengeti.'),
	(16, 16, 'Tour', '2026-12-01 10:00:00', 'Central Park, Times Square, MoMA', 'Full day NYC highlights tour.'),
	(17, 17, 'Tour', '2026-10-06 09:00:00', 'Sagrada Familia, Park Guell', 'Gaudi architecture guided tour.'),
	(18, 18, 'Food', '2026-11-11 18:00:00', 'Chatuchak Night Market, Cooking Class', 'Street food and Thai cooking class.'),
	(19, 19, 'Leisure', '2026-10-16 07:00:00', 'Cape Point Lighthouse, Baboons', 'Scenic drive to the tip of the Cape Peninsula.'),
	(20, 20, 'Leisure', '2026-12-06 09:00:00', 'Blue Bay Beach, Chamarel Waterfalls', 'Tropical beach and island interior exploration.'),
	(21, 21, 'Leisure', '2026-10-21 17:00:00', 'Caldera Sunset, Wine Tasting', 'Watch the famous Santorini sunset with local wines.'),
	(22, 22, 'Safari', '2026-11-22 06:00:00', 'Maasai Mara Game Drive', 'Hot air balloon over the Maasai Mara at sunrise.'),
	(23, 23, 'Tour', '2026-09-23 09:00:00', 'Colosseum, Vatican, Trastevere', 'Two-day guided tour of ancient and modern Rome.'),
	(24, 24, 'Leisure', '2026-11-25 08:00:00', 'Phi Phi Islands, Snorkelling', 'Full day speedboat island hopping trip.'),
	(25, 25, 'Leisure', '2026-12-01 08:00:00', 'Private Island, Dolphin Watching', 'Exclusive overwater villa with private butler.'),
	(26, 26, 'Tour', '2026-10-27 09:00:00', 'Hagia Sophia, Grand Bazaar', 'Walk through 2000 years of Istanbul history.'),
	(27, 27, 'Tour', '2026-11-28 09:00:00', 'Sintra Palaces, Belem Tower', 'Day trip to fairytale Sintra and Lisbon landmarks.'),
	(28, 28, 'Safari', '2026-10-29 05:30:00', 'Morning Game Drive, Sunset Drive', 'Budget-friendly Kruger self-drive experience.'),
	(29, 29, 'Tour', '2026-12-16 07:00:00', 'Pyramids of Giza, Sphinx, Museum', 'Full day tour of ancient Egyptian wonders.'),
	(30, 30, 'Tour', '2026-04-01 08:00:00', 'Arashiyama Bamboo, Fushimi Inari', 'Kyoto cherry blossom season temple walk.');

-- Dumping structure for table tripistry.menu_items
CREATE TABLE IF NOT EXISTS `menu_items` (
  `ResID` int(11) NOT NULL,
  `Item` varchar(100) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`ResID`,`Item`),
  CONSTRAINT `fk_menu_res` FOREIGN KEY (`ResID`) REFERENCES `restaurants` (`ResID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.menu_items: ~0 rows (approximately)
REPLACE INTO `menu_items` (`ResID`, `Item`, `Price`) VALUES
	(1, 'Café Noisette', 55.00),
	(1, 'Croissant au Beurre', 85.00),
	(1, 'Croque Monsieur', 145.00),
	(2, 'Beetroot Carpaccio', 210.00),
	(2, 'Duck Confit', 320.00),
	(2, 'Lemongrass Sorbet', 95.00),
	(3, 'Cape Malay Lamb', 480.00),
	(3, 'Snoek Pate', 220.00),
	(3, 'Tasting Menu 7 Courses', 950.00),
	(4, 'Miso Soup', 95.00),
	(4, 'Omakase Sushi Set', 1800.00),
	(4, 'Tuna Nigiri', 350.00),
	(5, 'Black Cod Miso', 680.00),
	(5, 'Wagyu Beef Tataki', 780.00),
	(5, 'Yellowtail Sashimi', 520.00),
	(6, 'Branzino al Forno', 740.00),
	(6, 'Risotto al Tartufo', 620.00),
	(6, 'Tiramisu', 180.00),
	(7, 'Chocolate Coulant', 220.00),
	(7, 'Iberian Suckling Pig', 890.00),
	(7, 'Sea Urchin Toast', 450.00),
	(8, 'Lobster Bisque', 420.00),
	(8, 'Poached Halibut', 680.00),
	(8, 'Tasting Menu 5 Courses', 1200.00),
	(9, 'Mud Crab', 520.00),
	(9, 'Pavlova', 195.00),
	(9, 'Sydney Rock Oysters', 380.00),
	(10, 'Chocolate Fondant', 185.00),
	(10, 'Line Fish', 480.00),
	(10, 'Springbok Tartare', 380.00);

-- Dumping structure for table tripistry.packages
CREATE TABLE IF NOT EXISTS `packages` (
  `PackID` int(11) NOT NULL AUTO_INCREMENT,
  `AgentID` int(11) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`PackID`),
  KEY `fk_pack_agent` (`AgentID`),
  CONSTRAINT `fk_pack_agent` FOREIGN KEY (`AgentID`) REFERENCES `agencies` (`AgentID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.packages: ~30 rows (approximately)
REPLACE INTO `packages` (`PackID`, `AgentID`, `Price`) VALUES
	(1, 1, 28500.00),
	(2, 1, 19500.00),
	(3, 1, 35000.00),
	(4, 2, 22000.00),
	(5, 2, 41000.00),
	(6, 2, 15000.00),
	(7, 3, 9500.00),
	(8, 3, 18000.00),
	(9, 3, 26500.00),
	(10, 4, 32000.00),
	(11, 4, 55000.00),
	(12, 4, 14500.00),
	(13, 5, 8500.00),
	(14, 5, 22000.00),
	(15, 5, 38000.00),
	(16, 6, 48000.00),
	(17, 6, 31000.00),
	(18, 6, 19000.00),
	(19, 7, 12500.00),
	(20, 7, 29000.00),
	(21, 7, 44000.00),
	(22, 8, 17500.00),
	(23, 8, 36000.00),
	(24, 8, 27000.00),
	(25, 9, 62000.00),
	(26, 9, 23500.00),
	(27, 9, 18000.00),
	(28, 10, 9500.00),
	(29, 10, 33000.00),
	(30, 10, 51000.00);

-- Dumping structure for table tripistry.packinfo
CREATE TABLE IF NOT EXISTS `packinfo` (
  `InfoID` int(11) NOT NULL AUTO_INCREMENT,
  `PackID` int(11) NOT NULL,
  `Name` varchar(150) NOT NULL,
  `Destination` varchar(150) NOT NULL,
  `Duration` int(11) NOT NULL,
  `Class` varchar(20) NOT NULL DEFAULT 'Standard' CHECK (`Class` in ('Standard','Premium','Luxury')),
  PRIMARY KEY (`InfoID`),
  UNIQUE KEY `PackID` (`PackID`),
  CONSTRAINT `fk_pi_pack` FOREIGN KEY (`PackID`) REFERENCES `packages` (`PackID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.packinfo: ~0 rows (approximately)
REPLACE INTO `packinfo` (`InfoID`, `PackID`, `Name`, `Destination`, `Duration`, `Class`) VALUES
	(1, 1, 'Paris Romantic Escape', 'Paris, France', 5, 'Premium'),
	(2, 2, 'Bali Bliss Retreat', 'Bali, Indonesia', 7, 'Standard'),
	(3, 3, 'Tokyo Discovery', 'Tokyo, Japan', 6, 'Luxury'),
	(4, 4, 'Cape Town Explorer', 'Cape Town, South Africa', 4, 'Standard'),
	(5, 5, 'Dubai Luxury Break', 'Dubai, UAE', 5, 'Luxury'),
	(6, 6, 'Kruger Safari Adventure', 'Kruger Park, SA', 4, 'Standard'),
	(7, 7, 'Cape Town Weekend', 'Cape Town, South Africa', 3, 'Standard'),
	(8, 8, 'Zanzibar Beach Escape', 'Zanzibar, Tanzania', 6, 'Premium'),
	(9, 9, 'Marrakech Cultural Tour', 'Marrakech, Morocco', 5, 'Standard'),
	(10, 10, 'Singapore City Break', 'Singapore', 4, 'Premium'),
	(11, 11, 'Maldives Overwater Dream', 'Maldives', 7, 'Luxury'),
	(12, 12, 'Joburg City Explorer', 'Johannesburg, SA', 3, 'Standard'),
	(13, 13, 'Durban Beach Holiday', 'Durban, South Africa', 4, 'Standard'),
	(14, 14, 'Victoria Falls Adventure', 'Victoria Falls, Zambia', 5, 'Premium'),
	(15, 15, 'Serengeti Safari', 'Serengeti, Tanzania', 7, 'Luxury'),
	(16, 16, 'New York City Experience', 'New York, USA', 6, 'Luxury'),
	(17, 17, 'Barcelona Art and Culture', 'Barcelona, Spain', 5, 'Premium'),
	(18, 18, 'Bangkok Street Food Tour', 'Bangkok, Thailand', 4, 'Standard'),
	(19, 19, 'Cape Point Day Trip', 'Cape Town, South Africa', 2, 'Standard'),
	(20, 20, 'Mauritius Sun Holiday', 'Mauritius', 7, 'Premium'),
	(21, 21, 'Santorini Sunset Escape', 'Santorini, Greece', 5, 'Luxury'),
	(22, 22, 'Nairobi and Maasai Mara', 'Nairobi, Kenya', 6, 'Premium'),
	(23, 23, 'Rome and Vatican Tour', 'Rome, Italy', 5, 'Premium'),
	(24, 24, 'Phuket Island Hopping', 'Phuket, Thailand', 6, 'Standard'),
	(25, 25, 'Maldives Platinum Package', 'Maldives', 10, 'Luxury'),
	(26, 26, 'Istanbul History Walk', 'Istanbul, Turkey', 5, 'Standard'),
	(27, 27, 'Lisbon and Sintra Getaway', 'Lisbon, Portugal', 4, 'Standard'),
	(28, 28, 'Kruger Budget Safari', 'Kruger Park, SA', 3, 'Standard'),
	(29, 29, 'Cairo and Pyramids Tour', 'Cairo, Egypt', 5, 'Premium'),
	(30, 30, 'Kyoto Cherry Blossom Tour', 'Kyoto, Japan', 6, 'Luxury');

-- Dumping structure for table tripistry.restaurants
CREATE TABLE IF NOT EXISTS `restaurants` (
  `ResID` int(11) NOT NULL AUTO_INCREMENT,
  `TOID` int(11) NOT NULL,
  `TimeOpen` time NOT NULL,
  `TimeClose` time NOT NULL,
  PRIMARY KEY (`ResID`),
  UNIQUE KEY `TOID` (`TOID`),
  CONSTRAINT `fk_res_to` FOREIGN KEY (`TOID`) REFERENCES `tourism_offerings` (`TOID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.restaurants: ~10 rows (approximately)
REPLACE INTO `restaurants` (`ResID`, `TOID`, `TimeOpen`, `TimeClose`) VALUES
	(1, 21, '07:00:00', '23:00:00'),
	(2, 22, '11:00:00', '22:00:00'),
	(3, 23, '12:00:00', '22:30:00'),
	(4, 24, '11:30:00', '21:00:00'),
	(5, 25, '12:00:00', '23:30:00'),
	(6, 26, '19:00:00', '23:00:00'),
	(7, 27, '13:00:00', '22:30:00'),
	(8, 28, '12:00:00', '22:00:00'),
	(9, 29, '12:00:00', '22:00:00'),
	(10, 30, '12:00:00', '22:00:00');

-- Dumping structure for table tripistry.reviews
CREATE TABLE IF NOT EXISTS `reviews` (
  `RevID` int(11) NOT NULL AUTO_INCREMENT,
  `TravID` int(11) NOT NULL,
  `TOID` int(11) NOT NULL,
  `RevNum` int(11) NOT NULL,
  `Description` text NOT NULL,
  `Rating` tinyint(4) NOT NULL CHECK (`Rating` between 1 and 5),
  PRIMARY KEY (`RevID`),
  KEY `fk_rev_trav` (`TravID`),
  KEY `fk_rev_to` (`TOID`),
  CONSTRAINT `fk_rev_to` FOREIGN KEY (`TOID`) REFERENCES `tourism_offerings` (`TOID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rev_trav` FOREIGN KEY (`TravID`) REFERENCES `travellers` (`TravID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.reviews: ~30 rows (approximately)
REPLACE INTO `reviews` (`RevID`, `TravID`, `TOID`, `RevNum`, `Description`, `Rating`) VALUES
	(1, 1, 1, 1001, 'The Eiffel Tower at night is breathtaking. Absolutely worth every cent.', 5),
	(2, 2, 2, 1002, 'Tanah Lot temple was magical, especially at sunset. Highly recommend.', 5),
	(3, 3, 3, 1003, 'Table Mountain cable car views are unreal. Went twice during the trip.', 5),
	(4, 4, 4, 1004, 'Senso-ji is beautiful but very busy. Go early morning to avoid crowds.', 4),
	(5, 5, 5, 1005, 'Burj Khalifa viewing deck is stunning. Pricey but totally worth it.', 4),
	(6, 6, 6, 1006, 'The Colosseum exceeded expectations. Our guide was incredibly knowledgeable.', 5),
	(7, 7, 7, 1007, 'Jardin Majorelle is a peaceful oasis in the middle of busy Marrakech.', 4),
	(8, 8, 8, 1008, 'Sagrada Familia is mind-blowing architecture. Book tickets well in advance.', 5),
	(9, 9, 9, 1009, 'Serengeti was life-changing. Saw lions, elephants and a cheetah hunt.', 5),
	(10, 10, 10, 1010, 'The Pyramids of Giza are incredible in person. Much larger than expected.', 5),
	(11, 11, 11, 1011, 'Hotel Le Marais is perfectly located. Rooms are elegant and staff are warm.', 4),
	(12, 12, 12, 1012, 'Ubud Jungle Resort is paradise. Woke up to monkeys outside the window.', 5),
	(13, 13, 13, 1013, 'The Silo Hotel is one of the best hotels I have ever stayed at. Stunning.', 5),
	(14, 14, 14, 1014, 'Shinjuku Grand Hotel was clean and well located. Breakfast was excellent.', 4),
	(15, 15, 15, 1015, 'Atlantis The Palm is enormous fun. Kids loved the water park.', 4),
	(16, 16, 16, 1016, 'Hotel de Russie in Rome is elegant and the garden is gorgeous.', 5),
	(17, 17, 17, 1017, 'Marina Bay Sands infinity pool is as good as the photos. Spectacular.', 5),
	(18, 18, 18, 1018, 'Soneva Jani overwater villa was the most luxurious experience of our lives.', 5),
	(19, 19, 19, 1019, 'Lux Belle Mare is a beautiful resort with a fantastic beach.', 4),
	(20, 20, 20, 1020, 'Canaves Oia Suites has the most incredible caldera views in all of Santorini.', 5),
	(21, 1, 21, 1021, 'Cafe de Flore is a Parisian institution. The hot chocolate is legendary.', 4),
	(22, 2, 22, 1022, 'Locavore in Ubud is world class. Every dish was a masterpiece.', 5),
	(23, 3, 23, 1023, 'The Test Kitchen lived up to the hype. One of the best meals in Africa.', 5),
	(24, 4, 24, 1024, 'Sukiyabashi Jiro is expensive but an unforgettable culinary experience.', 5),
	(25, 5, 25, 1025, 'Nobu Dubai is consistently excellent. The black cod miso is legendary.', 4),
	(26, 6, 26, 1026, 'La Pergola in Rome has three Michelin stars and it shows. Exceptional.', 5),
	(27, 7, 27, 1027, 'El Celler de Can Roca is one of the best restaurants in the world. Perfect.', 5),
	(28, 8, 28, 1028, 'Le Bernardin is impeccable. The poached halibut is the best fish I have had.', 5),
	(29, 9, 29, 1029, 'Quay Restaurant has stunning harbour views and beautiful creative cuisine.', 4),
	(30, 10, 30, 1030, 'The Pot Luck Club is a Cape Town classic. Sharing plates are brilliant.', 4);

-- Dumping structure for table tripistry.rooms
CREATE TABLE IF NOT EXISTS `rooms` (
  `RoomNo` int(11) NOT NULL,
  `AccID` int(11) NOT NULL,
  `Bed` varchar(20) NOT NULL DEFAULT 'Double' CHECK (`Bed` in ('Single','Double','Twin','King','Suite')),
  `Bath` tinyint(1) NOT NULL DEFAULT 1,
  `Price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`RoomNo`,`AccID`),
  KEY `fk_room_acc` (`AccID`),
  CONSTRAINT `fk_room_acc` FOREIGN KEY (`AccID`) REFERENCES `accomodation` (`AccID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.rooms: ~0 rows (approximately)
REPLACE INTO `rooms` (`RoomNo`, `AccID`, `Bed`, `Bath`, `Price`) VALUES
	(101, 1, 'Double', 1, 3200.00),
	(102, 1, 'King', 1, 4500.00),
	(103, 1, 'Suite', 1, 8500.00),
	(201, 2, 'Double', 1, 2800.00),
	(202, 2, 'King', 1, 3900.00),
	(203, 2, 'Suite', 1, 6500.00),
	(301, 3, 'King', 1, 5500.00),
	(302, 3, 'Suite', 1, 9800.00),
	(303, 3, 'Double', 1, 3800.00),
	(401, 4, 'Single', 1, 1800.00),
	(402, 4, 'Double', 1, 2500.00),
	(403, 4, 'King', 1, 3800.00),
	(501, 5, 'King', 1, 6200.00),
	(502, 5, 'Suite', 1, 12000.00),
	(503, 5, 'Double', 1, 4200.00),
	(601, 6, 'Double', 1, 2900.00),
	(602, 6, 'King', 1, 4100.00),
	(603, 6, 'Suite', 1, 7500.00),
	(701, 7, 'King', 1, 8500.00),
	(702, 7, 'Suite', 1, 15000.00),
	(703, 7, 'Double', 1, 5500.00),
	(801, 8, 'King', 1, 11000.00),
	(802, 8, 'Suite', 1, 18500.00),
	(803, 8, 'Double', 1, 7500.00),
	(901, 9, 'Double', 1, 4800.00),
	(902, 9, 'King', 1, 6800.00),
	(903, 9, 'Suite', 1, 12500.00),
	(1001, 10, 'Double', 1, 9500.00),
	(1002, 10, 'King', 1, 13000.00),
	(1003, 10, 'Suite', 1, 22000.00);

-- Dumping structure for table tripistry.tourism_offerings
CREATE TABLE IF NOT EXISTS `tourism_offerings` (
  `TOID` int(11) NOT NULL AUTO_INCREMENT,
  `DestID` int(11) NOT NULL,
  `Name` varchar(150) NOT NULL,
  `City` varchar(100) NOT NULL,
  `Type` varchar(20) NOT NULL CHECK (`Type` in ('ATTRACTION','ACCOMMODATION','RESTAURANT')),
  PRIMARY KEY (`TOID`),
  KEY `fk_to_dest` (`DestID`),
  CONSTRAINT `fk_to_dest` FOREIGN KEY (`DestID`) REFERENCES `destinations` (`DestID`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.tourism_offerings: ~0 rows (approximately)
REPLACE INTO `tourism_offerings` (`TOID`, `DestID`, `Name`, `City`, `Type`) VALUES
	(1, 1, 'Eiffel Tower', 'Paris', 'ATTRACTION'),
	(2, 2, 'Tanah Lot Temple', 'Tabanan', 'ATTRACTION'),
	(3, 3, 'Table Mountain', 'Cape Town', 'ATTRACTION'),
	(4, 4, 'Senso-ji Temple', 'Tokyo', 'ATTRACTION'),
	(5, 6, 'Burj Khalifa', 'Dubai', 'ATTRACTION'),
	(6, 7, 'Colosseum', 'Rome', 'ATTRACTION'),
	(7, 11, 'Jardin Majorelle', 'Marrakech', 'ATTRACTION'),
	(8, 12, 'Sagrada Familia', 'Barcelona', 'ATTRACTION'),
	(9, 24, 'Serengeti National Park', 'Arusha', 'ATTRACTION'),
	(10, 29, 'Pyramids of Giza', 'Cairo', 'ATTRACTION'),
	(11, 1, 'Hotel Le Marais', 'Paris', 'ACCOMMODATION'),
	(12, 2, 'Ubud Jungle Resort', 'Ubud', 'ACCOMMODATION'),
	(13, 3, 'The Silo Hotel', 'Cape Town', 'ACCOMMODATION'),
	(14, 4, 'Shinjuku Grand Hotel', 'Tokyo', 'ACCOMMODATION'),
	(15, 6, 'Atlantis The Palm', 'Dubai', 'ACCOMMODATION'),
	(16, 7, 'Hotel de Russie', 'Rome', 'ACCOMMODATION'),
	(17, 14, 'Marina Bay Sands', 'Singapore', 'ACCOMMODATION'),
	(18, 18, 'Soneva Jani', 'Male', 'ACCOMMODATION'),
	(19, 27, 'Lux Belle Mare', 'Port Louis', 'ACCOMMODATION'),
	(20, 20, 'Canaves Oia Suites', 'Santorini', 'ACCOMMODATION'),
	(21, 1, 'Cafe de Flore', 'Paris', 'RESTAURANT'),
	(22, 2, 'Locavore', 'Ubud', 'RESTAURANT'),
	(23, 3, 'The Test Kitchen', 'Cape Town', 'RESTAURANT'),
	(24, 4, 'Sukiyabashi Jiro', 'Tokyo', 'RESTAURANT'),
	(25, 6, 'Nobu Dubai', 'Dubai', 'RESTAURANT'),
	(26, 7, 'La Pergola', 'Rome', 'RESTAURANT'),
	(27, 12, 'El Celler de Can Roca', 'Barcelona', 'RESTAURANT'),
	(28, 5, 'Le Bernardin', 'New York City', 'RESTAURANT'),
	(29, 9, 'Quay Restaurant', 'Sydney', 'RESTAURANT'),
	(30, 10, 'The Pot Luck Club', 'Johannesburg', 'RESTAURANT');

-- Dumping structure for table tripistry.travellers
CREATE TABLE IF NOT EXISTS `travellers` (
  `TravID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) NOT NULL,
  `DoB` date NOT NULL,
  PRIMARY KEY (`TravID`),
  UNIQUE KEY `UserID` (`UserID`),
  CONSTRAINT `fk_trav_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.travellers: ~0 rows (approximately)
REPLACE INTO `travellers` (`TravID`, `UserID`, `DoB`) VALUES
	(1, 1, '1995-03-14'),
	(2, 2, '1998-07-22'),
	(3, 3, '1990-11-05'),
	(4, 4, '2000-01-30'),
	(5, 5, '1993-06-18'),
	(6, 6, '1997-09-09'),
	(7, 7, '1985-12-25'),
	(8, 8, '2001-04-03'),
	(9, 9, '1992-08-17'),
	(10, 10, '1996-02-28'),
	(11, 11, '1999-10-11'),
	(12, 12, '1988-05-20'),
	(13, 13, '2002-03-07'),
	(14, 14, '1994-07-14'),
	(15, 15, '1991-11-23'),
	(16, 16, '2003-01-09'),
	(17, 17, '1987-06-30'),
	(18, 18, '1995-09-16'),
	(19, 19, '2000-12-04'),
	(20, 20, '1989-04-27');

-- Dumping structure for table tripistry.users
CREATE TABLE IF NOT EXISTS `users` (
  `UserID` int(11) NOT NULL AUTO_INCREMENT,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `Username` (`Username`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table tripistry.users: ~0 rows (approximately)
REPLACE INTO `users` (`UserID`, `Username`, `Password`) VALUES
	(1, 'james_olivier', '$2y$10$abc1hashedpassword1'),
	(2, 'priya_naidoo', '$2y$10$abc2hashedpassword2'),
	(3, 'luca_ferrari', '$2y$10$abc3hashedpassword3'),
	(4, 'amara_diallo', '$2y$10$abc4hashedpassword4'),
	(5, 'ethan_brooks', '$2y$10$abc5hashedpassword5'),
	(6, 'sofia_martins', '$2y$10$abc6hashedpassword6'),
	(7, 'kwame_asante', '$2y$10$abc7hashedpassword7'),
	(8, 'nina_petrov', '$2y$10$abc8hashedpassword8'),
	(9, 'diego_reyes', '$2y$10$abc9hashedpassword9'),
	(10, 'yuki_tanaka', '$2y$10$abc10hashedpassword'),
	(11, 'chloe_dupont', '$2y$10$abc11hashedpassword'),
	(12, 'marco_rossi', '$2y$10$abc12hashedpassword'),
	(13, 'fatima_hassan', '$2y$10$abc13hashedpassword'),
	(14, 'ryan_mitchell', '$2y$10$abc14hashedpassword'),
	(15, 'aisha_kamara', '$2y$10$abc15hashedpassword'),
	(16, 'tom_vandenberg', '$2y$10$abc16hashedpassword'),
	(17, 'mei_chen', '$2y$10$abc17hashedpassword'),
	(18, 'oliver_james', '$2y$10$abc18hashedpassword'),
	(19, 'sara_kowalski', '$2y$10$abc19hashedpassword'),
	(20, 'ben_okafor', '$2y$10$abc20hashedpassword'),
	(21, 'suntrail_agency', '$2y$10$ag1hashedpassword1'),
	(22, 'horizons_travel', '$2y$10$ag2hashedpassword2'),
	(23, 'wandersa_tours', '$2y$10$ag3hashedpassword3'),
	(24, 'globetreks_sa', '$2y$10$ag4hashedpassword4'),
	(25, 'africanroots_exp', '$2y$10$ag5hashedpassword5'),
	(26, 'skybound_travel', '$2y$10$ag6hashedpassword6'),
	(27, 'coastal_escapes', '$2y$10$ag7hashedpassword7'),
	(28, 'nomad_pathways', '$2y$10$ag8hashedpassword8'),
	(29, 'velvet_voyages', '$2y$10$ag9hashedpassword9'),
	(30, 'peak_adventures', '$2y$10$ag10hashedpasswrd');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
