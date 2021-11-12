<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;
use App\Models\PembelianModel;
use App\Models\JurnalModel;
class Pembelian extends ResourceController
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
     * Sub Function from show Data
     */
    private function _showDataQuery($limit, $page, $getLevel) 
    {
        // request 
        $search = $this->request->getGet('search') != "" ? $this->request->getGet('search') : "";
        $status = $this->request->getGet('status') != "" ? $this->request->getGet('status') : "";
         // start date dan end date
        $startDate  = $this->request->getGet('startDate') != "" ? $this->request->getGet('startDate') : "";
        $endDate    = $this->request->getGet('endDate') != "" ? $this->request->getGet('endDate') : "";
        // Query
        $model = new PembelianModel();
        //
        $sql = $this->db->table('pembelian pm');
        $model->selectData($sql);
        $model->relation($sql);
        // filter Tanggal
        $model->filterDate($sql, [
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ]);
        // Penutup filter Tanggal
        $model->searchData($sql, $search);
        // Jika ada params search
        if ($getLevel->level != "Admin") $sql->where(['pm.pengguna_id' => $getLevel->id]);
        if ($status) $sql->where(['pm.pembelian_is_valid' => $status]);
        //
        $sql->orderBy('pm.pembelian_waktu', 'desc');
        // Jika ada params status
        $sql->limit($limit, $page * $limit);
        // result
        $result = $sql->get();
        return $result->getResult();
    }
    /**
     * Primary Function show data
     */
    use ResponseTrait;
    public function showData()
    {
        $getLevel = $this->_checkLevel();
        // if ($getLevel->level == "Admin") {
            // request
        $limit  = $this->request->getGet('limit') != "" ? $this->request->getGet('limit') : 5;
        $page   = $this->request->getGet('page') != "" ? $this->request->getGet('page') : 0;
        //
        return $this->setResponseFormat('json')->respond([
            'status' => 200,
            'error' => false,
            'data' => $this->_showDataQuery($limit, $page, $getLevel),
            'request' => [
                $this->request->getGet(),
            ]
        ]);
        // };
        return $this->fail("Maaf anda tidak bisa mengakses halaman ini!!");
    }
    /**
     * Sub Function tambah catatan ke kas
     */
    private function _tambahCatatanKeKas($req)
    {
        $model = new JurnalModel();
        $data[] = [
            'jurnal_kode' => $model->getUniqCode(1),
            'rekening_kode' => 1,
            'jurnal_ref_kode' => '101',
            'jurnal_debet' => 0,
            'jurnal_kredit' => $req['pembelian_total'],
            'jurnal_keterangan' => 'Kredit dari Kas dengan Nota Pembelian Barang: ' . $req['pembelian_nota'],
        ];
        $data[] = [
            'jurnal_kode' => $model->getUniqCode(5),
            'rekening_kode' => 5,
            'jurnal_ref_kode' => '501',
            'jurnal_debet' => $req['pembelian_total'],
            'jurnal_kredit' => 0,
            'jurnal_keterangan' => 'Debit ke Aset (barang) dengan Nota Pembelian Barang: ' . $req['pembelian_nota'],
        ];
        $sql = $this->db->table('jurnal');
        $sql->insertBatch($data);
        // if () $this->_tambahCatatanKePM($req);
    }
    /**
     * Primary Function add data
     */
    use ResponseTrait;
    public function addData()
    {
        $getLevel = $this->_checkLevel();
        //
        $validation =  \Config\Services::validation();
        $validation->setRules([
            'pmtotal'       => ['label' => 'Total (Rupiah)', 'rules' => 'required'],
            'pmketerangan'  => ['label' => 'Keterangan', 'rules' => 'required'],
        ]);
        //
        if (!$validation->withRequest($this->request)->run()) return $this->fail($validation->getErrors());
        $model = new PembelianModel();
        // 
        $req['pengguna_id']             = $getLevel->id;
        $req['pembelian_total']         = $this->request->getVar('pmtotal');
        $req['pembelian_keterangan']    = $this->request->getVar('pmketerangan');
        if ($this->request->getVar('pmwaktu')) {
            $req['pembelian_waktu']         = $this->request->getVar('pmwaktu');
            $req['pembelian_nota']          = $model->getUniqCode($req['pengguna_id'], $req['pembelian_waktu']);
        } else {
            $req['pembelian_nota']          = $model->getUniqCode($req['pengguna_id']);
        }
        // ketika status "Ya"
        if ($this->request->getVar('pmisvalid')) { 
            $req['pembelian_is_valid'] = $this->request->getVar('pmisvalid');
            if ($getLevel->level == "Admin" && $req['pembelian_is_valid'] == "Ya") 
            $req['accepted_id'] = $getLevel->id;
        }
        if ($model->save($req)) {
            // ketika status "Ya"
            // if ($this->request->getVar('pmisvalid') == "Ya") 
                // $this->_tambahStok($req['produk_kode'], $req['restok_produk_jumlah']);
            if ($this->request->getVar('pmisvalid') == "Ya") 
                $this->_tambahCatatanKeKas($req);
            return $this->setResponseFormat('json')->respond([
                'status' => 200,
                'error' => false,
                'message' => [
                    "success" => "Berhasil mengisi data transaksi pembelian..!",
                    "nota" => $req['pembelian_nota'],
                ],
            ]);
        }
        else return $this->fail("Gagal mengisi data transaksi pembelian..!");
    }
    /**
     * Primary Function update data
     */
    use ResponseTrait;
    public function updateData()
    {
        $getLevel = $this->_checkLevel();
        //
        if ($getLevel->level != "Admin") return $this->fail("Maaf anda tidak bisa mengakses halaman ini!!");
        $check = $this->_beforeUpdateData($this->request->getVar('pmnota')); 
        $model = new PembelianModel();
        if ($check['status']) {
            $req['pembelian_nota'] = $check['pn'];
            if ($this->request->getVar('pmisvalid')) { 
                $req['pembelian_is_valid']   = $this->request->getVar('pmisvalid');
                if ($getLevel->level == "Admin" && $req['pembelian_is_valid'] == "Ya") 
                $req['accepted_id']          = $getLevel->id;
            }
            if ($this->request->getVar('pmtotal')) { 
                $req['pembelian_total']      = $this->request->getVar('pmtotal');
            } else {
                $req['pembelian_total']      = $check['pembelian_total'];
            }
            if ($this->request->getVar('pmketerangan')) { 
                $req['pembelian_keterangan'] = $this->request->getVar('pmketerangan');
            }
            if ($model->save($req)) {
                if ($this->request->getVar('pmisvalid') == "Ya") 
                $this->_tambahCatatanKeKas($req);
                return $this->setResponseFormat('json')->respond([
                    'status' => 200,
                    'error' => false,
                    'message' => [
                        "success" => "Berhasil mengubah data transaksi pembelian..!",
                    ],
                ]);
            } else return $this->fail("Gagal melakukan perubahan transaksi pembelian..!");

        }
        else return $this->fail($check['message']);
    }
    /**
     * Sub Function before update data
     * Lumayan susah bagian ini!!!
     * dikarenakan banyak logika kemungkinan kemungkinan yang cukup rumit
     */
    private function _beforeUpdateData($id) 
    {
        $model = new PembelianModel();
        $checkData = $model->where(['pembelian_nota' => $id])->get();
        if (!$checkData->getNumRows()) return [
            'status' => false,
            'message' => 'Nota tidak ditemukan!!!',
        ];

        else {
            $status = true;
            $message = '';
            $check = $checkData->getRowArray();
            if ($check['pembelian_is_valid'] == "Ya") {
                $status = false;
                $message = 'Maaf tidak bisa mengubah transaksi yang sudah valid!!..';
            } 

            return [
                'status' => $status,
                'pn' => $check['pembelian_nota'],
                'pembelian_total' => $check['pembelian_total'],
                'statusBefore' => $check['pembelian_is_valid'],
                'message' => $message
            ];
        }
    }
}
