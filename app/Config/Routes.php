<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
// $routes->set404Override();
$routes->setAutoRoute(true);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');
//
$routes->post('api/v1/login', 'Login::index');
// Pengguna
$routes->get('api/v1/pengguna', 'Pengguna::showData', ['filter'=>'auth']);
$routes->get('api/v1/pengguna/(:num)', 'Pengguna::showDetail/$1', ['filter'=>'auth']);
$routes->get('api/v1/pengguna/token', 'Pengguna::showDetailByToken', ['filter'=>'auth']);
$routes->post('api/v1/pengguna', 'Pengguna::addData', ['filter'=>'auth']);
$routes->put('api/v1/pengguna/(:num)', 'Pengguna::updateData/$1', ['filter'=>'auth']);
$routes->delete('api/v1/pengguna/(:num)', 'Pengguna::deleteData/$1', ['filter'=>'auth']);
// Status
$routes->get('api/v1/status/pengguna', 'Pengguna::status', ['filter'=>'auth']);
$routes->get('api/v1/status/pembayaran', 'Penjualan::statusPembayaran', ['filter'=>'auth']);
$routes->get('api/v1/status/pemesanan', 'Penjualan::statusPemesanan', ['filter'=>'auth']);
// Produk
$routes->get('api/v1/produk', 'Produk::showData', ['filter'=>'auth']);
$routes->post('api/v1/produk', 'Produk::addData', ['filter'=>'auth']);
$routes->put('api/v1/produk/(:alphanum)', 'Produk::updateData/$1', ['filter'=>'auth']);
$routes->delete('api/v1/produk/(:alphanum)', 'Produk::deleteData/$1', ['filter'=>'auth']);
// Bahan
$routes->get('api/v1/bahan', 'Bahan::showData', ['filter'=>'auth']);
$routes->post('api/v1/bahan', 'Bahan::addData', ['filter'=>'auth']);
$routes->put('api/v1/bahan/(:alphanum)', 'Bahan::updateData/$1', ['filter'=>'auth']);
$routes->delete('api/v1/bahan/(:alphanum)', 'Bahan::deleteData/$1', ['filter'=>'auth']);
// Restok Produk
$routes->get('api/v1/resproduk', 'RestokProduk::showData', ['filter'=>'auth']);
$routes->post('api/v1/resproduk', 'RestokProduk::addData', ['filter'=>'auth']);
$routes->put('api/v1/resproduk/(:num)', 'RestokProduk::updateData/$1', ['filter'=>'auth']);
// Pembelian
$routes->get('api/v1/pembelian', 'Pembelian::showData', ['filter'=>'auth']);
$routes->post('api/v1/pembelian', 'Pembelian::addData', ['filter'=>'auth']);
$routes->put('api/v1/pembelian', 'Pembelian::updateData', ['filter'=>'auth']);
// Penjualan
$routes->get('api/v1/penjualan', 'Penjualan::showData', ['filter'=>'auth']);
$routes->post('api/v1/penjualan', 'Penjualan::addData', ['filter'=>'auth']);
$routes->put('api/v1/penjualan', 'Penjualan::updateData', ['filter'=>'auth']);
// Jurnal
$routes->get('api/v1/jurnal', 'Jurnal::showData', ['filter'=>'auth']);
$routes->post('api/v1/jurnal', 'Jurnal::addData', ['filter'=>'auth']);
$routes->get('api/v1/jurnal/rekening', 'Jurnal::showRekening', ['filter'=>'auth']);
$routes->get('api/v1/jurnal/ref', 'Jurnal::showRef', ['filter'=>'auth']);
// $routes->put('api/v1/jurnal', 'Jurnal::updateData', ['filter'=>'auth''[']);
// Pendapatan Pengeluaran
$routes->get('api/v1/pp', 'Info::ppData', ['filter'=>'auth']);
//
$routes->set404Override(function () {
    $this->response->setStatusCode(404, 'Nope. Not here.');
    echo json_encode([
        'status' => 404,
        'error' => 404,
        'message' => [
            'error' => 'Halaman tidak ditemukan!!',
        ]
    ]);
});
/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
