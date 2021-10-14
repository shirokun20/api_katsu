<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;
class Penjualan extends ResourceController
{

    private $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
    }
    /**
     * Check Autorization
     */
    private function _checkLevel() 
    {
        $key = getenv('TOKEN_SECRET');
        $header = $this->request->getServer('HTTP_AUTHORIZATION');
        if(!$header) return $this->failUnauthorized('Token Required');
        $token = explode(' ', $header)[1];
 
        try {
            $decoded = JWT::decode($token, $key, ['HS256']);
            $response = $decoded->data;
            return $response;
        } catch (\Throwable $th) {
            return $this->fail('Token tidak valid!!');
        }
    }
    /**
     * Primary function of status pembayaran
     */
    public function statusPembayaran()
    {
        $this->_checkLevel();
        $data = $this->db
                      ->table('status_pembayaran')
                      ->select('status_pembayaran_id as status_id, status_pembayaran_nama as status_text', false)
                      ->get();
        return $this->setResponseFormat('json')->respond([
            'status' => 200,
            'error' => false,
            'data' => $data->getResult(),
        ]);
    }
    /**
     * Primary function of status pemesanan
     */
    public function statusPemesanan()
    {
        $this->_checkLevel();
        $data = $this->db
                      ->table('status_pemesanan')
                      ->select('status_pemesanan_id as status_id, status_pemesanan_nama as status_text', false)
                      ->get();
        return $this->setResponseFormat('json')->respond([
            'status' => 200,
            'error' => false,
            'data' => $data->getResult(),
        ]);
    }
}
