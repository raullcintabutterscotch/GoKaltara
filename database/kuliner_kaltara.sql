-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 05, 2026 at 05:00 PM
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
-- Database: `kuliner_kaltara`
--

-- --------------------------------------------------------

--
-- Table structure for table `favorit`
--

CREATE TABLE `favorit` (
  `id_favorit` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_kuliner` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favorit`
--

INSERT INTO `favorit` (`id_favorit`, `id_user`, `id_kuliner`, `created_at`) VALUES
(8, 2, 11, '2026-10-05 11:52:09'),
(9, 1, 11, '2026-10-05 11:57:39');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`) VALUES
(1, 'cemilan'),
(2, 'Makanan Pokok'),
(3, 'Lauk Pauk'),
(4, 'Minuman');

-- --------------------------------------------------------

--
-- Table structure for table `komentar`
--

CREATE TABLE `komentar` (
  `id_komentar` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_kuliner` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `komentar` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `komentar`
--

INSERT INTO `komentar` (`id_komentar`, `id_user`, `id_kuliner`, `parent_id`, `komentar`, `created_at`) VALUES
(12, 2, 10, NULL, 'hai', '2026-10-05 11:46:45'),
(13, 2, 11, NULL, 'hai', '2026-10-05 14:14:26');

-- --------------------------------------------------------

--
-- Table structure for table `komentar_like`
--

CREATE TABLE `komentar_like` (
  `id_like` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_komentar` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `komentar_like`
--

INSERT INTO `komentar_like` (`id_like`, `id_user`, `id_komentar`, `created_at`) VALUES
(4, 1, 13, '2026-10-05 14:14:33');

-- --------------------------------------------------------

--
-- Table structure for table `kuliner`
--

CREATE TABLE `kuliner` (
  `id_kuliner` int(11) NOT NULL,
  `nama_kuliner` varchar(100) NOT NULL,
  `id_kategori` int(11) NOT NULL,
  `asal_daerah` varchar(100) NOT NULL,
  `bahan_utama` text NOT NULL,
  `deskripsi` text NOT NULL,
  `cara_penyajian` text NOT NULL,
  `sejarah` text NOT NULL,
  `foto` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kuliner`
--

INSERT INTO `kuliner` (`id_kuliner`, `nama_kuliner`, `id_kategori`, `asal_daerah`, `bahan_utama`, `deskripsi`, `cara_penyajian`, `sejarah`, `foto`) VALUES
(1, 'Nasi Subut', 2, 'Kabupaten Tana Tidung, Provinsi Kalimantan Utara', 'Beras putih, jagung manis pipil, dan ubi jalar ungu.', 'Nasi Subut dimasak dengan mencampurkan beras, potongan atau parutan ubi jalar ungu, dan jagung manis. Pigmen dari ubi jalar memberikan warna ungu merata pada nasi tanpa menggunakan pewarna buatan. Hasil akhirnya adalah nasi dengan tekstur pulen dan cita rasa gurih yang berpadu dengan rasa manis alami dari ubi dan jagung.', 'Hidangan ini umumnya disajikan hangat bersama lauk-pauk pesisir berprotein tinggi. Pendamping tradisional yang paling sering disandingkan dengan Nasi Subut adalah Sate Ikan Pari, udang, kerang, dan taburan serundeng kelapa. Kombinasi sambal pedas dan lauk gurih ini berfungsi menyeimbangkan karakter nasi yang sedikit manis.', 'Nasi Subut merupakan warisan kearifan lokal Suku Tidung dalam mengelola ketahanan pangan. Di masa lampau, masyarakat mengombinasikan beras dengan hasil panen perkebunan seperti ubi jalar dan jagung untuk memperkaya porsi dan gizi makanan pokok. Saat ini, hidangan ini bertransformasi dari sekadar menu rumahan menjadi simbol pelestarian budaya kuliner khas Kalimantan Utara yang sering ditampilkan dalam acara adat, festival gastronomi, dan menjadi daya tarik pariwisata daerah.', 'kuliner_1791162495_7634312a.webp'),
(2, 'Tudai', 3, 'Bulungan, Provinsi Kalimantan Utara', 'Kerang darah bercangkang tebal (Tudai), bumbu merah, atau bumbu kuning', 'Kerang berdaging sangat tebal khas perairan Kaltara yang ditumis dengan rempah pedas manis.', 'Disajikan sebagai lauk pendamping Nasi Subut atau nasi putih.', 'Menjadi hidangan kehormatan yang wajib ada dalam pesta pernikahan atau acara adat Suku Bulungan.', 'kuliner_1791163746_e8f302b5.webp'),
(3, 'Lawa', 3, 'Bulungan, Provinsi Kalimantan Utara', 'Mentimun atau rumput laut segar, udang rebus/kerang, dan kelapa sangrai tumbuk.', 'Sejenis salad segar khas Kaltara. Teksturnya renyah dari mentimun atau rumput laut, dipadukan dengan rasa gurih dari kelapa sangrai dan kaldu udang, serta sentuhan rasa asam segar.', 'Dihidangkan dingin atau pada suhu ruang sebagai lauk penyegar atau makanan pembuka.', 'Makanan peninggalan Kesultanan Bulungan yang dahulunya sering disajikan untuk menjamu tamu-tamu penting kerajaan.', 'kuliner_1791164052_89e8bbfe.webp'),
(4, 'Kepiting Soka', 3, 'Tarakan, Provinsi Kalimantan Utara', 'Kepiting cangkang lunak (soka), bumbu lada hitam, telur asin, atau asam manis.', 'Kepiting utuh yang cangkangnya sangat lunak sehingga seluruh bagian tubuhnya bisa dikunyah dan dimakan tanpa perlu dikupas.', 'Umumnya digoreng tepung hingga krispi lalu disiram saus telur asin atau saus asam manis.', 'Tarakan merupakan salah satu pusat budidaya kepiting bakau terbaik di Indonesia. Kepiting Soka menjadi ikon kuliner pesisir yang mendorong perekonomian nelayan lokal.', 'kuliner_1791164107_1c04cb5a.webp'),
(5, 'Sate Ikan Pari', 3, 'Kabupaten Tana Tidung & Tarakan, Provinsi Kalimantan Utara', 'Daging ikan pari, jeruk nipis, bumbu kecap manis, dan rempah oles.', 'Potongan dadu daging ikan pari yang lembut dan bebas duri keras, dibakar dengan olesan bumbu kecap yang terkaramelisasi.', 'Disajikan dengan bumbu kecap cabai rawit dan irisan bawang merah. Pasangan paling sempurna untuk Nasi Subut.', 'Memanfaatkan hasil tangkapan laut pesisir utara Kalimantan yang melimpah, sate ikan pari adalah cara masyarakat pesisir menikmati hidangan laut dengan teknik pembakaran khas Nusantara.', 'kuliner_1791164228_e5e00793.webp'),
(6, 'Tumis Kapah', 3, 'Tarakan, Provinsi Kalimantan Utara', 'Kerang kapah, bawang merah, bawang putih, cabai, jeruk nipis, jahe, garam', 'Tumis kapah merupakan olahan kerang khas Tarakan dengan tekstur daging kenyal dan rasa gurih, pedas, serta sedikit asam.', 'Kapah dibersihkan lalu ditumis bersama bumbu hingga matang. Biasanya disajikan dengan nasi hangat dan sambal jeruk nipis.', 'Kapah menjadi salah satu kuliner laut yang dikenal di pesisir Tarakan. Pantai Amal menjadi salah satu lokasi yang terkenal dengan sajian kapah.', 'kuliner_1791166420_66a2157d.webp'),
(7, 'Ikan Asin Richa', 3, 'Tarakan, Provinsi Kalimantan Utara', 'Ikan asin, cabai merah, cabai rawit, bawang merah, daun jeruk, daun kemangi, gula, garam', 'Ikan asin richa atau ikan asin gami merupakan olahan ikan asin dengan bumbu pedas dan aromatik.', 'Ikan asin dimasak bersama tumisan cabai, bawang, daun jeruk, dan kemangi sampai bumbu meresap. Disajikan sebagai lauk bersama nasi panas.', 'Hidangan ini disebut sebagai olahan tradisional suku Tidung di Tarakan dan juga menjadi salah satu produk pangan lokal yang dikenal masyarakat.', 'kuliner_1791167500_ad032f74.webp'),
(8, 'Sate Temburung', 3, 'Tarakan, Provinsi Kalimantan Utara', 'Temburung atau kerang laut, rempah-rempah, jeruk nipis', 'Sate Temburung merupakan olahan kerang dengan cita rasa gurih, berbumbu, dan segar dari jeruk nipis.', 'Temburung dibersihkan, dibumbui dengan rempah, ditusuk seperti sate, lalu dimasak hingga matang. Sate disajikan bersama bumbu dan jeruk nipis.', 'Sate Temburung merupakan kuliner khas Tarakan dan tercatat sebagai makanan yang biasa disajikan saat pergantian tahun.', 'kuliner_1791167842_8256e765.webp'),
(9, 'Luba Laya', 2, 'Krayan, Nunukan, Provinsi Kalimantan Utara', 'Beras Adan Krayan, daun itip, air', 'Luba laya merupakan makanan pokok tradisional Dayak Lundayeh yang bentuknya mirip lontong, tetapi memiliki tekstur lebih lembut.', 'Beras dimasak hingga lembut lalu dibungkus menggunakan daun itip dan dimasak sampai matang. Biasanya dibawa sebagai bekal atau dimakan bersama lauk.', 'Luba laya diwariskan turun-temurun oleh masyarakat Dayak Lundayeh. Dahulu makanan ini juga sering dibawa sebagai bekal ketika pergi ke ladang atau hutan. Penggunaan beras Adan dan daun itip menjadi bagian dari kekhasan pangan lokal Krayan.', 'kuliner_1791167994_f254f468.webp'),
(10, 'Kue Rangai', 1, 'Malinau', 'Tepung beras, gula pasir, kelapa parut', 'Rangai merupakan kue tradisional suku Tidung dengan rasa manis dan gurih dari perpaduan gula serta kelapa.', 'Bahan dicampur menjadi adonan lalu diolah hingga menjadi kue yang kering dan tahan lama. Kue disajikan sebagai camilan atau bekal.', 'Rangai sudah dikonsumsi masyarakat Tidung sejak dahulu. Pada masa lalu, kue ini digunakan sebagai bekal tahan lama bagi petani dan pemburu yang bekerja jauh dari rumah.', 'kuliner_1791168221_811459f4.webp'),
(11, 'Kopi Malinau', 4, 'Malinau, Provinsi Kalimantan Utara', 'Biji kopi robusta lokal Malinau.', 'Kopi jenis robusta dengan tingkat keasaman rendah (low acidity), body yang tebal, dan profil rasa yang cenderung memiliki nuansa earthy (rempah atau kayu) khas tanah Kalimantan.', 'Diseduh secara tradisional (kopi tubruk) dengan atau tanpa gula merah.', 'Perkebunan kopi di kawasan Malinau dikelola oleh petani lokal di dataran tinggi, menjadi simbol komoditas agroforestri yang menggerakkan ekonomi masyarakat pedalaman tanpa merusak hutan rimbun Kaltara.', 'kuliner_1791168539_301e9013.webp');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notifikasi` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_pengirim` int(11) NOT NULL,
  `id_kuliner` int(11) NOT NULL,
  `id_komentar` int(11) NOT NULL,
  `pesan` varchar(255) NOT NULL,
  `dibaca` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id_reset` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expired_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id_reset`, `username`, `token`, `expired_at`, `created_at`) VALUES
(3, 'admin', '690320d99fd305b8914bf164d4e69a6e8eebeb6e9c9374dc00687ea8cbf37122', '2026-09-30 04:33:30', '2026-09-30 02:03:30');

-- --------------------------------------------------------

--
-- Table structure for table `rating`
--

CREATE TABLE `rating` (
  `id_rating` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_kuliner` int(11) NOT NULL,
  `nilai` tinyint(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `level` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `username`, `password`, `nama_lengkap`, `foto_profil`, `level`) VALUES
(1, 'admin', '$2y$10$5PXv6wpu8ttEP61hOavQmeUKpp5rfGj4T8XpnnhMqdh/8srxo8gCu', 'Muhammad Raul Zia Parsa', 'profil_1_20260930092654_8f25155e.webp', 'admin'),
(2, 'pincup', '$2y$10$xlNsR3rZRkxiduEX4ODjKumEJR4F2NZjS7F.11LId7K/KoFdx8jsi', 'dafin', 'profil_2_1791140916.webp', 'user');

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id_token` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `selector` varchar(64) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `last_used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_token`),
  UNIQUE KEY `unique_selector` (`selector`),
  KEY `fk_remember_user` (`id_user`),
  KEY `idx_remember_expires` (`expires_at`),
  CONSTRAINT `fk_remember_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `favorit`
--
ALTER TABLE `favorit`
  ADD PRIMARY KEY (`id_favorit`),
  ADD UNIQUE KEY `unique_favorit` (`id_user`,`id_kuliner`),
  ADD KEY `fk_favorit_kuliner` (`id_kuliner`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indexes for table `komentar`
--
ALTER TABLE `komentar`
  ADD PRIMARY KEY (`id_komentar`),
  ADD KEY `fk_komentar_user` (`id_user`),
  ADD KEY `fk_komentar_kuliner` (`id_kuliner`),
  ADD KEY `idx_komentar_parent` (`parent_id`);

--
-- Indexes for table `komentar_like`
--
ALTER TABLE `komentar_like`
  ADD PRIMARY KEY (`id_like`),
  ADD UNIQUE KEY `unique_komentar_like` (`id_user`,`id_komentar`),
  ADD KEY `fk_komentar_like_komentar` (`id_komentar`);

--
-- Indexes for table `kuliner`
--
ALTER TABLE `kuliner`
  ADD PRIMARY KEY (`id_kuliner`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notifikasi`),
  ADD KEY `fk_notifikasi_pengirim` (`id_pengirim`),
  ADD KEY `fk_notifikasi_kuliner` (`id_kuliner`),
  ADD KEY `fk_notifikasi_komentar` (`id_komentar`),
  ADD KEY `idx_notifikasi_user` (`id_user`,`id_notifikasi`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id_reset`);

--
-- Indexes for table `rating`
--
ALTER TABLE `rating`
  ADD PRIMARY KEY (`id_rating`),
  ADD UNIQUE KEY `unique_rating` (`id_user`,`id_kuliner`),
  ADD KEY `fk_rating_kuliner` (`id_kuliner`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `favorit`
--
ALTER TABLE `favorit`
  MODIFY `id_favorit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `komentar`
--
ALTER TABLE `komentar`
  MODIFY `id_komentar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `komentar_like`
--
ALTER TABLE `komentar_like`
  MODIFY `id_like` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `kuliner`
--
ALTER TABLE `kuliner`
  MODIFY `id_kuliner` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id_reset` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rating`
--
ALTER TABLE `rating`
  MODIFY `id_rating` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `favorit`
--
ALTER TABLE `favorit`
  ADD CONSTRAINT `fk_favorit_kuliner` FOREIGN KEY (`id_kuliner`) REFERENCES `kuliner` (`id_kuliner`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_favorit_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `komentar`
--
ALTER TABLE `komentar`
  ADD CONSTRAINT `fk_komentar_kuliner` FOREIGN KEY (`id_kuliner`) REFERENCES `kuliner` (`id_kuliner`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_komentar_parent` FOREIGN KEY (`parent_id`) REFERENCES `komentar` (`id_komentar`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_komentar_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `komentar_like`
--
ALTER TABLE `komentar_like`
  ADD CONSTRAINT `fk_komentar_like_komentar` FOREIGN KEY (`id_komentar`) REFERENCES `komentar` (`id_komentar`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_komentar_like_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `fk_notifikasi_komentar` FOREIGN KEY (`id_komentar`) REFERENCES `komentar` (`id_komentar`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notifikasi_kuliner` FOREIGN KEY (`id_kuliner`) REFERENCES `kuliner` (`id_kuliner`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notifikasi_pengirim` FOREIGN KEY (`id_pengirim`) REFERENCES `user` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notifikasi_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `rating`
--
ALTER TABLE `rating`
  ADD CONSTRAINT `fk_rating_kuliner` FOREIGN KEY (`id_kuliner`) REFERENCES `kuliner` (`id_kuliner`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rating_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
