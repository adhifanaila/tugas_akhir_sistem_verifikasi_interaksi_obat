-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 29, 2026 at 10:45 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: interaction_cheker
--

--
-- Dumping data for table admins
--

INSERT INTO admins (id, user_id, name, email) VALUES
(1, 2, 'Adinda Ace', 'adindaace@gmail.com'),
(2, 5, 'Ditya Pratama', 'dityapratama58@gmail.com');

--
-- Dumping data for table doctors
--

INSERT INTO doctors (id, user_id, name, nip, str_number, phone, email) VALUES
(1, 1, 'Matin Azka Adzkiya', NULL, NULL, NULL, 'matinazka@gmail.com'),
(2, 4, 'Dinda Siti Fathonah', NULL, NULL, NULL, 'dindasiti@gmail.com'),
(3, 6, 'Anisa Nadzira', NULL, NULL, NULL, 'anadzira94@itb.ac.id'),
(4, 8, 'Hana Athiyah', '20279837682004', '123', '081234567890', 'hanaathiyah@gmail.com');

--
-- Dumping data for table patients
--

INSERT INTO patients (id, name, tanggal_lahir, usia_pasien, alamat, diagnosis_pasien) VALUES
(1, 'Ramadhan', '2002-08-04', '22 tahun -1 bulan', 'Jl. Kubangsari No.18 ', 'Pusing dan Demam '),
(2, 'Adinda Siti F', '2004-01-02', '21', 'Jl Djuanda No.40', 'Mual dan Mag'),
(3, 'Jasmine C', '2003-08-02', '21', 'Jl. Kemerdekaan Timur No.10', '-'),
(4, 'Muthi Eunike Harianto', '2004-02-11', '21', 'Jl Djuanda No.73', 'resep dummy aja cek'),
(5, 'Budi', '2005-03-12', '20', 'Jl. Kemerdekaan Timur No.109', 'Hipertensi dan Nyeri Sendi'),
(6, 'Siti Rohmah', '1999-02-10', '26 tahun 5 bulan', 'Jl. Kemerdekaan Timur No.1099', 'Diabetes Mellitus Tipe 2 dan Nyeri Sendi\r\n'),
(7, 'Budi H', '1991-01-01', '34 tahun 6 bulan', 'Jl Djuanda No.20', 'Hipertensi dan Infeksi Saluran Pernapasan\r\n'),
(8, 'Jasmine Naila', '2004-02-11', '21 tahun 5 bulan', 'Jl. Kemerdekaan Timur No.10', '-'),
(9, 'Budi', '2003-02-01', '22 tahun 5 bulan', 'Jl Djuanda No.39', '-'),
(10, 'ditya', '1999-07-25', '26 tahun 0 bulan', 'Jl Kemerdekaan Barat No.67', 'fever'),
(11, 'ike', '1997-02-11', '28', 'jln ganesa', 'irritable bowel syndrome'),
(12, 'yohannes', '2001-03-08', '24 tahun 4 bulan', 'jl cisitu', 'ISPA'),
(13, 'Syakira Maulida Naila', '2003-02-11', '22 tahun 5 bulan', 'Jl. Mekar No.35 Solo', 'pusing'),
(14, 'Muthi Eunike Dinda', '1999-07-29', '26', 'Jl Djuanda No.39', 'Resep untuk pasien yang mengalami infeksi yang memerlukan antibiotik, disertai dengan nyeri atau demam ringan hingga sedang. Paracetamol disertakan sebagai pilihan tambahan jika Ibuprofen tidak cukup meredakan gejala.'),
(15, 'Jasmine Naila', '2003-03-12', '22 tahun 4 bulan', 'Jl Djuanda No.50', 'Demam ringan'),
(16, 'Muthi Eunike Harianto', '2006-01-11', '19', 'Jl Djuanda No.76', 'Radang tenggorokan'),
(17, 'Syakira Maulida', '2003-01-11', '22', 'Jl Djuanda No.40', 'sdfsgh'),
(18, 'Gigih Sulaiman', '2000-06-06', '25 tahun 2 bulan', 'Jl. Soekarno Hatta No.19', 'Hipertensi, Varicella Zoster'),
(19, 'Jasmine Naila Lala', '2003-02-11', '22 tahun 6 bulan', 'Jl Djuanda No.50', 'Batuk'),
(20, 'Naila Muthi Syakira', '2003-04-11', '22', 'Jl Kemerdekaan Timur No. 17 Sumedang', 'Muntah dan pusing'),
(21, 'Syakira Maulida', '2025-08-20', '-1', 'Jl Djuanda No.39', 'Pusing'),
(22, 'Budi', '2004-02-11', '21 tahun 6 bulan', 'Jl Djuanda No.39', 'pusing'),
(23, 'Jasmine Naila', '2004-03-12', '21', 'Jl Djuanda No.50', 'Mual'),
(24, 'Barbie', '2004-02-12', '21 tahun 6 bulan', 'Jl. Kemerdekaan Timur No.8', 'Mual'),
(25, 'barbie', '2004-02-10', '21 tahun 6 bulan', 'Jl Djuanda No.50', 'Mual'),
(26, 'Maulida', '2003-02-11', '22', 'Jl Kemerdekaan Barat No.69', 'Resep untuk pasien yang mengalami infeksi yang memerlukan antibiotik, disertai dengan nyeri atau demam ringan hingga sedang. Paracetamol disertakan sebagai pilihan tambahan jika Ibuprofen tidak cukup meredakan gejala. '),
(27, 'Jasmine Naila Muthi', '2003-04-11', '22 tahun 4 bulan', 'Jl Djuanda No.40', 'Pusing dan demam'),
(28, 'Muthi Eunike Harianto', '2004-07-17', '21 tahun 1 bulan', 'Jl Djuanda No.39', 'Demam'),
(29, 'Naila', '2007-06-13', '18 tahun 2 bulan', 'Jl Djuanda No.45', 'pusing'),
(30, 'Jasmine Naila', '2005-02-11', '20 tahun 6 bulan', 'Jl Kemerdekaan Barat No.90', 'Demam'),
(31, 'Jasmine Naila', '1900-03-12', '125 tahun 5 bulan', 'Jl Djuanda No.30', 'Pusing'),
(32, 'Jasmine Naila', '2000-03-10', '25 tahun 5 bulan', 'Jl Djuanda No.50', 'pusing'),
(33, 'Naila', '2003-12-10', '21 tahun 8 bulan', 'Jl Djuanda No.39', 'Muaal'),
(34, 'Muthi Eunike Harianto k', '2005-03-12', '20 tahun 5 bulan', 'Jl Djuanda No.40', 'mual'),
(35, 'raisya', '2000-04-12', '25', 'Jl Djuanda No.40', 'Mual');

