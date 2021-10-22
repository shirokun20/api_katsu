-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 22 Okt 2021 pada 16.47
-- Versi server: 10.4.17-MariaDB
-- Versi PHP: 7.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `payamkatsu_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `bahan`
--

CREATE TABLE `bahan` (
  `bahan_kode` varchar(20) NOT NULL,
  `bahan_nama` varchar(100) NOT NULL,
  `bahan_stok` double NOT NULL,
  `bahan_jenis_ukuran` varchar(10) NOT NULL DEFAULT 'kg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `bahan`
--

INSERT INTO `bahan` (`bahan_kode`, `bahan_nama`, `bahan_stok`, `bahan_jenis_ukuran`) VALUES
('AF', 'Ayam Fillet', 2, 'kg'),
('BP', 'Bawang Putih', 50, 'gram'),
('GRM', 'Garam', 55, 'gram'),
('GS', 'Gas', 3, 'kg'),
('MP', 'Merica (Butir)', 20, 'gram'),
('MSK', 'Masako (Penyedap Rasa)', 3, 'bungkus'),
('MYK', 'Minyak', 1, 'Liter'),
('PMK', 'Plastik Mika', 40, 'pcs'),
('SS', 'Sasa', 1, 'bungkus'),
('TLR', 'Telur', 250, 'gram'),
('TM', 'Tepung Maizena', 100, 'gram'),
('TT', 'Tepung Terigu', 0.5, 'kg');

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_penjualan`
--

