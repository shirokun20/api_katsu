<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;
use App\Models\JurnalModel;
use App\Libraries\ShiroLib;

class Jurnal extends ResourceController
{
    private $db;
    protected $shiroLib;

    public function __construct() {
        $this->db = \Config\Database::connect();
        $this->shiroLib = new ShiroLib();
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
    private function _showDataQuery($month = '', $year = '')
    {
         // start date dan end date
         $startDate  = $this->request->getGet('startDate') != "" ? $this->request->getGet('startDate') : "";
         $endDate    = $this->request->getGet('endDate') != "" ? $this->request->getGet('endDate') : "";
         // bulan dan tahun
         $model = new JurnalModel();
         $sql = $this->db->table('jurnal j');
         $model->selectData($sql);
         $model->filterDate($sql, [
             'startDate' => $startDate, 
             'endDate' => $endDate
         ]);
         $model->filterMonthYear($sql, [
             'month' => $month,
             'year' => $year,
         ]);

        $sql->orderBy('j.jurnal_id', 'asc');
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
    /**
     * Primary Function Data Rekening
     */
    use ResponseTrait;
    public function showRekening()
    {
        $getLevel = $this->_checkLevel();
        // 
        if ($getLevel->level != "Admin") return $this->fail("Maaf anda tidak bisa mengakses halaman ini!!");
        //
        $res = $this->db->table('rekening');
        $res->select('rekening_kode as rkode');
        $res->select('rekening_nama as rnama');
        $res->select('rekening_jenis as rjenis');
        $res->orderBy('rekening_kode', 'ASC');

        return $this->setResponseFormat('json')->respond([
            'status' => 200,
            'error' => false,
            'data' => $res->get()->getResult(),
        ]);
        // };
    }
    /**
     * Primary Function Data Ref
     */
    use ResponseTrait;
    public function showRef()
    {
        $getLevel = $this->_checkLevel();
        // 
        if ($getLevel->level != "Admin") return $this->fail("Maaf anda tidak bisa mengakses halaman ini!!");
        //
        $res = $this->db->table('jurnal_ref');
        $res->select('jurnal_ref_kode as jrefkode');
        $res->select('jurnal_ref_nama as jrefnama');
        $res->orderBy('jurnal_ref_kode', 'ASC');

        return $this->setResponseFormat('json')->respond([
            'status' => 200,
            'error' => false,
            'data' => $res->get()->getResult(),
        ]);
        // };
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
        //
        $validation->setRules([
            'data'       => ['label' => 'Data Jurnal', 'rules' => 'required'],
        ]);
        if (!$validation->withRequest($this->request)->run()) return $this->fail($validation->getErrors());
        $data = $this->request->getVar('data');
        $dataDecode = json_encode($data);
        $res = $this->_beforeAddData($dataDecode);
        if ($res['status']) {
            return $this->setResponseFormat('json')->respond([
                'status' => 200,
                'error' => false,
                'message' => [
                    "success" => $res['message'],
                ],
            ]);
        } else return $this->fail($res);
    }

    private function _beforeAddData($data)
    {
        $status = false;
        $message = "";
        $model = new JurnalModel();
        $decode = json_decode($data, true);
        if (count($decode)) {
            $arData = [];
            $count = 0;
            $rekkode = "";
            $output = $this->shiroLib->shiro_arr_sort($decode, [
                'rekkode' => SORT_ASC,
            ]);
            foreach ($output as $key) {
                $key = (array) $key;
                if ($key['rekkode'] <= 5 && $key['rekkode'] > 0)  {
                    if ($rekkode == $key['rekkode']) {
                        $arData[$count - 1]['jurnal_debet']      = @$key['jurdebet'] + @$arData[$count - 1]['jurnal_debet']+0;
                        $arData[$count - 1]['jurnal_kredit']     = @$key['jurkredit'] + @$arData[$count - 1]['jurnal_kredit']+0;
                    } else {
                        $r = [];
                        $r['rekening_kode']     = $key['rekkode'];
                        $r['jurnal_kode']       = $model->getUniqCode($key['rekkode']);
                        $r['jurnal_debet']      = @$key['jurdebet'] ? @$key['jurdebet'] : 0;
                        $r['jurnal_kredit']     = @$key['jurkredit'] ? @$key['jurkredit'] : 0;
                        $r['jurnal_ref_kode']   = $key['jurref'];
                        $r['jurnal_keterangan'] = @$key['jurket'] ? $key['jurket'] : "";
                        $rekkode = $key['rekkode'];
                        $arData[] = $r;
                        $count++;
                    }
                } else continue;
            }

            if (count($arData)) {
                if ($model->insertBatch($arData)) {
                    $status = true;
                    $message = "Sukses mengisi data jurnal";
                } else {
                    $message = "Gagal mengisi data jurnal";
                }
            } else {
                $message = "Isi data jurnal tidak tepat!";
            }
        } else {
            $message = "Data jurnal tidak boleh kosong!";
        }

        return [
            'status' => $status,
            'message' => $message,
            'data' => $decode,
        ];
    }
}
