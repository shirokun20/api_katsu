<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;
use App\Models\RpModel;
class RestokProduk extends ResourceController
{
    private $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
    }
    /**
     * Sub Function from show Detail Show Data
     */
    // private function _showDetailDataQuery($rpid)
    // {
    //     $sql = $this->db->table('detail_restok_produk drp');
    //     $sql->select('drp.detail_restok_produk_id as drpid');
    //     $sql->select('b.bahan_kode as bkode');
    //     $sql->select('b.bahan_nama as bnama');
    //     $sql->select('b.bahan_jenis_ukuran as bjenis_ukuran');
    //     $sql->select('drp.detail_restok_produk_jumlah as drpjml');
    //     $sql->join('bahan b', 'b.bahan_kode = drp.bahan_kode', 'left');
    //     $sql->where(['drp.restok_produk_id' => $rpid]);
    //     return $sql->get();
    // } 
    /**
     * Sub Function from show Data
     */
    private function _showDataQuery($limit, $page) 
    {
        // request 
        $search = $this->request->getGet('search') != "" ? $this->request->getGet('search') : "";
        $status = $this->request->getGet('status') != "" ? $this->request->getGet('status') : "";
        // Query
        $sql = $this->db->table('restok_produk rp');
        $sql->select('rp.restok_produk_id as rpid');
        $sql->select('rp.restok_produk_waktu as rpwaktu');
        $sql->select('rp.restok_produk_jumlah as rpjumlah');
        $sql->select('rp.restok_produk_is_valid as rpstatus');
        $sql->select('rp.produk_kode as pdkkode');
        $sql->select('pdk.produk_nama as pdknama');
        $sql->select('p.pengguna_nama as name');
        $sql->join('produk pdk', 'pdk.produk_kode = rp.produk_kode', 'left');
        $sql->join('pengguna p', 'p.pengguna_id = rp.pengguna_id', 'left');
        // Jika ada params search
        if ($search) {
            $sql->groupStart();
            $sql->like('rp.restok_produk_waktu', $search, 'both');
            $sql->orLike('rp.restok_produk_jumlah', $search, 'both');
            $sql->orLike('p.pengguna_nama', $search, 'both');
            $sql->orLike('rp.produk_kode', $search, 'both');
            $sql->orLike('pdk.produk_nama', $search, 'both');
            $sql->groupEnd();
        }
        //
        if ($status) $sql->where(['rp.restok_produk_is_valid' => $status]);
        //
        $sql->orderBy('rp.restok_produk_waktu', 'desc');
        // Jika ada params status
        $sql->limit($limit, $page * $limit);
        // result
        $result = $sql->get();
        return $result->getResult();
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
     * Primary Function from show Data
     */
    use ResponseTrait;
    public function showData()
    {
        $this->_checkLevel();
        $limit  = $this->request->getGet('limit') != "" ? $this->request->getGet('limit') : 5;
        $page   = $this->request->getGet('page') != "" ? $this->request->getGet('page') : 0;
        //
        return $this->setResponseFormat('json')->respond([
            'status' => 200,
            'error' => false,
            'data' => $this->_showDataQuery($limit, $page),
            'request' => [
                $this->request->getGet()
            ]
        ]);
    }
    /**
     * Sub Function for re stok produk
     */
    private function _tambahStok($pkkode, $jml = 0) 
    {
        $sql = $this->db->table('produk');
        $sql->set('produk_stok', 'produk_stok+'.$jml, false);
        $sql->where('produk_kode', $pkkode);
        $sql->update();
    }
    /**
     * Sub Function for re stok produk
     */
    private function _kurangiStok($pkkode, $jml = 0) 
    {
        $sql = $this->db->table('produk');
        $sql->set('produk_stok', 'produk_stok-'.$jml, false);
        $sql->where('produk_kode', $pkkode);
        $sql->update();
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
            'rpjumlah'       => ['label' => 'Jumlah', 'rules' => 'required'],
            'pkkode'         => ['label' => 'Kode Produk', 'rules' => 'required'],
        ]);
        //
        if (!$validation->withRequest($this->request)->run()) return $this->fail($validation->getErrors());
        $model = new RpModel();
        // 
        $req['restok_produk_jumlah']   = $this->request->getVar('rpjumlah');
        $req['produk_kode']            = strtoupper($this->request->getVar('pkkode'));
        $req['pengguna_id']            = $getLevel->id;
        // ketika status "Ya"
        if ($this->request->getVar('rpisvalid')) { 
            $req['restok_produk_is_valid'] = $this->request->getVar('rpisvalid');
            if ($getLevel->level == "Admin" && $req['restok_produk_is_valid'] == "Ya") 
            $req['accepted_id'] = $getLevel->id;
        }
        if ($model->save($req)) {
            // ketika status "Ya"
            if ($this->request->getVar('rpisvalid') == "Ya") 
                $this->_tambahStok($req['produk_kode'], $req['restok_produk_jumlah']);
            return $this->setResponseFormat('json')->respond([
                'status' => 200,
                'error' => false,
                'message' => [
                    "success" => "Berhasil mengisi transaksi penambahan stok produk!"
                ],
            ]);
        }

        else return $this->fail("Gagal mengisi transaksi penambahan stok produk!");
    }
    /**
     * Primary Function add data
     */
    use ResponseTrait;
    public function updateData($id = "")
    {
        $getLevel = $this->_checkLevel();
        //
        if ($getLevel->level != "Admin") return $this->fail("Maaf anda tidak bisa mengakses halaman ini!!");
        //
        $model = new RpModel();
        //
        $check = $this->_beforeUpdateData($id); 
        //
        if ($check['status']) {
            $req['restok_produk_id']       = $id;
            if ($this->request->getVar('rpjumlah')) { 
                $check['jml'] = $this->request->getVar('rpjumlah');
                $req['restok_produk_jumlah']   = $this->request->getVar('rpjumlah');
            }
            if ($this->request->getVar('pkkode')) 
            $req['produk_kode']            = $this->request->getVar('pkkode');
            //
            if ($this->request->getVar('rpisvalid')) {
                $req['restok_produk_is_valid'] = $this->request->getVar('rpisvalid');
                if ($req['restok_produk_is_valid'] == "Ya") 
                $req['accepted_id'] = $getLevel->id;
            }
            if ($model->save($req)) {
                $this->_afterUpdateData($check);
                return $this->setResponseFormat('json')->respond([
                    'status' => 200,
                    'error' => false,
                    'message' => [
                        "success" => "Berhasil melakukan perubahan transaksi penambahan stok produk!"
                    ],
                ]);
            }
            
            else return $this->fail("Gagal melakukan perubahan transaksi penambahan stok produk!");
        } else return $this->fail($check['message']);
    }
    /**
     * Sub Function before update data
     * Lumayan susah bagian ini!!!
     * dikarenakan banyak logika kemungkinan kemungkinan yang cukup rumit
     */
    private function _beforeUpdateData($id) 
    {
        $model = new RpModel();
        $check = $model->where(['restok_produk_id' => $id])->first();
        //
        if (!$check) return [
            'status' => false,
            'message' => 'Restok Produk ID tidak ditemukan!!!',
        ];

        else {
            // Ketika produk kode berbeda dengan sebelumnya
            $jml = $check['restok_produk_jumlah'];
            if (
                $this->request->getVar('pkkode') && 
                $check['produk_kode'] != $this->request->getVar('pkkode') && 
                $check['restok_produk_is_valid'] == "Ya"
            ) $this->_kurangiStok($check['produk_kode'], $check['restok_produk_jumlah']);
            // Ketika produk kode sama atau kosong dan req jumlah tidak kosong!
            else if (
                $this->request->getVar('rpjumlah') && 
                $check['restok_produk_is_valid'] == "Ya" &&
                (
                    // Ketika rpisvalid kosong atau ada dan valuenya ya
                    !$this->request->getVar('rpisvalid') ||
                    (
                        $this->request->getVar('rpisvalid') && 
                        $this->request->getVar('rpisvalid') == "Ya"
                    )
                )
            ) {
                $reqJml = $this->request->getVar('rpjumlah');
                $jml = $reqJml;
                if ($reqJml > $check['restok_produk_jumlah']) 
                  $this->_tambahStok($check['produk_kode'], $reqJml - $check['restok_produk_jumlah']);
                else if ($reqJml < $check['restok_produk_jumlah'])
                  $this->_kurangiStok($check['produk_kode'], $check['restok_produk_jumlah'] - $reqJml);
            } 
            // Ketika status nya berbeda dengan sebelumnya
            else if (
                $check['restok_produk_is_valid'] == "Ya" &&
                (
                    $this->request->getVar('rpisvalid') && 
                    $this->request->getVar('rpisvalid') != "Ya"
                )
            ) $this->_kurangiStok($check['produk_kode'], $check['restok_produk_jumlah']);
            
            return [
                'status' => true,
                'produk_kode' => $check['produk_kode'],
                'jml' => $jml,
                'statusBefore' => $check['restok_produk_is_valid'],
            ];
        }
    }
      /**
     * Sub Function after update data
     * Lumayan susah bagian ini!!!
     * dikarenakan banyak logika kemungkinan kemungkinan yang cukup rumit
     */
    private function _afterUpdateData($check) 
    {
        // Ketika pk kode tidak sama dengan sebelumnya dan 
        // statusnya sudah valid 
        // atau yang direquest sudah valid 
        if (
            (
                $this->request->getVar('pkkode') && 
                $this->request->getVar('pkkode') != $check['produk_kode']
            ) &&
            (
                $check['statusBefore'] == "Ya" &&
                ($this->request->getVar('rpisvalid') && $this->request->getVar('rpisvalid') == "Ya")
            )
        ) $this->_tambahStok($this->request->getVar('pkkode'), $check['jml']); 
        // Ketika yang dikirim valid dan sebelumnya tidak (Statusnya)
        else if (
            $this->request->getVar('rpisvalid') && 
            $this->request->getVar('rpisvalid') == "Ya" &&
            $check['statusBefore'] != "Ya"
        ) $this->_tambahStok($check['produk_kode'], $check['jml']);
    }
}