CREATE TABLE `detail_penjualan` (
  `detail_penjualan_id` int(10) NOT NULL,
  `produk_kode` varchar(20) NOT NULL,
  `penjualan_nota` varchar(100) NOT NULL,
  `detail_penjualan_qty` int(10) NOT NULL,
  `detail_penjualan_harga` int(100) NOT NULL,
  `detail_penjualan_total` int(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `jurnal`
--

CREATE TABLE `jurnal` (
  `jurnal_kode` varchar(100) NOT NULL COMMENT 'DU-tgl-0001',
  `jurnal_waktu` datetime NOT NULL,
  `jurnal_ref` varchar(100) NOT NULL,
  `jurnal_debet` int(100) NOT NULL,
  `jurnal_kredit` int(100) NOT NULL,
  `jurnal_saldo` int(100) NOT NULL,
  `jurnal_keterangan` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembelian`
--

CREATE TABLE `pembelian` (
  `pembelian_nota` varchar(100) NOT NULL COMMENT 'PB-TGL-PENGGUNA_ID-0001',
  `pembelian_waktu` datetime NOT NULL,
  `pembelian_total` int(10) NOT NULL,
  `pembelian_keterangan` int(11) NOT NULL,
  `pengguna_id` int(10) NOT NULL,
  `pembelian_is_valid` enum('Ya','Tidak','Menunggu divalidasi') NOT NULL DEFAULT 'Menunggu divalidasi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengguna`
--

CREATE TABLE `pengguna` (
  `pengguna_id` int(10) NOT NULL,
  `pengguna_nama` varchar(100) NOT NULL,
  `pengguna_nohp` varchar(15) NOT NULL,
  `pengguna_jenis` enum('Admin','Pegawai','Konsumen') NOT NULL DEFAULT 'Pegawai',
  `status_pengguna_id` int(2) NOT NULL,
  `pengguna_tgl_masuk` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `pengguna`
--

INSERT INTO `pengguna` (`pengguna_id`, `pengguna_nama`, `pengguna_nohp`, `pengguna_jenis`, `status_pengguna_id`, `pengguna_tgl_masuk`) VALUES
(1, 'Shiro 2', '089661352511', 'Admin', 1, '2021-10-12'),
(2, 'Pegawai 2', '089661352512', 'Admin', 1, '2021-10-13'),
(3, 'Kepo Ah 3', '089661352513', 'Pegawai', 3, '2021-10-13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `penjualan`
--

CREATE TABLE `penjualan` (
  `penjualan_nota` varchar(100) NOT NULL COMMENT 'PJ-TGL-PENGGUNA_ID-0001',
  `penjualan_waktu` datetime NOT NULL DEFAULT current_timestamp(),
  `penjualan_sub_total` int(100) NOT NULL DEFAULT 0,
  `penjualan_ongkir` int(100) NOT NULL DEFAULT 0,
  `penjualan_total` int(100) NOT NULL DEFAULT 0,
  `penjualan_keterangan` text NOT NULL,
  `penjualan_metode_pembayaran` enum('cash','transfer') NOT NULL,
  `penjualan_bukti_transfer` varchar(100) NOT NULL,
  `pengguna_id` int(10) NOT NULL,
  `status_pembayaran_id` int(2) NOT NULL,
  `status_pemesanan` int(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `produk_kode` varchar(20) NOT NULL,
  `produk_nama` varchar(100) NOT NULL,
  `produk_harga` int(10) NOT NULL,
  `produk_stok` int(10) NOT NULL DEFAULT 0,
  `produk_stok_retur` int(10) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`produk_kode`, `produk_nama`, `produk_harga`, `produk_stok`, `produk_stok_retur`) VALUES
('AK', 'Ayam Katsu', 7000, 20, 0),
('NS', 'Nasi', 5000, 10, 0),
('PK', 'Paket Nasi Dan Ayam Katsu', 12000, 10, 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `restok_produk`
--

CREATE TABLE `restok_produk` (
  `restok_produk_id` int(10) NOT NULL,
  `restok_produk_waktu` timestamp NOT NULL DEFAULT current_timestamp(),
  `restok_produk_jumlah` int(11) NOT NULL COMMENT 'Jumlah Produk yang dihasilkan',
  `restok_produk_is_valid` enum('Ya','Tidak','Menunggu divalidasi') NOT NULL DEFAULT 'Menunggu divalidasi',
  `produk_kode` varchar(20) NOT NULL,
  `pengguna_id` int(10) NOT NULL,
  `accepted_id` int(10) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `restok_produk`
--

INSERT INTO `restok_produk` (`restok_produk_id`, `restok_produk_waktu`, `restok_produk_jumlah`, `restok_produk_is_valid`, `produk_kode`, `pengguna_id`, `accepted_id`) VALUES
(1, '2021-10-20 14:33:39', 10, 'Ya', 'AK', 1, 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `status_pembayaran`
--

CREATE TABLE `status_pembayaran` (
  `status_pembayaran_id` int(2) NOT NULL,
  `status_pembayaran_nama` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `status_pembayaran`
--

INSERT INTO `status_pembayaran` (`status_pembayaran_id`, `status_pembayaran_nama`) VALUES
(1, 'Belum Lunas'),
(2, 'Lunas'),
(3, 'Dibatalkan Konsumen'),
(4, 'Dibatalkan Admin');

-- --------------------------------------------------------

--
-- Struktur dari tabel `status_pemesanan`
--

CREATE TABLE `status_pemesanan` (
  `status_pemesanan_id` int(2) NOT NULL,
  `status_pemesanan_nama` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `status_pemesanan`
--

INSERT INTO `status_pemesanan` (`status_pemesanan_id`, `status_pemesanan_nama`) VALUES
(1, 'Pending Order'),
(2, 'Selesai'),
(3, 'Dikirim');

-- --------------------------------------------------------

--
-- Struktur dari tabel `status_pengguna`
--

CREATE TABLE `status_pengguna` (
  `status_pengguna_id` int(2) NOT NULL,
  `status_pengguna_nama` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `status_pengguna`
--

INSERT INTO `status_pengguna` (`status_pengguna_id`, `status_pengguna_nama`) VALUES
(1, 'Aktif'),
(2, 'Suspend'),
(3, 'Belum aktif');

-- --------------------------------------------------------

--
-- Struktur dari tabel `suplier`
--

CREATE TABLE `suplier` (
  `suplier_kode` varchar(10) NOT NULL,
  `suplier_nama` varchar(100) NOT NULL,
  `suplier_hp` varchar(15) NOT NULL,
  `suplier_alamat` text NOT NULL,
  `suplier_keterangan` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `bahan`
--
ALTER TABLE `bahan`
  ADD PRIMARY KEY (`bahan_kode`);

--
-- Indeks untuk tabel `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  ADD PRIMARY KEY (`detail_penjualan_id`);

--
-- Indeks untuk tabel `jurnal`
--
ALTER TABLE `jurnal`
  ADD PRIMARY KEY (`jurnal_kode`);

--
-- Indeks untuk tabel `pembelian`
--
ALTER TABLE `pembelian`
  ADD PRIMARY KEY (`pembelian_nota`);

--
-- Indeks untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`pengguna_id`);

--
-- Indeks untuk tabel `penjualan`
--
ALTER TABLE `penjualan`
  ADD PRIMARY KEY (`penjualan_nota`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`produk_kode`);

--
-- Indeks untuk tabel `restok_produk`
--
ALTER TABLE `restok_produk`
  ADD PRIMARY KEY (`restok_produk_id`);

--
-- Indeks untuk tabel `status_pembayaran`
--
ALTER TABLE `status_pembayaran`
  ADD PRIMARY KEY (`status_pembayaran_id`);

--
-- Indeks untuk tabel `status_pemesanan`
--
ALTER TABLE `status_pemesanan`
  ADD PRIMARY KEY (`status_pemesanan_id`);

--
-- Indeks untuk tabel `status_pengguna`
--
ALTER TABLE `status_pengguna`
  ADD PRIMARY KEY (`status_pengguna_id`);

--
-- Indeks untuk tabel `suplier`
--
ALTER TABLE `suplier`
  ADD PRIMARY KEY (`suplier_kode`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  MODIFY `detail_penjualan_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `pengguna_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `restok_produk`
--
ALTER TABLE `restok_produk`
  MODIFY `restok_produk_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `status_pemesanan`
--
ALTER TABLE `status_pemesanan`
  MODIFY `status_pemesanan_id` int(2) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `status_pengguna`
--
ALTER TABLE `status_pengguna`
  MODIFY `status_pengguna_id` int(2) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