--
-- Dumping data for table pharmacists
--

INSERT INTO pharmacists (id, user_id, name, phone, email) VALUES
(1, 3, 'Marwa Fellah Syakira', NULL, 'marwafella@gmail.com'),
(2, 7, 'Aulin Pranatalina', '', 'aulin@itb.ac.id'),
(3, 9, 'Qotrunnada Surya Balqis', NULL, 'qotrun@gmail.com'),
(4, 10, 'Faza', NULL, 'faza@gmail.com'),
(5, 11, 'Atha Araminta', NULL, 'athaaraminta@gmail.com'),
(6, 12, 'Qatrunnada Surya Balqis', '', 'naya@gmail.com');

--
-- Dumping data for table prescriptions
--

INSERT INTO prescriptions (id, doctor_id, patient_id, created_at, catatan) VALUES
(1, 1, 1, '2025-07-24', '-'),
(2, 1, 2, '2025-07-24', '-'),
(3, 2, 3, '2025-07-24', '-'),
(11, 3, 11, '2025-07-25', ''),
(12, 3, 12, '2025-07-25', ''),
(13, 1, 13, '2025-07-28', '-'),
(14, 1, 13, '2025-07-28', '-'),
(15, 3, 14, '2025-07-30', '-'),
(16, 3, 15, '2025-07-30', 'Diresepkan untuk 5 hari'),
(17, 1, 16, '2025-08-03', ''),
(18, 4, 17, '2025-08-03', ''),
(19, 4, 18, '2025-08-03', 'Minum paracetamol apabila demam'),
(20, 1, 19, '2025-08-03', ''),
(21, 1, 20, '2025-08-11', '-'),
(22, 3, 21, '2025-08-12', ''),
(23, 3, 22, '2025-08-12', ''),
(24, 1, 23, '2025-08-12', ''),
(25, 3, 24, '2025-08-12', ''),
(26, 3, 25, '2025-08-12', ''),
(27, 1, 26, '2025-08-12', '-'),
(28, 1, 27, '2025-08-17', ''),
(29, 1, 28, '2025-08-17', ''),
(30, 1, 23, '2025-08-18', ''),
(31, 1, 29, '2025-08-17', ''),
(32, 1, 30, '2025-08-17', ''),
(33, 1, 31, '2025-08-17', ''),
(34, 1, 32, '2025-08-17', ''),
(35, 1, 33, '2025-08-17', ''),
(36, 1, 34, '2025-08-17', ''),
(37, 1, 35, '2025-08-18', '');

