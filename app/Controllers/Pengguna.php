<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;
use App\Models\PenggunaModel;
class Pengguna extends ResourceController
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
        $pengguna = new PenggunaModel();
        // request 
        $search = $this->request->getGet('search') != "" ? $this->request->getGet('search') : "";
        $status = $this->request->getGet('status') != "" ? $this->request->getGet('status') : "";
        // Query
        $pengguna
            ->select('pengguna_id as id')
            ->select('pengguna_nama as name')
            ->select('pengguna_nohp as phone')
            ->select('pengguna_jenis as level')
            ->select('sp.status_pengguna_nama as status')
            ->join('status_pengguna sp', 'sp.status_pengguna_id = pengguna.status_pengguna_id', 'left');
        // Jika ada params search
        if ($search != "") {
            $pengguna->groupStart();
            $pengguna->like('pengguna.pengguna_nama', $search, 'both');
            $pengguna->orLike('pengguna.pengguna_nohp', $search, 'both');
            $pengguna->orLike('pengguna.pengguna_jenis', $search, 'both');
            $pengguna->groupEnd();
        }
        // Jika ada params status
        if ($status != "") {
            $pengguna->where('pengguna.status_pengguna_id', $status);
        }
        // result
        return $pengguna->findAll($limit, $page * $limit);
    }
    /**
     * Primary Function show data
     */
    use ResponseTrait;
    public function showData()
    {
        $getLevel = $this->_checkLevel();
        if ($getLevel->level == "Admin") {
            // request
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
        };

        return $this->fail("Maaf anda tidak bisa mengakses halaman ini!!");
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
            'name' => ['label' => 'Nama', 'rules' => 'required'],
            'phone' => ['label' => 'Nomor Telepon', 'rules' => 'required'],
            'level' => ['label' => 'Level', 'rules' => 'required'],
        ]);
        //
        if (!$validation->withRequest($this->request)->run()) return $this->fail($validation->getErrors());
        $model = new PenggunaModel();
        $user = $model->where(['pengguna_nohp' => $this->request->getVar('phone')])->first();
        if ($user) return $this->fail(['phone' => 'Nomor Telepon sudah digunakan!!']);
        
        $insert['pengguna_nohp']       = $this->request->getVar('phone');
        $insert['pengguna_nama']       = $this->request->getVar('name');
        $insert['pengguna_jenis']      = $this->request->getVar('level') == "Admin" ? "Admin" : "Pegawai";;
        $insert['status_pengguna_id']  = 1;

        if ($model->save($insert)) return $this->setResponseFormat('json')->respond([
            'status' => 200,
            'error' => false,
            'message' => [
                "success" => "Berhasil melakukan penambahan data pengguna!"
            ],
        ]);

        else return $this->fail("Gagal melakukan penambahan data pengguna!");
    }
     /**
     * Sub Function show detail data
     */
    private function _showDetailQuery($id = "") 
    {
        $pengguna = new PenggunaModel();
        return $pengguna
            ->select('pengguna_id as id')
            ->select('pengguna_nama as name')
            ->select('pengguna_nohp as phone')
            ->select('pengguna_jenis as level')
            ->select('sp.status_pengguna_nama as status')
            ->join('status_pengguna sp', 'sp.status_pengguna_id = pengguna.status_pengguna_id', 'left')
            ->where(['pengguna.pengguna_id' => $id]);
    }
    /**
     * Primary Function show detail data
     */
    use ResponseTrait;
    public function showDetail($id = "")
    {
        $this->_checkLevel();
        if ($id != "") {
            $check = $this->_showDetailQuery($id)->first();
            if ($check) {
                return $this->setResponseFormat('json')->respond([
                    'status' => 200,
                    'error' => false,
                    'data' => $check,
                ]);
            } else {
                return $this->failNotFound("Akun tidak ditemukan!");
            }
        } else {
            return $this->failNotFound("ID Wajib terisi!!");
        }
    }
    /**
     * Primary Function update data
     */
    use ResponseTrait;
    public function updateData($id = "") 
    {
        $getLevel = $this->_checkLevel();
        //
        $model = new PenggunaModel();
        $where = ['pengguna_id' => $id];
        if ($getLevel->level != "Admin") {
            if ($id != $getLevel->id) return $this->fail("Maaf anda tidak bisa melakukan perubahan dengan id yang lain!!");
        }
        $user = $model->where($where)->first();
        // 
        if (!$user) return $this->failNotFound("Akun tidak ditemukan!");
        // 
        $req['pengguna_id'] = $id;
        // Request JSON
        if ($this->request->getVar('name')) $req['pengguna_nama'] = $this->request->getVar('name');
        if ($this->request->getVar('phone')) {
            $check = $model->where([
                'pengguna_id !=' => $req['pengguna_id'],
                'pengguna_nohp' => $this->request->getVar('phone'),
            ])->first();
            if (!$check) $req['pengguna_nohp'] = $this->request->getVar('phone');
            else return $this->fail("Maaf anda tidak bisa melakukan perubahan dengan nomor handphone yang sudah digunakan!!!");

        };
        if ($this->request->getVar('level') && $getLevel->level == "Admin") $req['pengguna_jenis'] = $this->request->getVar('level') != "Pegawai" ? "Admin" : "Pegawai";
        if ($this->request->getVar('status') && $getLevel->level == "Admin") $req['status_pengguna_id'] = $this->request->getVar('status');
        //
        if (count($req) > 1) {
            if ($model->save($req)) return $this->setResponseFormat('json')->respond([
                'status' => 200,
                'error' => false,
                'message' => [
                    "success" => "Berhasil melakukan pembaharuan data pengguna!"
                ],
            ]);

            return $this->fail("Gagal melakukan pembaharuan data pengguna!");
        }
        return $this->fail("Tidak ada data pengguna yang akan di diperbaharui!");
    }
    /**
     * Primary Function delete data
     */
    use ResponseTrait;
    public function deleteData($id = "")
    {
        $getLevel = $this->_checkLevel();
        if ($getLevel->level != "Admin") return $this->fail("Maaf anda tidak bisa mengakses halaman ini!!");
        $model = new PenggunaModel();
        $check = $this->_showDetailQuery($id)->first();
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
            } else {
                return $this->failNotFound("Akun tidak ditemukan!");
            }
        } else {
            return $this->failNotFound("ID Wajib terisi!!");
        }
    }
    /**
     * Primary Function get status pengguna
     */
    public function status()
    {
        $this->_checkLevel();
        $data = $this->db
                      ->table('status_pengguna')
                      ->select('status_pengguna_id as status_id, status_pengguna_nama as status_text', false)
                      ->get();
        return $this->setResponseFormat('json')->respond([
            'status' => 200,
            'error' => false,
            'data' => $data->getResult(),
        ]);
    }
}
