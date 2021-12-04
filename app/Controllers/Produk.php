<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;
use App\Models\ProdukModel;
class Produk extends ResourceController
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
    private function _showDataQuery($limit, $page) 
    {
        // request 
        $search = $this->request->getGet('search') != "" ? $this->request->getGet('search') : "";
        // Query
        $sql = $this->db->table('produk');
        $sql->select('produk_kode as pkode');
        $sql->select('produk_nama as pnama');
        $sql->select('produk_harga as pharga');
        $sql->select('produk_stok as pstok');
        // Jika ada params search
        if ($search != "") {
            $sql->groupStart();
            $sql->like('produk_nama', $search, 'both');
            $sql->orLike('produk_harga', $search, 'both');
            $sql->orLike('produk_stok', $search, 'both');
            $sql->groupEnd();
        }
        // Jika ada params status
        $sql->limit($limit, $page * $limit);
        // result
        return $sql->get();
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
            'data' => $this->_showDataQuery($limit, $page)->getResult(),
            'request' => [
                $this->request->getGet()
            ]
        ]);
    }
    /**
     * Primary Function add data
     */
    use ResponseTrait;
    public function addData()
    {
        $getLevel = $this->_checkLevel();
        //
        if ($getLevel->level != "Admin") return $this->fail("Maaf anda tidak bisa mengakses halaman ini!!");
        //
        $validation =  \Config\Services::validation();
        $validation->setRules([
            'pnama'  => ['label' => 'Nama Produk', 'rules' => 'required'],
            'pkode'  => ['label' => 'Kode Produk', 'rules' => 'required'],
            'pharga' => ['label' => 'Harga Produk', 'rules' => 'required'],
            'pstok'  => ['label' => 'Stok Produk', 'rules' => 'required'],
        ]);
        //
        if (!$validation->withRequest($this->request)->run()) return $this->fail($validation->getErrors());
        $model = new ProdukModel();
        $check = $model->where(['produk_kode' => strtoupper($this->request->getVar('pkode'))])->first();
        if ($check) return $this->fail(['pkode' => 'Kode Produk sudah digunakan!!']);
        // 
        $req['produk_kode']           = strtoupper($this->request->getVar('pkode'));
        $req['produk_nama']           = $this->request->getVar('pnama');
        $req['produk_harga']          = $this->request->getVar('pharga');
        $req['produk_stok']           = $this->request->getVar('pstok');

        if ($model->save($req)) return $this->setResponseFormat('json')->respond([
            'status' => 200,
            'error' => false,
            'message' => [
                "success" => "Berhasil melakukan penambahan data produk!"
            ],
        ]);

        else return $this->fail("Gagal melakukan penambahan data produk!");
    }
    /**
     * Primary Function delete data
     */
    use ResponseTrait;
    public function deleteData($id = "")
    {
        $getLevel = $this->_checkLevel();
        // 
        if ($getLevel->level != "Admin") return $this->fail("Maaf anda tidak bisa mengakses halaman ini!!");
        $model = new ProdukModel();
        $check = $model->where(['produk_kode' => strtoupper($id)])->first();
        // 
        if ($id != "") {
            if ($check) {
                $model->delete($id);
                return $this->setResponseFormat('json')->respond([
                    'status' => 200,
                    'error' => false,
                    'message' => [
                        "success" => "Berhasil menghapus data!"
                    ],
                ]);
            } else return $this->failNotFound("Akun tidak ditemukan!");
        } else return $this->failNotFound("Kode produk wajib terisi!!");
    }
    /**
     * Primary Function update data
     */
    use ResponseTrait;
    public function updateData($id = "")
    {
        $getLevel = $this->_checkLevel();
        //
        if ($getLevel->level != "Admin") return $this->fail("Maaf anda tidak bisa mengakses halaman ini!!");
        //
        $model = new ProdukModel();
        $check = $model->where(['produk_kode' => strtoupper($id)])->first();
        if (!$check) return $this->fail('Produk tidak ditemukan!!');
        // 
        $req['produk_kode'] = strtoupper($id);
        if ($this->request->getVar('pnama')) $req['produk_nama']        = $this->request->getVar('pnama');
        if ($this->request->getVar('pharga')) $req['produk_harga']      = $this->request->getVar('pharga');
        if ($this->request->getVar('pstok')) $req['produk_stok']        = $this->request->getVar('pstok');
        if ($model->save($req)) return $this->setResponseFormat('json')->respond([
            'status' => 200,
            'error' => false,
            'message' => [
                "success" => "Berhasil melakukan pembaharuan data produk!"
            ],
        ]);

        else return $this->fail("Gagal melakukan pembaharuan data produk!");
    }
}