--
-- Dumping data for table prescription_items
--

INSERT INTO prescription_items (id, prescription_id, drug_id, dosage, usage_instruction) VALUES
(1, 1, 4189, '3 x sehari', 'Setelah makan'),
(2, 1, 1730, '1 x sehari', 'setelah makan'),
(3, 1, 1, '3 x sehari', 'Sebelum makan'),
(4, 1, 25, '3 x sehari', 'setelah makan'),
(6, 2, 181, '3 x sehari', 'setelah makan'),
(7, 2, 4189, '2 x sehari', 'setelah makan'),
(11, 2, 1, '3 x sehari', 'Sebelum makan'),
(12, 2, 4189, '2 x sehari', 'Setelah makan'),
(13, 3, 1730, '2 x sehari', 'sebelum makan'),
(15, 3, 4189, '1 x sehari', 'Setelah makan'),
(16, 3, 25, '2 x sehari', 'Setelah makan'),
(46, 11, 3815, '2x1', 'PO setelah makan '),
(48, 11, 25, '3x1', 'PO setelah makan '),
(49, 12, 3650, '3x1', 'setelah makan'),
(50, 12, 413, '2x1', 'PO setelah makan '),
(51, 12, 1247, '2x1', 'Sebelum makan'),
(52, 13, 4189, '3 x sehari', 'setelah makan'),
(53, 13, 3815, '1 x sehari', 'setelah makan'),
(54, 13, 181, '2 x sehari', 'Sebelum makan'),
(55, 14, 3815, '3 x sehari', 'setelah makan'),
(56, 14, 181, '3 x sehari', 'setelah makan'),
(57, 14, 4189, '3 x sehari', 'setelah makan'),
(58, 14, 533, '1 x sehari', 'setelah makan'),
(59, 15, 4189, '3 x sehari', 'Setelah makan'),
(60, 15, 1730, '1 x sehari', 'Setelah makan'),
(62, 16, 4189, '3 x sehari', 'setelah makan'),
(63, 16, 6530, '1 x sehari', 'setelah makan'),
(64, 17, 4189, '3 x sehari', 'Setelah makan'),
(65, 17, 179, '3 x sehari', 'setelah makan'),
(69, 18, 4189, '3 x sehari', 'Setelah makan'),
(71, 18, 181, '2x1', 'Setelah makan'),
(72, 19, 74, '1 x sehari', 'Setelah makan'),
(73, 19, 4414, '5 x sehari', 'Setelah makan'),
(74, 19, 25, '2 x sehari', 'Setelah makan'),
(75, 19, 5750, '2 x sehari', 'Dioles di atas gelembung setelah mandi'),
(76, 20, 4189, '3 x sehari', 'Sebelum makan'),
(77, 20, 2, '1 x sehari', 'Setelah makan'),
(78, 20, 1, '1 x sehari', 'Setelah makan'),
(79, 21, 1, '2 x sehari', 'Setelah makan'),
(80, 21, 25, '1 x sehari', 'setelah makan'),
(81, 22, 1, '1 x sehari', 'setelah makan'),
(83, 22, 6702, '3 x sehari', 'Setelah makan'),
(84, 23, 1, '3 x sehari', 'sebelum makan'),
(85, 23, 4, '3 x sehari', 'Setelah makan'),
(86, 24, 2, '2 x sehari', 'sebelum makan'),
(87, 24, 7, '1 Tablet 1 x sehari', 'PO setelah makan '),
(89, 25, 10, '3x1', 'sebelum makan'),
(90, 25, 29, '2 x sehari', 'setelah makan'),
(91, 26, 3, '3x1', 'Sebelum makan'),
(92, 26, 3, '2 x sehari', 'Setelah makan'),
(93, 26, 4, '2x1', 'Setelah makan'),
(94, 27, 3815, '3 x sehari', 'Setelah makan'),
(96, 27, 4189, '3x1', 'setelah makan'),
(97, 27, 1730, '1 x sehari', 'Sebelum makan'),
(98, 28, 1, '2x1', 'Sebelum makan'),
(99, 28, 4189, '3x1', 'Setelah makan'),
(100, 28, 181, '2x1', 'Setelah makan'),
(101, 29, 4189, '3 x sehari', 'Setelah makan'),
(102, 29, 1, '2 x sehari', 'setelah makan'),
(103, 30, 181, '2x1', 'PO setelah makan '),
(104, 30, 5, '2x1', 'Setelah makan'),
(105, 31, 1, '2 x sehari', 'Sebelum makan'),
(106, 31, 181, '1 x sehari', 'Sebelum makan'),
(107, 31, 6, '3 x sehari', 'Setelah makan'),
(108, 32, 1, '3 x sehari', 'Sebelum makan'),
(109, 32, 181, '2 x sehari', 'setelah makan'),
(110, 33, 1, '2 x sehari', 'Setelah makan'),
(111, 33, 181, '2 x sehari', 'Setelah makan'),
(112, 34, 3, '2x1', 'Setelah makan'),
(113, 34, 356, '2x1', 'Sebelum makan'),
(114, 35, 1, '3 x sehari', 'Sebelum makan'),
(115, 35, 181, '1 x sehari', 'setelah makan'),
(116, 36, 1, '1 x sehari', 'sebelum makan'),
(117, 36, 181, '2 x sehari', 'Setelah makan'),
(120, 37, 181, '2 x sehari', 'Sebelum makan'),
(121, 37, 1730, '1 x sehari', 'setelah makan');

