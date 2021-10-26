<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;
use App\Models\PembelianModel;
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
        // Query
        $sql = $this->db->table('pembelian pm');
        $sql->select('pm.pembelian_nota as pmnota');
        $sql->select('pm.pembelian_waktu as pmwaktu');
        $sql->select('pm.pembelian_total as pmtotal');
        $sql->select('pm.pembelian_keterangan as pmketerangan');
        $sql->select('pm.pengguna_id as pid');
        $sql->select('pgn.pengguna_nama as pnama');
        $sql->select('pm.pembelian_is_valid as pmisvalid');
        $sql->select('ac.pengguna_nama as acpenerima');
        $sql->join('pengguna pgn', 'pgn.pengguna_id = pm.pengguna_id', 'left');
        $sql->join('pengguna ac', 'ac.pengguna_id = pm.accepted_id', 'left');
        // Jika ada params search
        if ($search != "") {
            $sql->groupStart();
            $sql->like('pm.pembelian_nota', $search, 'both');
            $sql->orLike('pm.pembelian_total', $search, 'both');
            $sql->orLike('pgn.pengguna_nama', $search, 'both');
            $sql->groupEnd();
        }

        if ($getLevel->level != "Admin") $sql->where(['pm.pengguna_id' => $getLevel->id]);
        if ($status) $sql->where(['pm.pembelian_is_valid' => $status]);
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
    //
    private function _tambahCatatanKeJurnal($req)
    {
        
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
                $this->_tambahCatatanKeJurnal($req);
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
            }
            if ($this->request->getVar('pmketerangan')) { 
                $req['pembelian_keterangan'] = $this->request->getVar('pmketerangan');
            }
            if ($model->save($req)) {
                return $this->setResponseFormat('json')->respond([
                    'status' => 200,
                    'error' => false,
                    'message' => [
                        "success" => "Berhasil mengubah data transaksi pembelian..!",
                    ],
                ]);
            } else return $this->fail("Gagal melakukan perubahan transaksi pembelian..!");

        }
        else return $this->fail("Gagal mengubah data transaksi pembelian..!");
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
            $check = $checkData->getRowArray();
            if ($check['pembelian_is_valid'] == "Ya") {
                
            } 

            return [
                'status' => true,
                'pn' => $check['pembelian_nota'],
                'statusBefore' => $check['pembelian_is_valid'],
            ];
        }
    }
}
