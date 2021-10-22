<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;
use App\Models\BahanModel;
class Bahan extends ResourceController
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
        $sql = $this->db->table('bahan');
        $sql->select('bahan_kode as bkode');
        $sql->select('bahan_nama as bnama');
        $sql->select('bahan_stok as bstok');
        $sql->select('bahan_jenis_ukuran as bjenis_ukuran');
        // Jika ada params search
        if ($search != "") {
            $sql->groupStart();
            $sql->like('bahan_kode', $search, 'both');
            $sql->orLike('bahan_nama', $search, 'both');
            $sql->orLike('bahan_stok', $search, 'both');
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
            'bnama'          => ['label' => 'Nama Bahan', 'rules' => 'required'],
            'bkode'          => ['label' => 'Kode Bahan', 'rules' => 'required'],
            'bstok'          => ['label' => 'Stok Bahan', 'rules' => 'required'],
            'bjenis_ukuran'  => ['label' => 'Jenis Ukuran/Berat Bahan', 'rules' => 'required'],
        ]);
        //
        if (!$validation->withRequest($this->request)->run()) return $this->fail($validation->getErrors());
        $model = new BahanModel();
        $check = $model->where(['bahan_kode' => strtoupper($this->request->getVar('bkode'))])->first();
        if ($check) return $this->fail(['bkode' => 'Kode Bahan sudah digunakan!!']);
        // 
        $req['bahan_kode']           = strtoupper($this->request->getVar('bkode'));
        $req['bahan_nama']           = $this->request->getVar('bnama');
        $req['bahan_stok']           = $this->request->getVar('bstok');
        $req['bahan_jenis_ukuran']   = $this->request->getVar('bjenis_ukuran');

        if ($model->save($req)) return $this->setResponseFormat('json')->respond([
            'status' => 200,
            'error' => false,
            'message' => [
                "success" => "Berhasil melakukan penambahan data bahan!"
            ],
        ]);

        else return $this->fail("Gagal melakukan penambahan data bahan!");
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
        $model = new BahanModel();
        $check = $model->where(['bahan_kode' => strtoupper($id)])->first();
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
        } else return $this->failNotFound("Kode bahan wajib terisi!!");
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
        $model = new BahanModel();
        $check = $model->where(['bahan_kode' => strtoupper($id)])->first();
        if (!$check) return $this->fail('Bahan tidak ditemukan!!');
        // 
        $req['bahan_kode'] = strtoupper($id);
        if ($this->request->getVar('bnama')) $req['bahan_nama']        = $this->request->getVar('bnama');
        if ($this->request->getVar('bstok')) $req['bahan_stok']        = $this->request->getVar('bstok');
        if ($this->request->getVar('bjenis_ukuran')) $req['bahan_jenis_ukuran'] = $this->request->getVar('bjenis_ukuran');
        if ($model->save($req)) return $this->setResponseFormat('json')->respond([
            'status' => 200,
            'error' => false,
            'message' => [
                "success" => "Berhasil melakukan pembaharuan data bahan!"
            ],
        ]);

        else return $this->fail("Gagal melakukan pembaharuan data bahan!");
    }
}