--
-- Dumping data for table roles
--

INSERT INTO roles (id, role_name) VALUES
(1, 'admin'),
(2, 'dokter'),
(3, 'apoteker');

--
-- Dumping data for table users
--

INSERT INTO users (id, username, password, role_id) VALUES
(1, 'matinazka', '$2y$10$SMovqEHjDtI89.EdXvz6meKx0vGvu37bHAu6lilj5EJb0HxSBmKiu', 2),
(2, 'dindaace', '$2y$10$YyIFngTK1aLqb9EIB.gyVuJZ5EBFpYUq5w3RzrDBdj.BB7YmTQhQS', 1),
(3, 'marwasyakira', '$2y$10$WPqDeAoH3jO1I74VS8vYV.ypFD7W1SoIxk.V2UfX7Cq9bRXoc6Okq', 3),
(4, 'dindasiti', '$2y$10$RzOX35W96AdgSZIntOVhH.ijzqVf1JYzONnKAG6pZaEz8PdSa3hAi', 2),
(5, 'dityapratama58', '$2y$10$THA605Ttpv2Ix1qY6kUKt.OcyRIle8y26BatEBb43sOzs8sPAgXYK', 1),
(6, 'anisa', '$2y$10$AmuYSNgt1X4dTRfS924gVeX6YLa/v7oQEfZSNJz6SXRVHXyY4ktEO', 2),
(7, 'aulin', '$2y$10$cVzHfKcFM65G84JU6pY3Zej7Mz3cWo8ykrMR/19QD2FcZfln7BkX.', 3),
(8, 'hana', '$2y$10$qsGLyj5sjN5Bc09ub8uXj.DCA27Qg3/SQTxLCFDtuqAVJXoyMun/u', 2),
(9, 'qotrun', '$2y$10$dJIy0cUhzWs00pD2zENDlePImhTHj2qH6iqoINHj51VpW.Vst2rla', 3),
(10, 'faza', '$2y$10$Aj0TDXTg7dIzJnYvaCuzM..VdmXLPD/37Bor1XtiILP0vAjAejC92', 3),
(11, 'atha', '$2y$10$/4hLnLe5imrioufVZT8Zc.Sya8wzGJKYbmSeRcfKCH9p2wT3r90Xa', 3),
(12, 'qotrunnaya', '$2y$10$uNvwNVBPm7lYwCGu8oVkSeFlHwPj8ECzsS2XjtMG9/qXD6lgjnO5u', 3);
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
