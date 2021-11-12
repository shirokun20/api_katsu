<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;
use App\Models\PembelianModel;
use App\Models\PenjualanModel;

class Info extends ResourceController
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
    //
    private function _pendapatan($data)
    {
        $model = new PenjualanModel();
        //
        $sql = $this->db->table('penjualan pj');
        $sql->select('SUM(pj.penjualan_total) as pendapatan');
        //
        $sql->where(['pj.status_pembayaran_id' => '2']);
        $sql->where(['pj.status_pemesanan_id' => '2']);
        //
        $model->filterDate($sql, [
            'startDate' => $data['startDate'],
            'endDate'   => $data['endDate'],
        ]);

        $model->filterMonthYear($sql, [
            'month' => $data['month'],
            'year' => $data['year'],
        ]);

        $result = $sql->get();
        return $result->getRow();
    }
    //
    private function _pengeluaran($data)
    {
        $model = new PembelianModel();
        //
        $sql = $this->db->table('pembelian pm');
        $sql->select('SUM(pm.pembelian_total) as pengeluaran');
        //
        $sql->where(['pm.pembelian_is_valid' => 'Ya']);
        //
        $model->filterDate($sql, [
            'startDate' => $data['startDate'],
            'endDate'   => $data['endDate'],
        ]);

        $model->filterMonthYear($sql, [
            'month' => $data['month'],
            'year' => $data['year'],
        ]);

        $result = $sql->get();
        return $result->getRow();
    }
    //
    private function _showDataQuery($month = '', $year = '')
    {
        // start date dan end date
        $startDate  = $this->request->getGet('startDate') != "" ? $this->request->getGet('startDate') : "";
        $endDate    = $this->request->getGet('endDate') != "" ? $this->request->getGet('endDate') : "";
         // bulan dan tahun
        // $sql->orderBy('j.jurnal_id', 'asc');
        $pencarian = [
            'month'     => $month,
            'year'      => $year,
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ];
        $data['pendapatan'] = (int) $this->_pendapatan($pencarian)->pendapatan ?? 0;
        $data['pengeluaran'] = (int) $this->_pengeluaran($pencarian)->pengeluaran ?? 9;
        // result
        return $data;
    }
    /**
     * Sub Function from show Data
     */
    use ResponseTrait;
    public function ppData()
    {
        $getLevel = $this->_checkLevel();
        // 
        if ($getLevel->level != "Admin") return $this->fail("Maaf anda tidak bisa mengakses halaman ini!!");
        // request
        $month = $this->request->getGet('month') != "" ? $this->request->getGet('month') : "";
        $year = $this->request->getGet('year') != "" ? $this->request->getGet('year') : "";
        if ($month) {
            if ($month >= 13 || $month <= 0) return $this->fail('Harap isi bulan dengan valid!!');
        } 
        //
        return $this->setResponseFormat('json')->respond([
            'status' => 200,
            'error' => false,
            'data' => $this->_showDataQuery($month, $year),
            'request' => [
                $this->request->getGet(),
            ]
        ]);
        // };
    }
}
