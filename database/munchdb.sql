-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 04, 2026 at 02:11 AM
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
-- Database: `munchdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `CARTID` varchar(255) NOT NULL,
  `CARTDATE` date NOT NULL,
  `CARTSTATUS` varchar(20) NOT NULL,
  `CUSTUSERNAME` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cartmenu`
--

CREATE TABLE `cartmenu` (
  `CARTID` varchar(255) NOT NULL,
  `MENUID` varchar(255) NOT NULL,
  `QUANTITY` int(20) NOT NULL,
  `SUBTOTAL` decimal(6,2) NOT NULL,
  `REQUEST` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `CUSTUSERNAME` varchar(255) NOT NULL,
  `CUSTNAME` varchar(255) NOT NULL,
  `CUSTPASSWORD` varchar(255) NOT NULL,
  `PHONENO` varchar(15) DEFAULT NULL,
  `CUSTEMAIL` varchar(255) DEFAULT NULL,
  `EMAILVERIFIED` tinyint(1) NOT NULL DEFAULT 0,
  `EMAIL_VERIFY_TOKEN` varchar(64) DEFAULT NULL,
  `EMAIL_VERIFY_EXPIRES` datetime DEFAULT NULL,
  `CUSTIMAGE` varchar(255) DEFAULT 'img/user-1.png',
  `CUSTPROFILEPIC` varchar(255) DEFAULT 'img/user-1.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`CUSTUSERNAME`, `CUSTNAME`, `CUSTPASSWORD`, `PHONENO`, `CUSTIMAGE`, `CUSTPROFILEPIC`, `CUSTEMAIL`, `EMAILVERIFIED`, `EMAIL_VERIFY_TOKEN`, `EMAIL_VERIFY_EXPIRES`) VALUES
('001', 'Rar', '', '0172000000', 'img/user-1.png', 'img/user-1.png', NULL, 1, NULL, NULL),
('aisyah04', 'Aisyah Noor', 'aisyahpass', '0135566778', 'img/user-1.png', 'img/user-1.png', NULL, 1, NULL, NULL),
('ali01', 'Ali Hassan', 'pass123', '0123456789', 'img/user-1.png', 'img/user-1.png', NULL, 1, NULL, NULL),
('argh', 'ARGHHHHH', '$2y$10$YqRxPHwQsMJ91C8RM.Xpcu8W6g.XV3L8vGh3LTfpNdwvNguInCg42', '01222222222', 'img/user-1.png', 'img/user-1.png', NULL, 1, NULL, NULL),
('dan03', 'Daniel Tan', 'danny99', '0198877665', 'img/user-1.png', 'img/user-1.png', NULL, 1, NULL, NULL),
('gae', 'gae', '$2y$10$L4KviO8sMn8Tz18/Qm0EMe0kkE7q3Kj63aqYYS6VyOuO0i/ruXJTK', '0122222222', 'img/user-1.png', 'uploads/profile/gae_1780465880.png', NULL, 1, NULL, NULL),
('john05', 'John Lee', 'johnsecure', '0141122334', 'img/user-1.png', 'img/user-1.png', NULL, 1, NULL, NULL),
('Khai1', 'Khai', 'Khai1', '0172088887', 'img/user-1.png', 'img/user-1.png', NULL, 1, NULL, NULL),
('memeCat', 'Cipi capa', '$2y$10$HKriHqDxQHHD2oD2H5TnDus7FD2a/AQPNcD2zTPOfxv4pJVC9sBaO', '01222222222', 'img/user-1.png', 'uploads/profile/memeCat_1780444116.webp', NULL, 1, NULL, NULL),
('rawr', 'gae', '$2y$10$y4AJVUkKUy9TE302ZxZ33.R9oNEv7wiIi55bk4c9oCufQYowRy.7q', '01222222222', 'img/user-1.png', 'img/user-1.png', NULL, 1, NULL, NULL),
('sara02', 'Sara Lim', 'sara2025', '0112233445', 'img/user-1.png', 'img/user-1.png', NULL, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `MENUID` varchar(255) NOT NULL,
  `MENUNAME` varchar(50) NOT NULL,
  `MENUPRICE` decimal(10,2) NOT NULL,
  `MENUCATEGORY` varchar(30) NOT NULL,
  `MENUDESC` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`MENUID`, `MENUNAME`, `MENUPRICE`, `MENUCATEGORY`, `MENUDESC`) VALUES
('M001', 'Chicken Rice', 10.00, 'main', 'Fragrant rice served with tender poached chicken and sauces'),
('M002', 'Nasi Kandar', 12.00, 'main', 'Rice served with mixed curries and rich gravies'),
('M003', 'Teh Tarik', 3.00, 'drinks', 'Classic Malaysian pulled milk tea'),
('M004', 'Roti Canai', 3.00, 'side', 'Flaky flatbread served with dhal or curry'),
('M005', 'Fried Rice', 8.00, 'main', 'Stir-fried rice with egg, veggies, and seasoning'),
('M006', 'Nasi Goreng Kampung', 9.00, 'main', 'Classic Malay fried rice with anchovies and chili'),
('M007', 'Ayam Rendang', 12.00, 'main', 'Slow-cooked chicken in rich coconut spicy gravy'),
('M008', 'Mee Goreng Mamak', 8.00, 'main', 'Stir-fried noodles with egg, tofu, and spices'),
('M009', 'Nasi Lemak Ayam Goreng', 10.00, 'main', 'Coconut rice with fried chicken and sambal'),
('M010', 'Kuey Teow Kung Fu', 9.00, 'main', 'Fried noodles with egg gravy and vegetables'),
('M011', 'Nasi Ayam Hainan', 10.00, 'main', 'Fragrant rice with steamed chicken and sauces'),
('M012', 'Maggi Goreng Special', 7.00, 'main', 'Instant noodles fried with egg and veggies'),
('M013', 'Sirap Limau', 1.50, 'drinks', 'Classic Sirap with Limau'),
('M014', 'Kopi O', 3.00, 'drinks', 'Strong black coffee with sugar'),
('M015', 'Milo Ais', 4.00, 'drinks', 'Chilled chocolate malt drink'),
('M016', 'Lemon Tea', 4.00, 'drinks', 'Refreshing iced lemon tea'),
('M017', 'Sirap Bandung', 5.00, 'drinks', 'Rose syrup milk drink'),
('M018', 'Iced White Coffee', 5.00, 'drinks', 'Chilled aromatic white coffee'),
('M019', 'Barley Ice', 3.00, 'drinks', 'Cooling barley drink'),
('M020', 'Fried Chicken Wing', 6.00, 'side', 'Crispy marinated wings'),
('M021', 'French Fries', 5.00, 'side', 'Golden crispy fries'),
('M022', 'Chicken Nugget', 6.00, 'side', 'Bite-sized crispy chicken'),
('M023', 'Onion Rings', 6.00, 'side', 'Crunchy fried onion rings'),
('M024', 'Keropok Lekor', 5.00, 'side', 'Traditional fish sausage snack'),
('M025', 'Hash Brown', 5.00, 'side', 'Crispy shredded potato patty'),
('M026', 'Garlic Bread', 5.00, 'side', 'Toasted bread with garlic butter'),
('M027', 'Stir-Fried Kangkung', 6.00, 'veggies', 'Water spinach with garlic and chili'),
('M028', 'Mixed Vegetable Salad', 7.00, 'veggies', 'Fresh greens with dressing'),
('M029', 'Broccoli Oyster Sauce', 8.00, 'veggies', 'Steamed broccoli in savory sauce'),
('M030', 'Sambal Petai', 9.00, 'veggies', 'Spicy petai beans with sambal'),
('M031', 'Cabbage Stir Fry', 6.00, 'veggies', 'Simple cabbage with soy sauce'),
('M032', 'Carrot & Corn Medley', 7.00, 'veggies', 'Sweet stir-fried vegetables'),
('M033', 'Spinach Garlic Stir Fry', 6.00, 'veggies', 'Fresh spinach with garlic aroma'),
('M034', 'Chicken Chop', 13.00, 'western', 'Grilled chicken with black pepper sauce'),
('M035', 'Fish and Chips', 14.00, 'western', 'Crispy fried fish with fries'),
('M036', 'Beef Steak', 22.00, 'western', 'Juicy grilled beef steak'),
('M037', 'Spaghetti Bolognese', 13.00, 'western', 'Pasta with meat tomato sauce'),
('M038', 'Cheeseburger', 11.00, 'western', 'Beef burger with cheese'),
('M039', 'Grilled Lamb Chop', 24.00, 'western', 'Tender lamb with mint sauce'),
('M040', 'Carbonara Pasta', 14.00, 'western', 'Creamy pasta with bacon and sauce'),
('M041', 'Chocolate Brownie', 8.90, 'Dessert', 'Rich chocolate brownie served warm with a soft fudgy texture.'),
('M042', 'Caramel Pudding', 6.90, 'Dessert', 'Smooth and creamy caramel pudding topped with sweet caramel sauce.'),
('M043', 'Ice Cream Sundae', 7.90, 'Dessert', 'Classic ice cream sundae served with chocolate syrup and toppings.'),
('M044', 'Mango Sticky Rice', 9.90, 'Dessert', 'Sweet mango served with sticky rice and creamy coconut sauce.'),
('M045', 'Cheese Cake Slice', 10.90, 'Dessert', 'Creamy cheesecake slice with a smooth and rich cheese flavour.'),
('M046', 'Extra Cheese', 2.00, 'Addons', 'Additional cheese topping for selected meals.'),
('M047', 'Extra Sauce', 1.50, 'Addons', 'Extra sauce portion to enhance the meal flavour.'),
('M048', 'Extra Rice', 2.50, 'Addons', 'Additional serving of rice for main dishes.'),
('M049', 'Fried Egg', 2.00, 'Addons', 'Freshly fried egg added as an extra topping.'),
('M050', 'Extra Sambal', 1.50, 'Addons', 'Extra spicy sambal for customers who prefer stronger flavour.');

-- --------------------------------------------------------

--
-- Table structure for table `operationalstaff`
--

CREATE TABLE `operationalstaff` (
  `STAFFID` varchar(255) NOT NULL,
  `WORKSTATION` varchar(10) NOT NULL,
  `SKILLEVEL` int(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `operationalstaff`
--

INSERT INTO `operationalstaff` (`STAFFID`, `WORKSTATION`, `SKILLEVEL`) VALUES
('S002', 'Counter', 0),
('S004', 'Kitchen', 0),
('S005', 'Cashier', 0);

-- --------------------------------------------------------

--
-- Table structure for table `ordermenu`
--

CREATE TABLE `ordermenu` (
  `ORDERID` varchar(255) NOT NULL,
  `MENUID` varchar(255) NOT NULL,
  `QUANTITY` int(10) NOT NULL,
  `SUBTOTAL` decimal(10,2) NOT NULL,
  `REQUEST` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ordermenu`
--

INSERT INTO `ordermenu` (`ORDERID`, `MENUID`, `QUANTITY`, `SUBTOTAL`, `REQUEST`) VALUES
('O001', 'M002', 2, 18.00, 'Extra gravy'),
('O002', 'M001', 1, 8.00, 'No veg'),
('O003', 'M003', 3, 8.00, 'Less sugar'),
('O004', 'M005', 2, 12.00, 'Extra crispy'),
('O005', 'M004', 4, 6.00, 'Cut into halves'),
('O10926', 'M047', 1, 1.50, NULL),
('O56177', 'M023', 1, 6.00, NULL),
('O66614', 'M005', 2, 16.00, NULL),
('O95873', 'M002', 2, 24.00, NULL),
('O95873', 'M005', 1, 8.00, NULL),
('O95873', 'M020', 1, 6.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `ORDERID` varchar(255) NOT NULL,
  `ORDERDATE` date NOT NULL,
  `ORDERTYPE` varchar(15) NOT NULL,
  `TABLENO` varchar(10) DEFAULT NULL,
  `ORDERREMARK` varchar(255) DEFAULT NULL,
  `ORDERSTATUS` varchar(15) NOT NULL,
  `CUSTID` varchar(255) NOT NULL,
  `Address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`ORDERID`, `ORDERDATE`, `ORDERTYPE`, `TABLENO`, `ORDERREMARK`, `ORDERSTATUS`, `CUSTID`, `Address`) VALUES
('O001', '2026-05-01', 'Dine-in', '5', 'Extra spicy', 'Completed', 'ali01', NULL),
('O002', '2026-05-02', 'Takeaway', NULL, 'No onions', 'Served', 'sara02', NULL),
('O003', '2026-05-02', 'Dine-in', '3', 'Less rice', 'Completed', 'dan03', NULL),
('O004', '2026-05-03', 'Delivery', NULL, 'Ring doorbell', 'On the way', 'aisyah04', NULL),
('O005', '2026-05-03', 'Dine-in', '2', 'No chili', 'Served', 'john05', NULL),
('O10926', '2026-06-03', 'Dine In', NULL, NULL, 'Served', 'gae', NULL),
('O56177', '2026-06-03', 'Dine In', NULL, NULL, 'Served', 'memeCat', NULL),
('O66614', '2026-06-03', 'Dine In', NULL, NULL, 'Served', 'memeCat', NULL),
('O95873', '2026-06-03', 'Delivery', NULL, NULL, 'Served', 'memeCat', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `owner`
--

CREATE TABLE `owner` (
  `STAFFID` varchar(255) NOT NULL,
  `EQUITYTYPE` varchar(20) NOT NULL,
  `CONTRACTDURATION` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `owner`
--

INSERT INTO `owner` (`STAFFID`, `EQUITYTYPE`, `CONTRACTDURATION`) VALUES
('S001', 'Full Equity', 5),
('S003', 'Partnership', 3);

-- --------------------------------------------------------

--
-- Table structure for table `reservation`
--

CREATE TABLE `reservation` (
  `RESERVEID` int(11) NOT NULL,
  `RESERVEDATE` date NOT NULL,
  `SESSION` varchar(20) NOT NULL,
  `TIMESLOT` varchar(20) NOT NULL,
  `SEATINGPREF` varchar(50) NOT NULL,
  `guestCount` int(3) NOT NULL,
  `FULLNAME` varchar(50) NOT NULL,
  `PHONENO` varchar(15) NOT NULL,
  `EMAIL` varchar(30) NOT NULL,
  `OCCASION` varchar(50) DEFAULT NULL,
  `SPECIALREQ` varchar(255) DEFAULT NULL,
  `DEPOSIT` decimal(10,2) NOT NULL,
  `PAYMENTMETHOD` varchar(30) NOT NULL,
  `STATUS` varchar(20) NOT NULL,
  `CUSTID` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation`
--

INSERT INTO `reservation` (`RESERVEID`, `RESERVEDATE`, `SESSION`, `TIMESLOT`, `SEATINGPREF`, `guestCount`, `FULLNAME`, `PHONENO`, `EMAIL`, `OCCASION`, `SPECIALREQ`, `DEPOSIT`, `PAYMENTMETHOD`, `STATUS`, `CUSTID`) VALUES
(5, '2026-06-06', 'Lunch', '11:00 AM', 'Indoor Area', 2, 'Khai RAWR', '0172087874', 'annaakaanimelover@gmail.com', 'Birthday', 'grgegwve', 20.00, 'E-Wallet', 'Confirmed', 'gae'),
(6, '2026-06-24', 'Lunch', '6:00 PM', 'Indoor Area', 6, '3rfgrr', '0172087874', 'annaakaanimelover@gmail.com', 'Family Gathering', 'vrkjvwivEOVU', 60.00, 'Online Banking', 'Confirmed', 'rawr'),
(7, '2026-06-09', 'Catering', '14:09', 'Event Catering', 80, 'Khai RAWR', '0172087874', 'annaakaanimelover@gmail.com', 'Company Event', 'Event Location: KNWON3 ONO\nSetup Time: 11:00\nRice: Nasi Putih\nMain Dish: Ayam Goreng Berempah\nSide Dish: Kobis Goreng, Sayur Campur\nDrink: Sirap Ais\nSpecial Request: rie3n1ci3ucrn3i', 100.00, 'E-Wallet', 'Confirmed', 'gae'),
(8, '2026-06-03', 'Lunch', '11:00 AM', 'Indoor Area', 3, 'Ana Khairun Nisa', '0172087874', 'annaakaanimelover@gmail.com', 'Birthday', 'gvdgdgdc', 30.00, 'Online Banking', 'Confirmed', 'memeCat');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `SALESID` varchar(255) NOT NULL,
  `SALESDATE` date NOT NULL DEFAULT current_timestamp(),
  `SALESTOTAL` decimal(10,2) NOT NULL,
  `SALESPAYMETHOD` varchar(10) NOT NULL,
  `ORDERID` varchar(255) NOT NULL,
  `STAFFID` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`SALESID`, `SALESDATE`, `SALESTOTAL`, `SALESPAYMETHOD`, `ORDERID`, `STAFFID`) VALUES
('S65018', '2026-06-03', 40.28, 'Online Ban', 'O95873', 'S001'),
('S71322', '2026-06-03', 1.59, 'Cash at Co', 'O10926', 'S001'),
('S72793', '2026-06-03', 6.36, 'Cash at Co', 'O56177', 'S001'),
('S76435', '2026-06-03', 16.96, 'Cash at Co', 'O66614', 'S001'),
('SA001', '2026-05-01', 18.00, 'Cash', 'O001', 'S002'),
('SA002', '2026-05-02', 8.00, 'Card', 'O002', 'S004'),
('SA003', '2026-05-02', 8.00, 'E-Wallet', 'O003', 'S002'),
('SA004', '2026-05-03', 12.00, 'Cash', 'O004', 'S005'),
('SA005', '2026-05-03', 6.00, 'Card', 'O005', 'S004');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `STAFFID` varchar(255) NOT NULL,
  `STAFFNAME` varchar(255) NOT NULL,
  `STAFFPHONENO` varchar(10) NOT NULL,
  `STAFFPASS` varchar(255) NOT NULL,
  `STAFFROLE` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`STAFFID`, `STAFFNAME`, `STAFFPHONENO`, `STAFFPASS`, `STAFFROLE`) VALUES
('S001', 'Farid Malik', '0121112233', 'staff123', 'OWNER'),
('S002', 'Nur Aina', '0132223344', 'ops123', 'OPERATIONAL'),
('S003', 'Hafiz Rahman', '0143334455', 'staff321', 'OWNER'),
('S004', 'Siti Aminah', '0114445566', 'ops456', 'OPERATIONAL'),
('S005', 'Kumar Raj', '0195556677', 'staff777', 'OPERATIONAL'),
('S009', 'KHAI', '0122222222', '$2y$10$KiFpbN4XpEGuvB.H46j2leIvXPYdfqpEj//5v.st1sskhcpOhvH7O', 'OPERATIONAL');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `PAYMENTID` varchar(30) NOT NULL,
  `RELATEDTYPE` varchar(20) NOT NULL,
  `RELATEDID` varchar(255) NOT NULL,
  `CUSTID` varchar(255) DEFAULT NULL,
  `PAYEREMAIL` varchar(255) DEFAULT NULL,
  `AMOUNT` decimal(10,2) NOT NULL,
  `PAYMENTMETHOD` varchar(40) NOT NULL,
  `PAYMENTSTATUS` varchar(20) NOT NULL DEFAULT 'Pending',
  `TRANSACTIONNO` varchar(40) DEFAULT NULL,
  `CREATED_AT` datetime NOT NULL DEFAULT current_timestamp(),
  `PAID_AT` datetime DEFAULT NULL,
  PRIMARY KEY (`PAYMENTID`),
  KEY `related_lookup` (`RELATEDTYPE`,`RELATEDID`),
  KEY `cust_lookup` (`CUSTID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`CARTID`),
  ADD KEY `CUSTUSERNAME` (`CUSTUSERNAME`),
  ADD KEY `CUSTUSERNAME_2` (`CUSTUSERNAME`);

--
-- Indexes for table `cartmenu`
--
ALTER TABLE `cartmenu`
  ADD KEY `CARTID` (`CARTID`,`MENUID`),
  ADD KEY `MENUID` (`MENUID`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`CUSTUSERNAME`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`MENUID`);

--
-- Indexes for table `operationalstaff`
--
ALTER TABLE `operationalstaff`
  ADD PRIMARY KEY (`STAFFID`),
  ADD KEY `STAFFID` (`STAFFID`);

--
-- Indexes for table `ordermenu`
--
ALTER TABLE `ordermenu`
  ADD PRIMARY KEY (`ORDERID`,`MENUID`),
  ADD KEY `ORDERID` (`ORDERID`,`MENUID`),
  ADD KEY `MENUID` (`MENUID`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`ORDERID`),
  ADD KEY `CUSTID` (`CUSTID`);

--
-- Indexes for table `owner`
--
ALTER TABLE `owner`
  ADD PRIMARY KEY (`STAFFID`),
  ADD KEY `STAFFID` (`STAFFID`);

--
-- Indexes for table `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`RESERVEID`),
  ADD KEY `CUSTID` (`CUSTID`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`SALESID`),
  ADD KEY `ORDERID` (`ORDERID`,`STAFFID`),
  ADD KEY `STAFFID` (`STAFFID`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`STAFFID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `reservation`
--
ALTER TABLE `reservation`
  MODIFY `RESERVEID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`CUSTUSERNAME`) REFERENCES `customer` (`CUSTUSERNAME`);

--
-- Constraints for table `cartmenu`
--
ALTER TABLE `cartmenu`
  ADD CONSTRAINT `cartmenu_ibfk_1` FOREIGN KEY (`CARTID`) REFERENCES `cart` (`CARTID`),
  ADD CONSTRAINT `cartmenu_ibfk_2` FOREIGN KEY (`MENUID`) REFERENCES `menu` (`MENUID`);

--
-- Constraints for table `operationalstaff`
--
ALTER TABLE `operationalstaff`
  ADD CONSTRAINT `operationalstaff_ibfk_1` FOREIGN KEY (`STAFFID`) REFERENCES `staff` (`STAFFID`);

--
-- Constraints for table `ordermenu`
--
ALTER TABLE `ordermenu`
  ADD CONSTRAINT `ordermenu_ibfk_1` FOREIGN KEY (`ORDERID`) REFERENCES `orders` (`ORDERID`),
  ADD CONSTRAINT `ordermenu_ibfk_2` FOREIGN KEY (`MENUID`) REFERENCES `menu` (`MENUID`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`CUSTID`) REFERENCES `customer` (`CUSTUSERNAME`);

--
-- Constraints for table `owner`
--
ALTER TABLE `owner`
  ADD CONSTRAINT `owner_ibfk_1` FOREIGN KEY (`STAFFID`) REFERENCES `staff` (`STAFFID`);

--
-- Constraints for table `reservation`
--
ALTER TABLE `reservation`
  ADD CONSTRAINT `reservation_ibfk_1` FOREIGN KEY (`CUSTID`) REFERENCES `customer` (`CUSTUSERNAME`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`ORDERID`) REFERENCES `orders` (`ORDERID`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`STAFFID`) REFERENCES `staff` (`STAFFID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
